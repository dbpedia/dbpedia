<?php

/**
 * This Extractor extract the first 500 characters, from the first section
 * of a Wikipedia article
 * 
 *  
 */
class ShortAbstractExtractor implements Extractor 
{
	const extractorID = "http://dbpedia.org/extractors/ShortAbstractExtractor";
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
                
                $CleanSource = $this->remove_wikicode($pageSource) ;
                $Abstract = $this->extract_abstract($CleanSource);
                //$LongAbstract = $this->extract_abstract($CleanSource, 3000, false, false);
                             
        $result->addTriple(
                RDFtriple::page($pageID), 
                RDFtriple::URI("http://www.w3.org/2000/01/rdf-schema#comment"),
                RDFtriple::Literal($Abstract, NULL, $this->language));   
                
        return $result;
    }
    public function finish() { 
        return null;
    }
    
    public function remove_wikicode($text){
	// Remove Table-of-contents
	$text = preg_replace("/(__TOC__|__NOTOC__|__FORCETOC__)/","", $text);
	
	//Remove SeeOther
		$text = preg_replace("/:''For(.*)see(.*).''/", "" , $text);
		$text = preg_replace("/: ''If you are searching(.*).''/", "" , $text);
        // Remove comment sections
        $text = preg_replace("/\s*<!--.*?-->\s*/s", "", $text);
		//Remove small templates
		$text = preg_replace("/\s*\{\{[a-zA-Z0-9-]+\}\}\s*/s", "", $text);
        // Remove tables
		$text = preg_replace("/\s*\{\|[^\}]*?\{\|.*?\|\}.*?\|\}\s*/s", "", $text);
        $text = preg_replace("/\s*\{\|.*?\|\}\s*/s", "", $text);
        // Remove Templates
        while ( preg_match("/\{{2}[^\{\}]+\}{2}/",$text) )
			$text = preg_replace("/\{{2}[^\{\}]+\}{2}/","",$text); 
        // Remove templates
        $text = preg_replace("/\s*\{\{[^{}]*\}\}\s*/s", "", $text);
        // Remove templates
		$text = preg_replace("/\s*\{\{.*?\}\}\s*/s", "", $text);
        // Remove images, category links, language links
		// This regex has to be fixed (e.g. Franz_Oppenheimer) PIET 
		$text = preg_replace("/\s*\[\[[a-zA-Z0-9-]+:.*?\[\[.*?\]\].*?\[\[.*?\]\].*?\[\[.*?\]\].*?\[\[.*?\]\].*?\]\] */", "", $text);
		//
		$text = preg_replace("/\s*\[\[[a-zA-Z0-9-]+:.*?\[\[.*?\]\].*?\[\[.*?\]\].*?\[\[.*?\]\].*?\]\] */", "", $text);
		$text = preg_replace("/\s*\[\[[a-zA-Z0-9-]+:.*?\[\[.*?\]\].*?\[\[.*?\]\].*?\]\] */", "", $text);
		$text = preg_replace("/\s*\[\[[a-zA-Z0-9-]+:.*?\[\[.*?\]\].*?\]\] */", "", $text);
        $text = preg_replace("/\s*\[\[[a-zA-Z0-9-]+:.*?\]\] */", "", $text);
        // Remove references
        $text = preg_replace("/<ref.*?<\/ref>/s", "", $text);
        // Remove HTML tags
        $text = preg_replace("/<.*?>/s", "", $text);
        // Remove bold and italics
        $text = preg_replace("/'''/", "", $text);
        $text = preg_replace("/''/", "", $text);
        // Replace Wiki links with their labels
        $text = preg_replace_callback("/\[\[([^|]*?)(\|.*?)?\]\]/",array(&$this, 'getLabelForLink'), $text);
        // Replace external links with their labels
        $text = preg_replace_callback("/(\[http:\/\/[^\]]+\.[a-zA-Z0-9_]{1,4})( [^\]]+)(\])/",array(&$this, 'getLabelForExternalLink'), $text);
        //$text = preg_replace_callback("/\[([^ ]+?)( +[^\]]+)?\]/",  array("WikipediaArticle", "extlink_replacement_text"), $text);
		//leere Klammern weg
		$text = preg_replace("/\[\]/", "", $text);
		$text = preg_replace("/\(\)/", "", $text);
	return trim($text);
	}
	
	public function extract_abstract($text, $maxabstlen = 500, $firstparagraph=true, $joinlines=true) {
		
        // Keep only first section, up to first headline
        preg_match("/^(.*?)([\s]*==+[^\n\r=]*==+.*)?$/s", $text, $match);
        $text = $match[1];
        // Keep only first paragraph
		if ($firstparagraph)
		{
			$text = preg_replace("/\n\s*\n.*/s", "", $text);
		}
        // Take only a few sentences; we want to have less than 500 characters
		$sentences = preg_split("/(?<=\.\s)/", $text, -1);
        $text = "";
        $length = 0;
        foreach ($sentences as $sentence) {
            if ($length > 50 && $length + strlen($sentence) > $maxabstlen) {
                break;
            }
            $length += strlen($sentence);
            $text .= $sentence;
        }
        // Join lines and collapse whitespace
        	if ($joinlines)
		{
			$text = preg_replace("/\s+/", " ", $text);
		}
        // Remove leading and trailing spaces
        return trim($text);
    }
	
	public function getLabelForLink($text2) {
		return str_replace("]]","",str_replace("[[","",preg_replace("/.*\|/", "[[", $text2[0]))) ;
	}
	
	public function getLabelForExternalLink($text2) {
		return str_replace( "]","",preg_replace("/(\[http:\/\/[^\]]+\.[a-zA-Z0-9_]{1,4})/","",$text2[0]) );

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


