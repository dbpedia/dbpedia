<?php

/*
 * Template for a Wikipedia-ChemBox extractor. Not implemented yet.
 * 
 */ 

class ChemboxExtractor extends Extractor 
{	
	private $allPredicates;
   		
	/*
	 * Overrides default
	 * */
    public function start($language) {
        $this->language = $language;
		$this->allPredicates = new ExtractionResult("PredicateCollection", $this->language, $this->getExtractorID());
    }
    public function extractPage($pageID, $pageTitle, $pageSource) {
        
		//create a new Extraction Result to hold all extrated Triples
		$result = new ExtractionResult(
                $pageID, $this->language, $this->getExtractorID());
                
		
		//Look for {{chembox header}} in PageSource
		if (preg_match("/{{chembox header}}/", $pageSource, $match))
		{
		
		
		//DO SOME PARSING
		
		
		//Add a Triple for each Property
		$result->addTriple(
				$this->getPageURI(),
                RDFtriple::URI(DB_MY_CHEM_PROPERTY,false),
                RDFtriple::Literal("my_value"));   	
			
		
		//Add each Predicate to the Predicate Collection 
		$this->allPredicates->addPredicate("my_chem_property");
		
		
		}	
        return $result;
		
    }
	
	private function getPredicates() {
    	return $this->allPredicates->getPredicateTriples();
    }
	
    public function finish() { 
        return $this->getPredicates();
    }
	
	
	public function getLabelForLink($text2) {
		return str_replace("]]","",str_replace("[[","",preg_replace("/.*\|/", "[[", $text2[0]))) ;
	}
	
	public function getLinkForLabeledLink($text2) {
		return str_replace(" ","_",str_replace("]]","",str_replace("[[","",preg_replace("/\|.*/", "]]", $text2[0])))) ;
	}
	
    
}


