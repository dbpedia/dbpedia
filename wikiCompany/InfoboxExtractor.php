<?php

/**
 * Extracts Wikipedia Templates (Infoboxes). Needs all .php files in the subfolder /extraction.
 * 
 * 
 */

class InfoboxExtractor implements Extractor 
{
	const extractorID = "http://dbpedia.org/extractors/InfoboxExtractor";
    private $language;
    private $allPredicates;
    public function getExtractorID() {
        return self::extractorID;
    }
    public function start($language) {
        $this->language = $language;
        $this->allPredicates = new ExtractionResult("PredicateCollection", $this->language, self::extractorID);
    }
    public function extractPage($pageID, $pageTitle, $pageSource) {
        global $pagetitle; // Needed for Imageextraction in catchObjectDatatype.php (catchLogo());
        $pagetitle = $pageTitle;
        $result = new ExtractionResult(
                $pageID, $this->language, self::extractorID);
                
                global $parseResult; // Contains the Extraction result
                $parseResult = null;
                	
                parsePage($pageID, $pageSource);
				
				if ( count($parseResult) < 1 )
					return $result;
				
				$knownProperties = array($parseResult[0][1]);
        		
                
                foreach($parseResult as $myTriple)
                {
                	$subject = RDFtriple::URI($myTriple[0]);
                	
                	// Rename Properties like LeaderName1, LeaderName2, ... to LeaderName
                	if ( preg_match("/(.*[^0-9_]+)([0-9])$/",$myTriple[1],$matches) ) {
                		$key = array_search($matches[1],$knownProperties);
                		if ( $key )
                			$myTriple[1] = $knownProperties[$key];
                		else 
                			array_push( $knownProperties, $matches[1] );
                			$myTriple[1] = $matches[1];
                	} else if ( !array_search($myTriple[1],$knownProperties) ) {
                		array_push($knownProperties, $myTriple[1]);
                	}
                	
                	$predicate = RDFtriple::URI($myTriple[1]);
                	
                	if ($myTriple[3] == "r")
                		$object = RDFtriple::URI($myTriple[2]);
                	else { 
                		if ( $myTriple[5] == null ) $myTriple[5] = $this->language;
                		$object = RDFtriple::literal($myTriple[2], $myTriple[4], $myTriple[5]);               	
                	}
        			$result->addTriple($subject, $predicate, $object);
        			$this->allPredicates->addPredicate($myTriple[1]);
                }
		
        return $result;
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


