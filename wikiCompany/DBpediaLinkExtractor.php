<?php

/**
 * Extracts the link to Wikipedia from a wikicompany article
 */

class DBpediaLinkExtractor implements Extractor 
{
	const extractorID = "http://dbpedia.org/wikicompany/extractors/DBpediaLinkExtractor";
    private $language;
    private $dbConnection;
    public function getExtractorID() {
        return self::extractorID;
    }
    public function start($language) {
        include ('extraction/config.inc.php');
	    $this->language = $language;
    }
    public function extractPage($pageID, $pageTitle, $pageSource) {
        $result = new ExtractionResult(
                $pageID, $this->language, self::extractorID);
        
        $pageID = encodeLocalName($pageID);
        // Extract Wikipedia Link
        if ( preg_match('/\{\{wikipedia\-c(\-note)?\}\}/',$pageSource) ) {
        	$result->addTriple(
                RDFtriple::page($pageID), 
                RDFtriple::URI("http://www.w3.org/2002/07/owl#sameAs"),
                RDFtriple::URI("http://dbpedia.org/resource/".$pageID));    
        }          
        return $result;
    }
    
    public function finish() { 
        return null;
    }
    
    

}

