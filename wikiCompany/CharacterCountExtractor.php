<?php

/*
 * Counts the characters in a Wikipedia articles
 */

class CharacterCountExtractor implements Extractor {
    const extractorID = "http://dbpedia.org/extractors/CharacterCountExtractor";
    private $language;
    public function getExtractorID() {
        return self::extractorID;
    }
    public function start($language) {
        $this->language = $language;
    }
    public function extractPage($pageID, $pageTitle, $pageSource) {
        $result = new ExtractionResult(
                $pageID, $this->language, self::extractorID);
        $result->addTriple(
                RDFtriple::page($pageID), 
                RDFtriple::predicate("characterCount"),
                RDFtriple::literal(strlen($pageSource)));
        return $result;
    }
    public function finish() { 
        return null;
    }
}


