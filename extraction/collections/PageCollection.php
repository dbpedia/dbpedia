<?php
/**
 * Defines the interface PageCollection.
 * PageCollections are data sources (e.g. online Wikipedia)
 * for DBpedia data extraction
 * 
 */
interface PageCollection {

    public function getLanguage();
    public function getSource($pageTitle);
}

