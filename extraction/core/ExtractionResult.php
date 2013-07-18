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
        	$predicateTriples->addTriple(RDFtriple::URI($subject), RDFtriple::URI(RDF_TYPE,false), RDFtriple::URI(RDF_PROPERTY, false) );
			$predicateTriples->addTriple(RDFtriple::URI($subject), RDFtriple::URI(RDFS_LABEL, false), RDFtriple::Literal($this->getPredicateLabel($subject)) );	        
        }
        return $predicateTriples;
    }
    
	public function addTripleObject(RDFtriple $triple) {
		$this->_addToTripleArray( $triple);
    }
	
	public function addTriple($s, $p, $o) {
		$this->_addToTripleArray(new RDFtriple($s, $p, $o));
    }
	
	private function _addToTripleArray(RDFtriple $triple){
		if($triple->validate()){
				 $this->triples[] = $triple;
			}
	}
	
    public function addMetadataTriple($s, $p, $o) {
        $this->metadataTriples = new RDFtriple($s, $p, $o);
    }
    public function addPredicate($p) {
        $this->predicates[$p] = true;
    }
    
    private function getPredicateLabel($p) {
    	$p = str_replace(DB_PROPERTY_NS,"",$p);
    	return urldecode( strtolower( preg_replace("/[A-Z]/",' \\0',$p)) );
    	
    }

	public function clear() {
		$triples = array();
		$metadataTriples = array();
		$predicates = array();		
	}
	
	
  
}


