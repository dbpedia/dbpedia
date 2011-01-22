<?php

/*
 * Counts the characters in a Wikipedia articles
 */

class CharacterCountExtractor extends Extractor {
   
    public function extractPage($pageID, $pageTitle, $pageSource) {
        $result = new ExtractionResult(
                $pageID, $this->language, $this->getExtractorID());
        $result->addTriple(
				$this->getPageURI(),
                RDFtriple::URI(DB_CHARACTERCOUNT,false),
                RDFtriple::literal("".strlen($pageSource)."",XS_INTEGER));
        return $result;
    }
    public function finish() { 
        return null;
    }
}


