<?php

/**
 * This is an extractor sample, which can be used for creating new extractors.
 * It is based on the LabelExtractor
 */

class SampleExtractor extends Extractor 
{
	protected $metadata = array(
			PRODUCES => array(
						array('type'=>STARTSWITH, 's' => '', 'p' => RDFS_LABEL, 'o'=>'', 'otype'=> '')
						)
					);
/**
 * See ExtractorInterface.php
 */
    public function extractPage($pageID, $pageTitle,  $pageSource) {
		
		//create a new ExtractionResult for the collection of triples
        $result = new ExtractionResult(
                $pageID, $this->language, $this->getExtractorID());
        
		if($this->decode_title($pageTitle)==NULL) return $result;
        
		$result->addTriple(
				$this->getPageURI(),
                RDFtriple::URI(RDFS_LABEL,false),
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
        $label = preg_replace("/^(Category|Template):/", "", str_replace('_', ' ', $s));
		// take care of "(" ")" "&"
		$label = str_replace('%28','(',$label);
		$label = str_replace('%29',')',$label);
		$label = str_replace('%26','&',$label);
		return $label;
    }
    
    
}


