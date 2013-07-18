<?php

/**
 * For every DBpedia resource, this Extractor sets a Link to the corresponding
 * Wikipedia page.
 * 
 */

class WikipageExtractor implements Extractor 
{
	const extractorID = "http://dbpedia.org/extractors/WikipageExtractor";
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
                
				if ($this->language == "en")
				{
				 $result->addTriple(
                RDFtriple::page($pageID), 
                RDFtriple::URI("http://xmlns.com/foaf/0.1/page"),
                RDFtriple::URI("http://wikicompany.org/wiki/" . URI::wikipediaEncode($pageTitle)));
				
				}
				
        return $result;
    }
    public function finish() { 
        return null;
    }
    
}


