<?php

/**
 * Extracts Wikipedia Templates (Infoboxes). Needs all .php files in the subfolder /infobox.
 *
 *
 */
require_once('infobox/extractFunctions.php');
include('infobox/config.inc.php');
include('infobox/cleanUpFunctions.php');
include('infobox/testFunctions.php');
include('infobox/catchObjectDataTypeFunctions.php');

class InfoboxExtractor extends Extractor
{
	private $allPredicates;
	public $parsers = array();

    public function start($language) {
        $this->language = $language;
        $this->allPredicates = new ExtractionResult("PredicateCollection", $this->language, $this->getExtractorID());

        if ($this->language != "en") {
			$addParser = ucfirst($this->language)."ParseAttribute";
			if (file_exists("./extractors/infobox/".$addParser.".php")) {
				$this->parsers[] = new $addParser;
			}
		}
		$this->parsers[] = new ParseAttribute;
    }

    public function extractPage($pageID, $pageTitle, $pageSource) {
        $result = new ExtractionResult($pageID, $this->language, $this->getExtractorID());

        global $parseResult; // Contains the Extraction result
        $parseResult = null;

        $this->parsePage($pageID, $pageSource, $this->language);

		if ( count($parseResult) < 1 ) {
			return $result;
		}

		$knownProperties = array($parseResult[0][1]);


        foreach($parseResult as $myTriple)
        {
        	try {
				$subject = RDFtriple::URI($myTriple[0]);
			} catch (Exception $e) {
				echo 'Caught exception: ',  $e->getMessage(), "\n";
				continue;
			  }
        	// Rename Properties like LeaderName1, LeaderName2, ... to LeaderName
        	if ( preg_match("/(.*[^0-9_]+)([0-9])$/",$myTriple[1],$matches) ) {
        		// if property consist of letters from another writing system then latin, e.g. Korean,
        		// the words are decoded as e.g. _percent_B1, this must not be changed
        		// if language.use_percent_encoding = false, it looks like e.g. %B1
        		if ((substr(substr($myTriple[1], -11), 0, 9) != "_percent_") && !(ereg("%([A-F0-9]{2})", substr($myTriple[1], -3)))) {
            		$key = array_search($matches[1],$knownProperties);

            		if ( $key ) {
           				$myTriple[1] = $knownProperties[$key];
            		} else {
            			array_push( $knownProperties, $matches[1] );
            			$myTriple[1] = $matches[1];
            		}
        		}
        	} else if ( !array_search($myTriple[1],$knownProperties) ) {
        		array_push($knownProperties, $myTriple[1]);
        	}

			// if a property is longer than the maximum configured length, we do
			// do not write the triple
			if(strlen($myTriple[1])>$GLOBALS['W2RCFG']['maximumPropertyLength']) {
				continue;
			}

			try {
				$predicate = RDFtriple::URI($myTriple[1]);
			} catch (Exception $e) {
				echo 'Caught exception: ',  $e->getMessage(), "\n";
				continue;
			  }

        	if ($myTriple[3] == "r") {
				try {
					$object = RDFtriple::URI($myTriple[2]);
				} catch (Exception $e) {
					echo 'Caught exception: ',  $e->getMessage(), "\n";
					continue;
				  }
			}

        	else {
        		if ( $myTriple[5] == null ) $myTriple[5] = $this->language;
        		$object = RDFtriple::literal($myTriple[2], $myTriple[4], $myTriple[5]);
        	}

			//this is for the db:London/rating
			//subtemplate problem
			$triple = new RDFtriple($subject, $predicate, $object);
			$currentSubject = RDFtriple::page($pageID);
			$small = $currentSubject->getURI();
			$big = $subject->getURI();

			if(strpos($big,$small) === 0 && strlen($big)>strlen($small)){
					$triple->addOnDeleteCascadeAnnotation($currentSubject);
				}
			$result->addTripleObject($triple);
			$this->allPredicates->addPredicate($myTriple[1]);
        }
        return $result;
    }

    private function parsePage($page,$text,$language=NULL) {
		include('infobox/parsePage.php');
    }

    private function parseTemplate($subject,$template,$language=NULL) {
		include('infobox/parseTemplate.php');
		if(isset($extracted))
			return $extracted;
		else
			return false;
    }

	/**
	 * Main function to extract data-types, internal Links etc. from the Template.
	 *
	 */
    public function parseAttributeValue($object,$subject,$predicate) {
		foreach ($this->parsers as $parser) {
			$result = $parser->parseValue($object,$subject,$predicate,$this,$this->language);
			if ($result != null) {
				return $result;
			}
		}
	}

    private function getPredicates() {
    	return $this->allPredicates->getPredicateTriples();
    }

    public function finish() {
        return $this->getPredicates();
    }


	function encode_title($s, $namespace = null) {
        $result = urlencode(str_replace(' ', '_', $s));
        if ($namespace) {
            $result = $namespace . ":" . $result;
        }
        return $result;
    }

    function decode_title($s) {
		if (is_null($s)) return null;
        return preg_replace("/^.*:/", "", str_replace('_', ' ', $s));
    }


}


