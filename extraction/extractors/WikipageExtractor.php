<?php

/**
 * For every DBpedia resource, this Extractor sets a Link to the corresponding
 * Wikipedia page.
 * 
 */

class WikipageExtractor extends Extractor 
{

    public function extractPage($pageID, $pageTitle,  $pageSource) {
	
		// language code in URI uses '-', not '_'
		$language = str_replace('_', '-', $this->language);
		
		$subject = $this->getPageURI();
		$predicate = $language == 'en' ? RDFtriple::predicate("wikipage-" . $language) : RDFtriple::URI(FOAF_PAGE,false);
		$object = RDFtriple::URI("http://" . $language . ".wikipedia.org/wiki/" . URI::wikipediaEncode($pageTitle));
		
        $result = new ExtractionResult($pageID, $this->language, $this->getExtractorID());
		$result->addTriple($subject, $predicate, $object);
        return $result;
    }
    
}


