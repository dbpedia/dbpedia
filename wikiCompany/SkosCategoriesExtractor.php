<?php


class SkosCategoriesExtractor implements Extractor 
{
	const extractorID = "http://dbpedia.org/extractors/SkosCategoriesExtractor";
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
                
		if (preg_match_all("/Category:(.*)/",$pageID,$match))
		{
		 $result->addTriple(
                RDFTriple::page($pageID), 
                RDFTriple::URI("http://www.w3.org/2004/02/skos/core#prefLabel"),
                RDFTriple::Literal($this->decode_title($pageTitle), NULL, $this->language));

		$result->addTriple(
                RDFTriple::page($pageID), 
                RDFTriple::URI("http://www.w3.org/1999/02/22-rdf-syntax-ns#type"),
                RDFTriple::URI("http://www.w3.org/2004/02/skos/core#Concept"));

				
		if (preg_match_all("/\[\[Category:(.*)\]\]/",$pageSource,$matches, PREG_SET_ORDER))
			{
				foreach ($matches as $match)
				{
				$result->addTriple(
		                RDFTriple::page($pageID), 
		                RDFTriple::URI("http://www.w3.org/2004/02/skos/core#broader"),
		                RDFTriple::page("Category:" . $match[1]));  
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


