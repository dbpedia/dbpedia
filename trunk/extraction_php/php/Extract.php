<?php
namespace dbpedia
{
use dbpedia\sources\FileSource;
use dbpedia\sources\XMLSource;
use dbpedia\destinations\FileQuadDestination;
use dbpedia\destinations\SingletonQuadDestinations;
use dbpedia\ontology\OntologyReader;
use dbpedia\mapping\ExtractionContext;
use dbpedia\mapping\ExtractionManager;
use dbpedia\mapping\MappingExtractor;
use dbpedia\mapping\LabelExtractor;
use dbpedia\mapping\AbstractExtractor;
use dbpedia\mapping\GeoExtractor;
use dbpedia\ontology\OntologyNamespaces;
use dbpedia\wikiparser\WikiTitle;

include('DBpedia.php');
error_reporting(E_ALL | E_STRICT);

$allowCaching = $argc > 1 && $argv[1] == "--cache";

$extract = new Extract($allowCaching);
$extract->start();

/**
 * Does a extraction based on source files on the hard disk and dumps the results in a file.
 */
class Extract
{
    /** The directory where the ontology files can be found */
    const ONTOLOGY_DIR = "../resources/ontologyschema";
    /** The directory where the mappings can be found */
    const MAPPINGS_DIR = "../resources/mappings";
    /** The directory where the source pages can be found */
    const SOURCE_DIR = "../resources/source";

    const OUTPUT_FILE = "../resources/output_php.nq";

    const ONTOLOGY_FILE = "../resources/ontology.cache";
    const MAPPINGS_FILE = "../resources/mappings.cache";    
    const REDIRECTS_FILE = "../resources/redirects";

    private $logger;

    private $ontologySource;
    private $mappingsSource;
    private $pageSource;
    private $ontology;
    private $extractor;
    private $redirects;
    private $context;
    private $allowCaching;

    /**
     * Constructor
     *
     * @param $allowCaching If true, the configuration is loaded from a cache.
     */
    public function __construct($allowCaching = false)
    {
        $this->logger = \dbpedia\core\DBpediaLogger::getLogger(__CLASS__);
        $this->allowCaching = $allowCaching;
        if ($this->allowCaching)
        {
            $this->logger->info('Using cached ontology and mappings');
        }
        else
        {
            $this->logger->info('Start with --cache to use cached ontology and mappings');
        }
    }

    /**
     * Main method, which loads the configuration and maps all source files.
     */
    public function start()
    {
        $startTime = microtime(true);

        $this->context = new ExtractionContext();

        //Create sources
        $this->ontologySource = new FileSource(self::ONTOLOGY_DIR, array('.svn'));
        $this->mappingsSource = new FileSource(self::MAPPINGS_DIR, array('.svn'));
        $this->pageSource = new FileSource(self::SOURCE_DIR, array('.svn'));
        $this->templatesSource = new FileSource(self::SOURCE_DIR, array(WikiTitle::NS_TEMPLATE));
        //$this->pageSource = new XMLSource("D:\enwiki-latest-pages-articles.xml");
        //$this->templatesSource = new XMLSource("D:\enwiki-latest-pages-articles.xml");

        //Create destinations
        $destination = new FileQuadDestination(self::OUTPUT_FILE);
        $destinations = new SingletonQuadDestinations($destination);
        $this->context->setDestinations($destinations);

        //Load redirects
        $this->redirects = $this->loadRedirects();
        $this->context->setRedirects($this->redirects);

        //Load ontology and mappings
        $this->loadOntology();
        $this->loadMappings();

        //Add extractors
        $this->extractor->addExtractor(LabelExtractor::load($this->ontology, $this->context));
        $this->extractor->addExtractor(GeoExtractor::load($this->ontology, $this->context));
        $this->extractor->addExtractor(AbstractExtractor::load($this->ontology, $this->context));

        //Extract
        $destination->open();
        $this->extract();
        $destination->close();

        $stopTime = microtime(true);
        echo "Total time: " . ($stopTime - $startTime);
    }

    /**
     * Loads the ontology
     */
    private function loadOntology()
    {
        if ($this->allowCaching && file_exists(self::ONTOLOGY_FILE))
        {
            $this->logger->info('Loading ontology from file.');
            $this->ontology = unserialize(file_get_contents(self::ONTOLOGY_FILE));
        }
        else
        {
            //Parse ontology schema pages
            $this->logger->info('Reading ontology...');
            $ontologyPages = $this->loadAllPages($this->ontologySource);

            //Load ontology
            $ontologyReader = new OntologyReader();
            $this->ontology = $ontologyReader->read($ontologyPages);

            LabelExtractor::addProperties($this->ontology);
            AbstractExtractor::addProperties($this->ontology);

            $this->destroyAllPages($ontologyPages);

            file_put_contents(self::ONTOLOGY_FILE, serialize($this->ontology));

            //Show the extracted ontology
            $writer = new ontology\OWLOntologyWriter();
            echo $writer->toOWL($this->ontology);
        }
    }

    /**
     * Loads the mappings
     */
    private function loadMappings()
    {
        $this->extractor = new ExtractionManager();

        //Parse mapping pages
        if ($this->allowCaching && file_exists(self::MAPPINGS_FILE))
        {
            $this->logger->info('Loading mappings from file.');
            $mappingExtractor = unserialize(file_get_contents(self::MAPPINGS_FILE));
        
            if ($mappingExtractor && $mappingExtractor->getContext() != $this->context)
            {
            	$this->logger->info('Discarding cached mappings as context differs');
            	unset($mappingExtractor);
            }
            else
            {
            	/* Hack: Re-open the quad destination file and assume the context from the extractor */
            	$mappingExtractor->getContext()->getDestinations()->getDestination('destination id')->open();
            	$this->context = $mappingExtractor->getContext();
            }
        }
        
        if (!isset($mappingExtractor))
        {
	        $this->logger->info('Reading mapping definitions...');
	        $mappingPages = $this->loadAllPages($this->mappingsSource);
	        $this->logger->info('Building mapping definitions...');
	        $mappingExtractor = MappingExtractor::load($mappingPages, $this->ontology, $this->context);
	        $this->destroyAllPages($mappingPages);
	        file_put_contents(self::MAPPINGS_FILE, serialize($mappingExtractor));
        }

        $this->extractor->addExtractor($mappingExtractor);
        
        //echo $this->extractor;
    }

    /**
     * Loads the redirects
     */
    private function loadRedirects()
    {
        if(file_exists(self::REDIRECTS_FILE))
        {
            $this->logger->info('Loading redirects from file.');

            return unserialize(file_get_contents(self::REDIRECTS_FILE));
        }
        else
        {
            $this->logger->info('Loading redirects...');

            $redirects = new Redirect();
            $pageCount = 0;
            $startTime = microtime(true);
            $logger = $this->logger;

            $callback = function($title, $source) use ($redirects, &$pageCount, $startTime, $logger)
            {
                $redirects->addRedirect($title, $source);
                $pageCount++;
                if($pageCount % 1000 == 0)
                {
                    $timePerPage = round((microtime(true) - $startTime) * 1000.0 / $pageCount, 2);
                    $logger->info('Extracted pages: '.$pageCount.' ('.$timePerPage.' ms per page)');
                }
            };

            $this->templatesSource->processSources($callback);
            
            file_put_contents(self::REDIRECTS_FILE, serialize($redirects));

            return $redirects;
        }
    }

    /**
     * Extracts all pages in the source directory according to the mappings
     */
    private function extract()
    {
        $this->logger->info('Extracting...');

        $extractor = $this->extractor;
        $pageCount = 0;
        $startTime = microtime(true);
        $logger = $this->logger;

        $callback =
        function($page) use($extractor, &$pageCount, $startTime, $logger)
        {
            $pageUri = OntologyNamespaces::getUri($page->getTitle()->encoded(), OntologyNamespaces::DBPEDIA_INSTANCE_NAMESPACE);
            $pageContext = new mapping\PageContext($pageUri);
            $extractor->extract($page, $pageUri, $pageContext);
            $pageCount++;
            if($pageCount % 10 == 0)
            {
                $timePerPage = round((microtime(true) - $startTime) * 1000.0 / $pageCount, 0);
                $logger->info('Extracted pages: '.$pageCount.' ('.$timePerPage.' ms per page)');
            }
        };

        $this->pageSource->processPages($callback);

        $this->logger->info('Extraction finished ('.$pageCount.' pages)');
    }

    /**
     * Utility function, which parses all pages from a source
     *
     * @param The source which contains the pages to be parsed
     * @return Array of PageNode instances
     */
    private function loadAllPages($source)
    {
        $pageNodes = array();

        $callback = 
        function ($page) use (&$pageNodes)
        {
            $pageNodes[] = $page;
        };
        
        $source->processPages($callback);
        
        return $pageNodes;
    }

    /**
     * Utility function, which call destroy on every page inside a given array.
     * This is necessary because the PHP garbage collector cannot cope with circular references.
     */
    private function destroyAllPages( $pages )
    {
        foreach ($pages as $page)
        {
            $page->destroy();
        }
    }
}

}
