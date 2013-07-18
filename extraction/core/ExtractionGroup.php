<?php

/**
 * An ExtractionGroup connects one Destination with one or more Extractors
 * 
 */

class ExtractionGroup {
    private $destination;
	private $metadestination;
    private $extractors = array();
    public function __construct($destination, $metadestination = NULL) {
        $this->destination = $destination;
		$this->metadestination = $metadestination;
    }
   
    public function addExtractor($extractor) {
		if($this->destination instanceOf LiveUpdateDestination){
			if($extractor->getStatus()==KEEP){
				//$arr = $extractor->getSPARQLFilter();
				$arr = $extractor->getMetadataProduces();
				$this->destination->addFilter($arr);
			}else if($extractor->getStatus()==ACTIVE){
				$this->destination->addActiveExtractor($extractor->getExtractorID());
			}else if($extractor->getStatus()==PURGE){
				$this->destination->addPurgeExtractor($extractor->getExtractorID());
			}else{
				die('invalid Extractor state, ExtractionGroup.php');
				
				}
		}
		
        $this->extractors[] = new ExtractorContainer($extractor);
    }
    public function getDestination() {
        return $this->destination;
    }
	public function getMetaDestination() {
        return $this->metadestination;
    }
    public function getExtractors() {
        return $this->extractors;
    }
/*
	 public function getExtractorInContainer() {
		 $container = array();
		 foreach($this->extractors as $one){
			 $container[] = new ExtractionContainer($one);
			 }
        return $container;
    }
*/
}

