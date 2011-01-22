<?php


class PageLinksExtractor extends Extractor 
{
	private $allPredicates;
	
    public function start($language) {
        $this->language = $language;
		$this->allPredicates = new ExtractionResult("PredicateCollection", $this->language, $this->getExtractorID());
    }
    
    public function extractPage($pageID, $pageTitle, $pageSource) {
        $result = new ExtractionResult($pageID, $this->language, $this->getExtractorID());
                
    	$pagelinks = $this->extract_internal_links($pageSource, $this->language);
    	//var_dump($pagelinks);
    	$pagelinks = array_unique($pagelinks);
    
    	 foreach($pagelinks as $LinkURI)
		 {	 
			 $object=DB_RESOURCE_NS. ucfirst(URI::wikipediaEncode($LinkURI));
			 try {
				$object = RDFtriple::URI($object);
			 } catch (Exception $e) {
				$this->log('warn', 'Caught exception: ',  $e->getMessage(), "\n");
				continue;						
			 }
			 
			 $result->addTriple(
			 $this->getPageURI(),
			 RDFtriple::URI(DB_WIKILINK,false),
             $object);
		 }

        return $result;
    }
	
	private function getPredicates() {
	   $this->allPredicates->addPredicate(RDFtriple::URI(DB_REFERENCE,false));
    	return $this->allPredicates->getPredicateTriples();
    }
	
	 function extract_internal_links($text, $Language) {
		
        $result = array();
        $set = array();
		
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


