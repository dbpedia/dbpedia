<?php

/**
 * Extracts the Pagelabel from a Wikipedia page, by using the last part of its URL
 * 
 */

class LabelExtractor implements Extractor 
{
	const extractorID = "http://dbpedia.org/extractors/LabelExtractor";
    private $language;
    public function getExtractorID() {
        return self::extractorID;
    }
    public function start($language) {
        $this->language = $language;
    }
    public function extractPage($pageID, $pageTitle,  $pageSource) {
        $result = new ExtractionResult(
                $pageID, $this->language, self::extractorID);
                
        $result->addTriple(
                RDFtriple::page($pageID), 
                RDFtriple::URI("http://www.w3.org/2000/01/rdf-schema#label"),
                RDFtriple::Literal($this->decode_title($pageTitle), NULL, $this->language));

        return $result;
    }
    public function finish() { 
        return null;
    }
    
	function encode_title($s, $namespace = null) {
        $result = urlencode(str_replace(' ', '_', $s));
        if ($namespace) {
            $result = $namespace . ":" . $result;
        }
        return $result;
    }

    function decode_title($s) {
		if (is_null($s)) return null;
        return preg_replace("/^.*:/", "", str_replace('_', ' ', $s));
    }
    
    
}


