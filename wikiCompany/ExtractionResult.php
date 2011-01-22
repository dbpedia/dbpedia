<?php

/**
 * Extraction Results collect the triples from a page while extraction is in process.
 * 
 * Triples are stored in the array $triples.
 * Optional metainformation, such as all predicates used can be stored in the array
 * $metadataTriples.
 * 
 * 
 */

class ExtractionResult {
    private $pageID;
    private $language;
    private $extractorID;
    private $triples = array();
    private $metadataTriples = array();
    private $predicates = array();
    

    /* @param pageID The page from which this result originated, or null if
            from no specific page */
    public function __construct($pageID, $language, $extractorID) {
        $this->pageID = $pageID;
        $this->language = $language;
        $this->extractorID = $extractorID;
    }
    /* @result The page from which this result originated, or null if
            from no specific page */
    public function getPageID() {
        return $this->pageID;
    }
    public function getLanguage() {
        return $this->language;
    }
    public function getExtractorID() {
        return $this->extractorID;
    }
    public function getTriples() {
        return $this->triples;
    }
    public function getMetadataTriples() {
        return $this->metadataTriples;
    }
    public function getPredicateTriples() {
        $predicateTriples = new ExtractionResult($this->pageID, $this->language, $this->extractorID);
        foreach ( $this->predicates as $subject => $bool ) {
        	// array_push( $predicateTriples, new RDFtriple($subject, "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", "http://www.w3.org/1999/02/22-rdf-syntax-ns#Property"));
        	$predicateTriples->addTriple(RDFtriple::URI($subject), RDFtriple::URI("http://www.w3.org/1999/02/22-rdf-syntax-ns#type"), RDFtriple::URI("http://www.w3.org/1999/02/22-rdf-syntax-ns#Property") );
			$predicateTriples->addTriple(RDFtriple::URI($subject), RDFtriple::URI("http://www.w3.org/2000/01/rdf-schema#label"), RDFtriple::Literal($this->getPredicateLabel($subject)) );	        
        }
        return $predicateTriples;
    }
    public function addTriple($s, $p, $o) {
        $this->triples[] = new RDFtriple($s, $p, $o);
    }
    public function addMetadataTriple($s, $p, $o) {
        $this->metadataTriples = new RDFtriple($s, $p, $o);
    }
    public function addPredicate($p) {
        $this->predicates[$p] = true;
    }
    
    private function getPredicateLabel($p) {
    	$p = str_replace("http://dbpedia.org/property/","",$p);
    	return urldecode( strtolower( preg_replace("/[A-Z]/",' \\0',$p)) );
    	
    }
  
}


