<?php

/**
 * An ExtractionJob connects a pageCollection (data source) with 
 * ExtractionGroups (Extractors + Destinations)
 * 
 */

class ExtractionJob {
    private $pageCollection;
    private $pageTitleIterator;
    private $extractionGroups = array();
   
    public function __construct($pageCollection, $pageTitleIterator) {
        $this->pageCollection = $pageCollection;
        $this->pageTitleIterator = $pageTitleIterator;
    }
    public function addExtractionGroup($group) {
        $this->extractionGroups[] = $group;
    }
    public function getPageCollection() {
        return $this->pageCollection;
    }
    public function getPageTitleIterator() {
        return $this->pageTitleIterator;
    }
    public function getExtractionGroups() {
        return $this->extractionGroups;
    }
}


