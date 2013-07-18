<?php

/**
 * Extracts the Pagelabel from a Wikipedia page, by using the last part of its URL
 * 
 */

class LabelExtractor extends Extractor 
{
	
    public function extractPage($pageID, $pageTitle,  $pageSource) {
        $result = new ExtractionResult(
                $pageID, $this->language, $this->getExtractorID());
                if($this->decode_title($pageTitle)==NULL) return $result;
        $result->addTriple(
				$this->getPageURI(),
                RDFtriple::URI(RDFS_LABEL, false),
                RDFtriple::Literal($this->decode_title($pageTitle), NULL, $this->language));

        return $result;
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
		$template = Util::getMediaWikiNamespace($this->language, MW_TEMPLATE_NAMESPACE);
		$category = Util::getMediaWikiNamespace($this->language, MW_CATEGORY_NAMESPACE);
        $label = preg_replace("/^(".$template."|".$category."):/", "", str_replace('_', ' ', $s));
		// take care of "(" ")" "&"
		$label = str_replace('%28','(',$label);
		$label = str_replace('%29',')',$label);
		$label = str_replace('%26','&',$label);
		return $label;
    }
    
    
}


