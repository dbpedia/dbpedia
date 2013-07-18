<?php

define("PATH", 'extractors/LiveMappingBasedExtractorFiles/');

function require_once_recursive($path) {
    $objects =
        new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path),
            RecursiveIteratorIterator::SELF_FIRST);

    foreach($objects as $name => $object) {        
        if(strpos($name, "svn"))
            continue;

        if(strpos($name, "IBreadCrumbTransformer.php") ||
                strpos($name, "ITripleGenerator.php") ||
                strpos($name, "IValueParser.php") ||
                strpos($name, "IFilter.php"))
        continue;

        if(strpos($name, ".php")) {
            //echo "require_once($name)\n";
            require_once($name);
        }
        //else
            //echo "Rejected: $name\n";
    }
}

require_once(PATH."IBreadCrumbTransformer.php");
require_once(PATH."TripleGenerator/ITripleGenerator.php");
require_once(PATH."ValueParser/IValueParser.php");
require_once(PATH."Filter/IFilter.php");
require_once_recursive(PATH);


// This extractor requires the template-database
//require_once(PATH."templateDb/helpers.php");
//require_once(PATH."templateDb/TemplateDb.php");
require_once("extractors/infobox/extractFunctions.php");


/**
 * Puts a value into a Map<TKey, List<TValue>>
 *
 *
 * So you can do:
 * $x = array();
 * putMultiMap($x, 1, 1);
 * putMultiMap($x, 1, 2);
 *
 * Not type safe
 *
 * No idea where to put that little helper - or maybe there is already a
 * built in php function for this?
 */
function putMultiMap(&$map, $key, $value)
{
    if(!array_key_exists($key, $map))
        $map[$key] = array();

    $map[$key][] = $value;
}



/**
 * The MappingBasedExtractor extracts infoboxes/templates from Wikipedia articles.
 * It matches the template properties to the DBpedia ontology ones.
 *
 * @author: Georgi Kobilarov (FU Berlin)
 */
class LiveMappingBasedExtractor
    extends Extractor
{
    // Object for accessing the template database
    //private $templateDb;

    //private $parseHintToTripleGenerator;

    // The triple generator in case no parse hint is found
    private $defaultTripleGenerator;

    // Filters templates by their name
    // (servers the purpose of the isIgnoredTemplate method)
    //private $templateNameFilter;

    private $rootTripleGenerator;

    private function getTripleGenerator($parseHint)
    {
        if(!array_key_exists($parseHint, $this->parseHintToTripleGenerator))
            return $this->defaultTripleGenerator;

        return $this->parseHintToTripleGenerator[$parseHint];
    }

    // This map is build on start (because of the language parameter)
    private static function createParseHintToTripleGeneratorMap($language)
    {
        return array(
            // Units
            "km"       => new UnitTripleGenerator($language, "km"),
            "m3/s"     => new UnitTripleGenerator($language, "m3/s"),
            "cuft/s"   => new UnitTripleGenerator($language, "cuft/s"),
            "sqkm"     => new UnitTripleGenerator($language, "km2"),
            "sqmi"     => new UnitTripleGenerator($language, "sqmi"),
            "K"        => new UnitTripleGenerator($language, "K"),
            "C"        => new UnitTripleGenerator($language, "C"),
            "F"        => new UnitTripleGenerator($language, "F"),
            "ft"       => new UnitTripleGenerator($language, "ft"),
            "in"       => new UnitTripleGenerator($language, "in"),
            "pop/sqkm" => new UnitTripleGenerator($language, "PD/sqkm"),
            "pop/sqmi" => new UnitTripleGenerator($language, "PD/sqmi"),
            "m"        => new UnitTripleGenerator($language, "m"),
            "mi"       => new UnitTripleGenerator($language, "mi"),
            "lb"       => new UnitTripleGenerator($language, "lb"),
            "kg"       => new UnitTripleGenerator($language, "kg"),
            "st"       => new UnitTripleGenerator($language, "st"),
            "min"      => new UnitTripleGenerator($language, "min"),

            // Currencies
            //"USD"      => new CurrencyTripleGenerator($language),
            //"$"        => new CurrencyTripleGenerator($language),

            // Simple int and float
            "date"     => new DateTimeTripleGenerator($language),

            "int"      => new IntegerTripleGenerator($language),
            "float"    => new FloatTripleGenerator($language),

            "currency" => new CurrencyTripleGenerator($language),
            "text"     => new TextTripleGenerator($language),
            "links"    => new SmartLinkTripleGenerator(DB_RESOURCE_NS),

            // Temporary hack, the place parse hint should be more intelligent
            "place"    => new SmartLinkTripleGenerator(DB_RESOURCE_NS)
        );
    }


    public function start($language)
    {
        $this->language = $language;

        $this->parseHintToTripleGenerator =
            self::createParseHintToTripleGeneratorMap($language);

        //$this->defaultTripleGenerator = new DefaultTripleGenerator($language);

        // Initialize database connection
		if(false == Options::getOption('LiveMappingBased.useTemplateDb'))
		{
        	$this->templateDb = new DummyTemplateDb();
		}
		else {
        	$odbc = ODBC::getDefaultConnection();

			//$odbc = new ODBC("VOS", "dbpedia", "dbpedia");
        	
			$this->templateDb = new TemplateDb($odbc);			
		}

        // Initialize the template name filter
        $this->templateNameFilter =
            new AndCompoundFilter(array(
                    new LegacyTemplateFilter(),
                    new TemplateFilter()));

        $mediaWikiUtil = MediaWikiUtil::getInstance("http://en.wikipedia.org/wiki/");

        $this->rootTripleGenerator =
            new RootTripleGenerator(
                $this->language,
                $this->templateNameFilter,
                $this->templateDb,
                $this->parseHintToTripleGenerator,
                $mediaWikiUtil);

    }


    public function finish()
    {
    }


    public function extractPage($pageId, $pageTitle, $pageSource)
    {
    	$this->setPageURI($pageTitle);
    	
        // Set up the result object
        $result = new ExtractionResult(
			$pageId,
			$this->language,
			$this->getExtractorID());

        // Return empty result if there is no title
        if($this->decode_title($pageTitle) == NULL)
            return $result;

        try {
            $breadCrumb = new Breadcrumb($pageId);

            //echo "\n\n\n\n\nPAGE = $pageId --- $pageTitle\n\n";
            
            $triples =
                $this->rootTripleGenerator->generate($breadCrumb, $pageSource);

            //print_r($triples);
                
            // Add annotation to all triples
			
            //$rootSubject = $this->getPageURI();//RDFTriple::page($pageId);
            //$rootSubject = RDFTriple::page($pageId);
			//$this->log(DEBUG, "diff root to getPageURI: $rootSubject | {$this->getPageURI()}");
            $this->log(TRACE, "generated triples:");
			$logmsg = "";
			foreach($triples as $triple) {
                $triple->addOnDeleteCascadeAnnotation($this->getPageURI());
                $result->addTripleObject($triple);
				$logmsg .= $triple->toNTriples();
            }
			$this->log(TRACE, "\n".$logmsg);
        } catch(Exception $e) {
            $this->log(WARN, "Caught exception: ".$e->getMessage());
        }

        
        //$this->log('info','LiveMappingBasedExtractor: Count of generated triples ' + sizeof($result));
        
        
        return $result;
    }




	function encode_title($s, $namespace = null) {
		$result = urlencode(str_replace(' ', '_', $s));
		if ($namespace) {
			$result = $namespace . ":" . $result;
		}
		return $result;
	}

	function decode_title($s) {
		if (!isset($s)) return null;
		$label = preg_replace("/^(Category|Template):/", "", str_replace('_', ' ', $s));
		// take care of "(" ")" "&"
		$label = str_replace('%28','(',$label);
		$label = str_replace('%29',')',$label);
		$label = str_replace('%26','&',$label);
		return $label;
	}

	public function getLinkForLabeledLink($text2) {
		return preg_replace("/\|.*/", "", $text2) ;
	}

	// Helpfunction for preg_replace_callback, to replace "|" with #### inside subtemplates
	public static function replaceBarInSubTemplate($stringArray) {
		return str_replace("|","####",$stringArray[0]);
	}


	function encodeLocalName($string) {
		//$string = strtolower(trim($string));
		//  return urlencode(str_replace(" ","_",trim($string)));
		$string = urlencode(str_replace(" ","_",trim($string)));
		// Decode slash "/", colon ":", as wikimedia does not encode these
		$string = str_replace("%2F","/",$string);
		$string = str_replace("%3A",":",$string);

		return $string;
	}

  
    /*
    private function processExternalProperty($pageId, $uri, $key, $value, $result)
    {
        if($key == "homepage") {
            try {
                $result->addTriple(
                    RDFtriple::page($pageId),
                    RDFtriple::URI($uri),
                    RDFtriple::URI($value));
            } catch(Exception $e) {
                //TODO uncorrect URI
            }
        }
        else if($key == "name" &&
            strpos($value, "{{PAGENAME}}") === false &&
            strpos($value, "{{") === false) {

            $names = StringParser::parseValue($value, $this->language, null);

            foreach($names as $name) {
                if($names == "")
                    continue;

                $result->addTriple(
                    RDFtriple::page($pageId),
                    RDFtriple::URI("http://xmlns.com/foaf/0.1/name"),
                    RDFtriple::Literal($name));
            }
        }
    }
    */
}


