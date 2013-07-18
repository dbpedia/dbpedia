<?php

namespace dbpedia
{

// Set default response code to 500. Otherwise, PHP just dies and 
// sends "200 OK" if there are syntax errors etc., which sucks.
header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error');

use dbpedia\LocalConfiguration;
use dbpedia\util\ConfigHandler;
use dbpedia\util\StringUtil;
use dbpedia\util\HttpResponse;
use dbpedia\util\PostBodyRequest;
use dbpedia\util\PostParamsRequest;
use dbpedia\util\PostMultiRequest;
use dbpedia\wikiparser\WikiTitle;
use dbpedia\wikiparser\WikiParser;
use dbpedia\ontology\OntologyNamespaces;
use dbpedia\ontology\OntologyReader;
use dbpedia\ontology\OWLOntologyWriter;

error_reporting(E_ALL | E_STRICT);
include('DBpedia.php');

$processor = new UpdateProcessor();
$processor->process();
$processor->cleanUp();

class UpdateProcessor
{
    private static $ontologyTemplates = array(\dbpedia\ontology\OntologyClass::TEMPLATE_NAME, \dbpedia\ontology\OntologyObjectProperty::TEMPLATE_NAME, \dbpedia\ontology\OntologyDataTypeProperty::TEMPLATE_NAME);
    
    private static $templateMappingTemplates = array(\dbpedia\mapping\TemplateMapping::TEMPLATE_NAME);
    
    private static $tableMappingTemplates = array(\dbpedia\mapping\TableMapping::TEMPLATE_NAME);
    
    // mapping from 'newarticle' parameter values to 'action' values
    private static $actions = array('true' => 'create', 'false' => 'update');
    
    private $log;

    private $configHandler;
    
    /**
     * 'create', 'update', or 'delete'. TODO: 'delete' is not yet implemented.
     */
    private $action;
    
    /**
     * article revision id. may be null.
     */
    private $revision;
    
    /**
     * original page title.
     */
    private $rawTitle;
    
    /**
     * original page source.
     */
    private $rawPage;
    
    /**
     * @var WikiTitle
     */
    private $parsedTitle;
    
    /**
     * @var PageNode
     */
    private $parsedPage;
    
    /**
     * temporary file used to hold authentication cookies
     */
    private $cookieFile;
    
    /**
     * edit token assigned when we logging into the MediaWiki API
     * defaults to standard anonymous edit token
     */
    private $editToken = '+\\';
    
    public function __construct()
    {
        $this->log = \dbpedia\core\DBpediaLogger::getLogger(__CLASS__);
    }

    private function response( $code, $message, $body = null )
    {
        if (headers_sent())
        {
            $this->log->error('cannot change response header to ' . $code . ' ' . $message);
        }
        else
        {
            header($_SERVER['SERVER_PROTOCOL'] . ' ' . $code . ' ' . $message);
            header('Content-Type: text/plain; charset=UTF-8');
            $this->log->info('sent response header ' . $code . ' ' . $message);
        }
        
        if ($body === null) $body = $message;
        echo $body;
        $this->log->debug('sent response body' . PHP_EOL . substr($body, 0, 500));
        
        return $code >= 200 && $code < 300;
    }
    
    /**
     */
    private function sendSparul( $sparul )
    {
        $request = new PostBodyRequest();
        $request->setUrl(LocalConfiguration::sparulUrl);
        $request->setBody($sparul, 'text/xml; charset=UTF-8');
        
        // The HTTP server included by JDK 6 REST stuff sucks. We have to tell it not to send 
        // a "100" response, otherwise it doesn't send data with the real "200" response,
        // to which curl rightly complains. curl sends 'Expect: 100-continue' by default.
        $request->setHeaders(array('Expect' => ''));
        
        $response = $request->execute();
        
        return $this->checkResponse('data for page ' . $this->parsedTitle . ' to quad store', $response);
    }
    
    /**
     * Logs in, retrieves (session cookie as well as) edit token
     */
    private function logIn()
    {
    	/* Log in */
        $request = new PostParamsRequest();
        
        $request->setUrl(LocalConfiguration::ultraUrl);
        
        $params = array();
        
        $params['action'] = 'login';
        $params['lgname'] = LocalConfiguration::ultraUser;
        $params['lgpassword'] = LocalConfiguration::ultraPassword;

        $this->log->debug('login params: ' . http_build_query(array_merge($params, array("lgpassword" => "********"))));
        
        $request->setParams($params);
        
        $this->cookieFile = $request->enableCookieJar(true, LocalConfiguration::tmpDir);
        
        $response = $request->execute();
        
        if (!$this->checkResponse('login sent to ultrapedia', $response)) {
        	return false;
        }
        
        /* Get an edit token */
        $params = array();
        
        $params['action'] = 'query';
        $params['prop'] = 'info';
        $params['intoken'] = 'edit';
        
        /*
         * MediaWiki requires us to specify at least one title when requesting an edit token, regardless
         * of whether the page exists or not.
         * We always provide the same static title "Foo" as there wouldn't be any applicable page in any case
         * when requesting an edit token for uploads.
         * 
         * @see http://www.mediawiki.org/wiki/API:Edit_-_Create%26Edit_pages
         * "This token is the same for all pages, but changes at every login"
         * @see http://www.mediawiki.org/wiki/API:Edit_-_Uploading_files
         * "To upload files, a token is required. This token is identical to the edit token [...]"
         */
        $params['titles'] = 'Foo2'; 
        $params['format'] = 'json';
        
        $this->log->debug('query params: ' . http_build_query($params));
        
        $request->setParams($params);
        
        /* Send back the session cookie we got in the last call */
        $request->setCookieFile($this->cookieFile);
		
        $response = $request->execute();
        
        if (!$this->checkResponse('query sent to ultrapedia', $response)) {
        	return false;
        }
        
        preg_match('/"edittoken":"([^"]+)"/', $response->getBody(), $matches);
        $this->editToken = stripslashes($matches[1]);
        return true;
    }

    /**
     */
    private function sendWiki()
    {
        $request = new PostParamsRequest();
        
        $request->setUrl(LocalConfiguration::ultraUrl);
        
        $params = array();
        
        $params['token'] = $this->editToken;
        $params['format'] = 'xml';
        // TODO: handle action 'delete'
        $params['action'] = 'edit';
        if ($this->action === 'create') $params['createonly'] = 'true';
        else if ($this->action === 'update') $params['nocreate'] = 'true';
        
        $params['title'] = $this->rawTitle;
        
        if ($this->revision !== null) $params['id'] = $this->revision;
        
        $this->log->debug('article update params: ' . http_build_query($params));
        
        $params['text'] = $this->rawPage;
        
        $request->setParams($params);
        
        if (isset($this->cookieFile)) {
            $request->setCookieFile($this->cookieFile);
        }
        
        $response = $request->execute();
        
        return $this->checkResponse('text for page ' . $this->parsedTitle . ' to ultrapedia', $response);
    }
    
    /**
     */
    private function sendOwl( $owl )
    {
        // WAAAAAAAAHHHHHHHHH!!!! The only way to create a proper multipart 
        // request with PHP seems to be to use an actual file.... I WANT JAVA!!!!! 
        // The filename has to be dbpedia.owl, so we need a new directory
        // for each process...
         
        $dir = LocalConfiguration::tmpDir . '/' . getmypid();
        $file = $dir . '/dbpedia.owl';
        mkdir($dir);
        file_put_contents($file, $owl);
        
        $request = new PostMultiRequest();
        
        $request->setUrl(LocalConfiguration::ultraUrl);
        
        $fields = array();
        $fields['token'] = $this->editToken;
        $fields['format'] = 'xml';
        $fields['action'] = 'upload';
        $fields['filename'] = 'dbpedia.owl';
        $fields['file'] = '@' . $file;
        
        $request->setFields($fields);
        
        if (isset($this->cookieFile)) {
            $request->setCookieFile($this->cookieFile);
        }

        $response = $request->execute();
        
        unlink($file);
        rmdir($dir);
        
        // FIXME: sometimes api.php sends 200 and no MediaWiki-API-Error, but an
        // error message in the XML. We should check that. For now, just log it...
        $this->log->debug($response->getBody());
        
        return $this->checkResponse('OWL file to ultrapedia', $response);
    }
    
    /**
     * @return call time in seconds, or false of call failed
     */
    private function checkResponse( $description, HttpResponse $response )
    {
        $error = false;
        $message = 'Failed to send ' . $description . '.';
        
        $curl = $response->getError();
        if ($curl !== '')
        {
            $message .= ' cURL error: ' . $curl . '.';
            $error = true;
        }
        
        $code = $response->getCode();
        if ($curl !== '' || $code < 200 || $code >= 300)
        {
            $message .= ' HTTP response code: ' . $code . '.';
            $error = true;
        }
        
        $headers = $response->getHeaders();
        if (isset($headers['MediaWiki-API-Error']))
        {
            $message .= ' MediaWiki-API-Error: ' . $headers['MediaWiki-API-Error'] . '.';
            $error = true;
        } 
        
        if ($error)
        {
            // TODO: do we really want the whole page content?
            if ($response->getBody()) $message .= "\r\n" . $response->getBody();
            return $this->response(500, 'Failed to send ' . $description, $message);
        }
        
        $this->log->info('Successfully sent ' . $description . ' (' . ($response->getTime() * 1000) . ' millis)');
        return true;
    }
    
    /**
     * @param $quads quads to wrap in a SPARUL INSERT statement
     */
    private function insert( $quads )
    {
        $insert = 'INSERT INTO <http://ultrapedia/wiki> {';
        $insert .= $quads;
        $insert .= '}';
        return $insert;
    }
    
    private function delete( $uriPrefix )
    {
        // Note: addslashes is close enough to http://www.w3.org/TR/rdf-sparql-query/#rECHAR
        $delete = 'DELETE FROM <http://ultrapedia/wiki> WHERE {';
        $delete .= '?s ?p ?o ?prov .';
        $delete .= 'FILTER regex(str(?prov), "' . addslashes(preg_quote($uriPrefix)) . '.*") ';
        $delete .= '}';
        
        //echo "Query: \n" . $delete . "\n";
        
        return $delete;
    }
    
    /**
     * @param $commands array of strings
     */
    private function sparul( $commands )
    {
        $sparul = '<sparul>';
        foreach ($commands as $command)
        {
            $sparul .= '<command>';
            $sparul .= htmlspecialchars($command, ENT_NOQUOTES);
            $sparul .= '</command>';
        }
        $sparul .= '</sparul>';
        
        return $sparul;
    }
    
    private function hasTemplate( $names )
    {
        foreach ($this->parsedPage->getChildren('TemplateNode') as $template)
        {
            if (in_array($template->getName(), $names, true)) return true;
        }
        
        return false;
    }
    
    private function processOntology()
    {
        $path = $this->ontologyPath();
        if ($path === false) return false;
        
        try
        {
            $name = OntologyReader::getName($path);
            // check that namespace is known
            OntologyNamespaces::getUri($name, 'http://example.com/');
        }
        catch (\Exception $e)
        {
            $this->response(400, 'Invalid ontology page title ' . $path);
            return true; // we're done, give up
        }
        
        // TODO: add the following two lines? or at least the first line?
        // if (! $this->parsePage()) return true; // we're done, give up
        // if (! $this->hasTemplate(self::$ontologyTemplates)) ignore data? send error response?
        
        // forward source to ultrapedia
        // Ontology pages don't exist @ Ultrapedia
        //if (! $this->sendWiki()) return true; // error response already sent
        
        try
        {
            $configHolder = $this->configHandler->updateOntology($path, $this->rawPage);
        }
        catch (\Exception $e)
        {
            $this->response(500, 'Internal server error', 'Failed to update ontology: ' . $e);
            return true; // give up
        }
        
        $writer = new OWLOntologyWriter();
        $owl = $writer->toOWL($configHolder->ontology);
        if (! $this->sendOwl($owl)) return true; // error response already sent
        
        return $this->response(200, 'Successfully updated ontology schema');
    }
    
    private function ontologyPath()
    {
        if ($this->parsedTitle->nsCode() !== LocalConfiguration::ontologyNsCode) return false;
        
        $path = $this->parsedTitle->encoded();
        $prefix = LocalConfiguration::ontologyPrefix;
        if (! StringUtil::startsWith($path, $prefix)) return false;
        
        return substr($path, strlen($prefix));
    }
    
    private function processTemplateMapping()
    {
        $path = $this->templateMappingPath();
        if ($path === false) return false;
        
        // TODO: add the following two lines? or at least the first line?
        // if (! $this->parsePage()) return true; // we're done, give up
        // if (! $this->hasTemplate(self::$templateMappingTemplates)) ignore data? send error response?
        
        // forward source to ultrapedia
        // Template mappings don't exist @ Ultrapedia
        //if (! $this->sendWiki()) return true; // error response already sent
        
        try
        {
            $this->configHandler->updateMapping($path, $this->rawPage);
        }
        catch (\Exception $e)
        {
            $this->response(500, 'Internal server error', 'Failed to update template mapping: ' . $e);
            return true; // give up
        }
            
        return $this->response(200, 'Successfully updated template mapping configuration');
    }
    
    private function templateMappingPath()
    {
        if ($this->parsedTitle->nsCode() !== LocalConfiguration::templateMappingNsCode) return false;
        
        $path = $this->parsedTitle->encoded();
        $suffix = LocalConfiguration::templateMappingSuffix;
        if (! StringUtil::endsWith($path, $suffix)) return false;
        
        return substr($path, 0, - strlen($suffix));
    }
    
    private function processTableMapping()
    {
        $path = $this->tableMappingPath();
        if ($path === false) return false;
        
        // TODO: add the following two lines? or at least the first line?
        // if (! $this->parsePage()) return true; // we're done, give up
        // if (! $this->hasTemplate(self::$tableMappingTemplates)) ignore data? send error response?
        
        // forward source to ultrapedia
        if (! $this->sendWiki()) return true; // error response already sent
        
        try
        {
            $this->configHandler->updateMapping($path, $this->rawPage);
        }
        catch (\Exception $e)
        {
            $this->response(500, 'Internal server error', 'Failed to update template mapping: ' . $e);
            return true; // give up
        }
            
        return $this->response(200, 'Successfully updated table mapping configuration');
    }
    
    private function tableMappingPath()
    {
        if ($this->parsedTitle->nsCode() !== LocalConfiguration::tableMappingNsCode) return false;
        if ($this->parsedTitle->encoded() !== LocalConfiguration::tableMappingName) return false;
        return LocalConfiguration::tableMappingPath;
    }
    
    private function processData()
    {
        if ($this->parsedTitle->nsCode() !== WikiTitle::NS_MAIN) return false;
        
        if (! $this->parsePage()) return true; // we're done, give up
        
        try
        {
            $configHolder = $this->configHandler->loadConfigFile();
        }
        catch (\Exception $e)
        {
            return $this->response(500, 'Internal server error', 'Failed to load config: ' . $e);
        }
        
	// forward source to ultrapedia
	if (! $this->sendWiki()) return true; // error response already sent

        $secs = microtime(true);
        
        $extractor = $configHolder->extractor;
        // destinations must be SingletonQuadDestinations, so key doesn't matter
        // destination must be a StringQuadDestination
        // TODO: check types 
        $destination = $configHolder->destinations->getDestination('');
        
        $pageUri = OntologyNamespaces::appendUri(OntologyNamespaces::DBPEDIA_INSTANCE_NAMESPACE, $this->parsedTitle->encoded());
        $pageContext = new mapping\PageContext($pageUri);
        $extractor->extract($this->parsedPage, $pageUri, $pageContext);
        
        $delete = $this->delete($this->parsedPage->getSourceUriPrefix());
        
        $insert = $this->insert((string)$destination);
        
        $sparul = $this->sparul(array($delete, $insert));
        
        $this->log->info('Successfully extracted data (' . (microtime(true) - $secs) * 1000 . ' millis)');
        
        // send triples to triple store
        if (! $this->sendSparul($sparul)) return true; // error response already sent
        
        return $this->response(200, 'Successfully extracted data', $this->reply ? "Successfully extracted data.\n\n" . $sparul : null);
    }
    
    private function processOther()
    {
        // forward source to ultrapedia
        if (! $this->sendWiki()) return; // error response already sent
        
        return $this->response(200, 'Successfully fowarded data');
    }
    
    private function parsePage()
    {
        $secs = microtime(true);
        $parser = new WikiParser();
        try
        {
            $this->parsedPage = $parser->parse($this->parsedTitle, $this->rawPage);
            $this->log->info('successfully parsed page [' . $this->parsedTitle . '] (' . (microtime(true) - $secs) * 1000 . ' millis)');
            return true;
        }
        catch (\Exception $e)
        {
            $this->log->warn('failed to parse page [' . $this->parsedTitle . '] (' . (microtime(true) - $secs) * 1000 . ' millis)');
            return $this->response(400, 'Invalid source text', 'Invalid source text - ' . $e->getMessage());
        }
    }
    
    public function process()
    {
        $this->action = @self::$actions[$_REQUEST['newarticle']];
        if ($this->action === null) return $this->response(400, 'Missing action');
        
        $this->rawTitle = @$_REQUEST['title'];
        if ($this->rawTitle === null) return $this->response(400, 'Missing page title');
        
        // TODO: source is not required when action is delete
        $this->rawPage = @$_REQUEST['source'];
        if ($this->rawPage === null) return $this->response(400, 'Missing page source');
        
        $this->revision = @$_REQUEST['revision'];
        // revision is optional
        
        $this->reply = @$_REQUEST['mode'] === 'reply';
        
        try
        {
            $this->parsedTitle = WikiTitle::parse($this->rawTitle);
        }
        catch (\Exception $e)
        {
            return $this->response(400, 'Invalid page title', 'Invalid page title - ' . $e->getMessage());
        }
        
        if (defined('dbpedia\LocalConfiguration::ultraUser') && defined('dbpedia\LocalConfiguration::ultraPassword')) {
        	$this->logIn();
        }

        $this->log->info('received update for page ' . $this->parsedTitle);
        
        $this->configHandler = new ConfigHandler(LocalConfiguration::ontologyDir, LocalConfiguration::mappingDir, LocalConfiguration::configFile);
        
        if ($this->processOntology()) return;
        
        if ($this->processTemplateMapping()) return;
        
        if ($this->processTableMapping()) return;
        
        if ($this->processData()) return;
        
        $this->processOther();
    }
    
    public function cleanUp()
    {
        if (isset($this->cookieFile)) {
			unlink($this->cookieFile);
        }
    }
}

}
