<?php

/**
 * Destination for Unit Test.
 * Stores all Results in an array, whre the Page ID is the key 
 * and the NTriples are the value.
 * 
 */

class TestDestination implements Destination {
    
    private $displayOutput;
    private $pageTitles = array();
    
    public function __construct($displayOutput = false) {
    	$this->displayOutput = $displayOutput;
    }
    
    public function start() {
    		
    }
    public function accept($extractionResult) {
        $pageID = $extractionResult->getPageID();
        
        if ($this->displayOutput)
        		echo "\n\n" . $extractionResult->getPageID() . "\n";   
        
        foreach (new ArrayObject($extractionResult->getTriples()) as $triple) {
        	if ( !isset($this->pageTitles[$pageID]) )
        		$this->pageTitles[$pageID] = $triple->toString();
        	else
        		$this->pageTitles[$pageID] .= $triple->toString();   
        	// If set in the Constructor, write Output	
        	if ($this->displayOutput)
        		echo $triple->toString();   	
        }
    }
  
    public function finish() {
    	// var_dump($this->pageTitles);
    	return null;    	
    }
    
    public function getExtractionResult($pageID) {
    	if ( isset($this->pageTitles[$pageID]) )
    		return $this->pageTitles[$pageID];
    	return null;
    }
    
    
}

