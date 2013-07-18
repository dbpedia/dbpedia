<?php

/**
 * 
 */

class RedirectExtractor extends Extractor 
{
	public function extractPage($pageID, $pageTitle,  $pageSource) {
        $result = new ExtractionResult(
		$pageID, $this->language, $this->getExtractorID());

        if (Util::isRedirect($pageSource, $this->language))
		{
			if (preg_match("/\[\[(.*?)\]\]/",$pageSource,$matches) === 1)
			{
                try {
                    $s = $this->getPageURI();
                    $p = RDFtriple::URI(DB_REDIRECT,false);
                    $o = RDFtriple::page($this->getLinkForLabeledLink($matches[1]));
                    $result->addTriple($s,$p,$o);
                } catch(Exception $e) {
                    // exception is thrown when URIs are not valid, in this case we just
                    // do nothing i.e. do not write the triple
                    $this->log(INFO, $o->getURI().' is an invalid uri');
                }
			}
		}

        return $result;
    }
    
	function getLinkForLabeledLink($text2) {
		return preg_replace("/\|.*/", "", $text2) ;
	}
    
}


