<?php
/**
 * Extracts the Wikipedia-Categoires an Article belongs to
 */

class ArticleCategoriesExtractor implements Extractor 
{
	const extractorID = "http://dbpedia.org/extractors/ArticleCategoriesExtractor";
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
				
		if(!preg_match("/Category:/",$pageID,$match))
		{
			if (preg_match_all("/\[\[Category:(.*)\]\]/",$pageSource,$matches, PREG_SET_ORDER))
			{
				foreach ($matches as $match)
				{
				$Category = preg_replace("/\|.*/","",$match[1]);
				
				$result->addTriple(
		                RDFtriple::page($pageID), 
		                RDFtriple::URI("http://www.w3.org/2004/02/skos/core#subject"),
		                RDFtriple::page("Category:" . $Category));  
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


