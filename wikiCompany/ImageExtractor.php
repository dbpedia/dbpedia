<?php

/**
 * Extracts the first image of a Wikipediapage. Constrcuts a thumbnail from it, and
 * the fullsize image.
 */

class ImageExtractor implements Extractor 
{
	const extractorID = "http://dbpedia.org/extractors/ImageExtractor";
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
             
        // Add fullsize image   
        $ImageURL = $this->extract_image_url($pageSource,$pageTitle); 
		if ($ImageURL == null || !URI::validate($ImageURL)) {
            return $result;    
        }
        $result->addTriple(
                RDFtriple::page($pageID), 
                RDFtriple::URI("http://xmlns.com/foaf/0.1/depiction"),
                RDFtriple::URI($ImageURL));        
                
        return $result;
    }
    
    public function finish() { 
        return null;
    }
    
    
    function extract_image_url($text,$pageTitle) {
    	   
	    // Remove HTML-Tags from text
	    $text = trim(preg_replace("/<[^>]+>/"," ",$text));

    	if (preg_match_all("/(\[\[image:)([^\]\|]*)(\|[^\]]*)?\]\]/i", $text, $match)) {
	       	$name = $match[2][0];
	       	if (preg_match("/\{\{logo\}\}/",$name)) {
	        	$name = ucwords(strtolower($pageTitle)).".png";
	        }
		} else if (preg_match_all("/([a-z0-9_ -]+\.)(?:jpe?g|png|gif)/i", $text, $match)) {
	        $name = $match[0][0];
	    }
	    if (!isset($name)) return null;
		return $this->make_image_url($name);
	}

function make_image_url($image_name) {
    $clean_name = str_replace(" ", "_", trim($image_name));
	$prefix = "http://wikicompany.org/wiki/images/";
	return $prefix.$clean_name;
}


}

