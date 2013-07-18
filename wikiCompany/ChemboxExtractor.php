<?php

/*
 * Template for a Wikipedia-ChemBox extractor. Not implemented yet.
 * 
 */ 

class ChemboxExtractor implements Extractor 
{
	const extractorID = "http://dbpedia.org/extractors/ChemboxExtractor";
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
        
		//create a new Extraction Result to hold all extrated Triples
		$result = new ExtractionResult(
                $pageID, $this->language, self::extractorID);
                
		
		//Look for {{chembox header}} in PageSource
		if (preg_match("/{{chembox header}}/", $pageSource, $match))
		{
		
		
		//DO SOME PARSING
		
		
		//Add a Triple for each Property
		$result->addTriple(
                RDFtriple::page($pageID), 
                RDFtriple::predicate("my_chem_property"),
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


