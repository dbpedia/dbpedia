<?php
/**
 * Extracts the Wikipedia-Categoires an Article belongs to
 */

class ArticleCategoriesExtractor extends Extractor {
	
    public function extractPage($pageID, $pageTitle,  $pageSource) {
		$category = Util::getMediaWikiNamespace($this->language, MW_CATEGORY_NAMESPACE);
        $result = new ExtractionResult(
                $pageID, $this->language, $this->getExtractorID());
		if(!preg_match("/".$category.":/",$pageID,$match))
		{
			if (preg_match_all("/\[\[".$category.":(.*)\]\]/U",$pageSource,$matches, PREG_SET_ORDER))
			{
				foreach ($matches as $match)
				{
				$Category = preg_replace("/\|.*/","",$match[1]);
				$object=Util::getDBpediaCategoryPrefix($this->language). URI::wikipediaEncode($Category);
				try {
					$object = RDFtriple::URI($object);
				} catch (Exception $e) {
					$this->log(WARN, 'Caught exception: ',  $e->getMessage(), "\n");
					continue;						
				  }
				$result->addTriple(
						$this->getPageURI(),
		                RDFtriple::URI(SKOS_SUBJECT,false),
		                $object);  
				}				

			}
		}
        
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


