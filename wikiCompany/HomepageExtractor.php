<?php

/**
 * HomepageExtractor
 * 
 * @author  Christian Becker
 */
 
class HomepageExtractor implements Extractor 
{
    const extractorID = "http://dbpedia.org/extractors/HomepageExtractor";
    const enableDebug = false;
    
    /**
    * @desc 
    */
    var $knownHomepagePredicates = array('website', 'homepage', 'Webpr%C3%A4senz', 'web', 'site', 'siteweb', 'site web');
    
    /**
    * @desc 
    */
    var $knownLinkDesignations = array('official', 'offizielle', 'officiel');
    
    
    /**
    * @desc 
    */
    var $externalLinkSections = array("en" => "External links?",
                                      "de" => "Weblinks?",
                                      "fr" => "(?:Lien externe|Liens externes|Liens et documents externes)",
                                      );
    
    private $language;
    private $allPredicates;
    
    public function getExtractorID() {
        return self::extractorID;
    }
    public function start($language) {
        $this->language = $language;
        $this->allPredicates = new ExtractionResult("PredicateCollection", $this->language, self::extractorID);
    }
    
   
    
    public function extractPage($pageID, $pageTitle, $pageSource) {
        
        $result = new ExtractionResult(
            $pageID, $this->language, self::extractorID);
            
        $foundLinks = array();
        
        /* Look in infoboxes */
        $infoboxes = $this->getInfoboxes($pageSource);
        
        foreach ($infoboxes[1] as $box)  {            
            
            $boxProperties = $this->getBoxProperties($box, true /* toLower */);
            
            foreach ($this->knownHomepagePredicates as $pred)  {
                
                    $key = strtolower(urldecode($pred));
                    
                    if (isset($boxProperties[$key])) {
                        $foundLinks[] = $this->parseURL($boxProperties[$key], true /* guessRoot */);
                        if (HomepageExtractor::enableDebug)
                            echo("<h3>Found box property '" . $pred . "'</h3>");
                    }
            }
        }
        
        /* Process "External links" */
        if (isset($this->externalLinkSections[$this->language])) {
            preg_match('/(==+\s*' . $this->externalLinkSections[$this->language] . '\s*==+(?:.(?!==+[^=]+==+))*)/s', $pageSource, $matches);
            
            preg_match_all('/\*\s*([^\n]*)/', $matches[1], $links);
            
            $linkDesignationsPattern = '/\b(' . implode('|', $this->knownLinkDesignations) . ')\b/i';
            
            foreach ($links[1] as $link) {
                if (preg_match($linkDesignationsPattern, $link))  {
                    $foundLinks[] = $this->parseURL($link, true /* $guessRoot */);
                }
            }
        }

        
        $numResults = 0;

        foreach ($foundLinks as $link)  {
            
            if (URI::validate($link)) {
                
                if (HomepageExtractor::enableDebug)
                    echo("<h3>Found link $link</h3>");
                
                /* Only process the first result */
                if (++$numResults == 1)  {
                    $result->addTriple(
                            RDFtriple::page($pageID), 
                            RDFtriple::URI("http://xmlns.com/foaf/0.1/homepage"),
                            RDFtriple::URI($link));
                }
            }
      }
        
      return $result;
    }
    
    public function finish() { 
        return $this->getPredicates();
    }
    
    
    private function getPredicates() {
        return $this->allPredicates->getPredicateTriples();
    }
    
    /**
     * Retrieves all infoboxes for a provided page source
     * 
     * @param   $pageSource
     * @return  Array as returned by preg_match_all
     */
    private function getInfoboxes($pageSource)
    {
        preg_match_all('/\{((?>[^{}]+)|(?R))*\}/x', $pageSource, $infoboxes);
        return $infoboxes;
    }

    /**
     * Retrieves properties defined in an infobox as an associative array
     * 
     * @param   $box    Infobox code
     * @param   $toLower    Whether to convert all predicate keys to lowercase
     * @return  Associative array with predicates as keys
     */
    private function getBoxProperties($box, $toLower = false) {
        
        /* Remove outside curly brackets */
        $box = substr($box, 1, strlen($box) - 2);
        
        /* Remove HTML comments */
        $box = preg_replace('/<\!--[^>]*->/mU', '', $box);
        
        /* Split triples; ignoring triples in subtemplates */
        $triples = preg_split('/\| (?! [^{]*\}\} | [^[]*\]\] )/x',$box);  
       
        $a = array();
        
        foreach ($triples as $triple) {
                $predObj = explode('=',$triple,2);
                
                if (count($predObj) == 2 && ($pred = trim($predObj[0])) != "" && ($obj = trim($predObj[1])) != "")
                {
                    $key = ($toLower ? strtolower($pred) : $pred);
                    $a[$key] = $obj;
                }
        }
        
        return $a;
    }
    
    /**
     * Tries to convert link formats found in Wiki source to plain URLs
     * 
     * @param   $link       Link entry from in Wiki source (various formats possible)
     * @param   $guessRoot  Whether a title providing the link's domain root overrides the link,
     *                      e.g. take www.microsoft.com when given "[http://www.microsoft.com/worldwide/ www.microsoft.com]"
     * @return  Plain URL or null
     */
    private function parseURL($link, $guessRoot = true) {
        /*
         * URLs may be provided in raw form within templates (website = http://hu-berlin.de) 
         * or even without http prefix (Website = www.alabama.gov)
         */
        foreach (array($link, "http://".$link) as $variant) {
            if (URI::validate($variant))
                return $variant;
        }
            
        if (!preg_match('~\[(http(?:s)?://[^ ]+)\s?([^]]+)?\]~i', $link, $pieces))
            return null;
        
        /*
         * [1]: URL
         * [2]: Link title (optional)
         */
         if ($guessRoot && count($pieces) == 3 && preg_match('/\w+\.\w+/', $pieces[2]) && stristr($pieces[1], $pieces[2]) !== false)
             /* TBD: Add 'www' prefix, if not provided? */
             return "http://" . strtolower($pieces[2]);
         else
            return $pieces[1];
    }
    
}