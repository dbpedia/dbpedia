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
        $this->extractors[] = $extractor;
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
}

