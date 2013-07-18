<?php


class PageLinksExtractor implements Extractor 
{
	const extractorID = "http://dbpedia.org/extractors/PageLinksExtractor";
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
        $result = new ExtractionResult(
                $pageID, $this->language, self::extractorID);
                
				
				$pagelinks = $this->extract_internal_links($pageSource, $this->language);
				//var_dump($pagelinks);

				 foreach($pagelinks as $LinkURI)
					 {
						
						 $result->addTriple(
		                 RDFTriple::page($pageID), 
		                 RDFTriple::predicate("wikilink"),
		                 RDFTriple::page($LinkURI));
					 }

        return $result;
    }
	
	private function getPredicates() {
	   $this->allPredicates->addPredicate(RDFTriple::predicate("reference")->getURI());
    	return $this->allPredicates->getPredicateTriples();
    }
    public function finish() { 
       return $this->getPredicates();
    }
	
	 function extract_internal_links($text, $Language) {
		
        $result = array();
        $set = array();
		
        // Remove internal links, this makes matching easier
        
        preg_match_all("/\[\[([^:\]]*)\]\]/", $text, $matches, PREG_SET_ORDER);
		
		//var_dump($matches);
        foreach ($matches as $match) {
            if (strlen($match[1]) > 255) {
                continue;
             }
			
			$result[] = $this->getLinkForLabeledLink($match[1]);
        }
        return $result;
    }
	
	function extlink_replacement_text($match) {
        if (!isset($match[2])) {
            return $match[1];
        }
        return trim($match[2]);
    }
	
		public function getLinkForLabeledLink($text2) {
		return preg_replace("/\|.*/", "", $text2) ;
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


