<?php


class SkosCategoriesExtractor extends Extractor 
{
	public function extractPage($pageID, $pageTitle,  $pageSource) {
        $result = new ExtractionResult(
                $pageID, $this->language, $this->getExtractorID());
        
		$category = Util::getMediaWikiNamespace($this->language, MW_CATEGORY_NAMESPACE);
		if (preg_match_all("/".$category.":(.*)/",$pageID,$match))
		//if (preg_match_all("/Category:(.*)/",$pageID,$match))
		{
		 $result->addTriple(
				$this->getPageURI(),
                RDFtriple::URI(SKOS_PREFLABEL,false),
                RDFtriple::Literal($this->decode_title($pageTitle), NULL, $this->language));

		$result->addTriple(
                $this->getPageURI(), 
                RDFtriple::URI(RDF_TYPE,false),
                RDFtriple::URI(SKOS_CONCEPT,false));

				
		if (preg_match_all("/\[\[".$category.":(.*)\]\]/",$pageSource,$matches, PREG_SET_ORDER))
			{
				foreach ($matches as $match)
				{
				
				// split on | sign
				if(strpos($match[1],'|') === false) {
					$object=Util::getDBpediaCategoryPrefix($this->language). URI::wikipediaEncode($match[1]);
				} else {
					$split = explode('|',$match[1]);
					$object = Util::getDBpediaCategoryPrefix($this->language). URI::wikipediaEncode($split[0]);
				}
				try {
					$object = RDFtriple::URI($object);
				} catch (Exception $e) {
					echo 'Caught exception: ',  $e->getMessage(), "\n";
					continue;						
				  }
				$result->addTriple(
		                $this->getPageURI(), 
		                RDFtriple::URI(SKOS_BROADER,false),
		                $object);  
				}				

			}
		}

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
        return preg_replace("/^.*:/", "", str_replace('_', ' ', $s));
    }
    
    
}


