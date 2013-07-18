<?php

/**
 * HomepageExtractor
 * 
 * @author  Christian Becker
 */
 
class HomepageExtractor extends Extractor 
{
    const enableDebug = false;
    
    /**
    * @desc template properties names commonly used for the official homepage. must be lower case.
    */
    var $knownHomepagePredicates = array('website', 'homepage', 'webpräsenz', 'web', 'site', 'siteweb', 'site web');
    
    /**
    * @desc regex parts matching words commonly used for the official homepage
    */
    var $knownPatterns = array('en' => 'official',
                               'de' => 'offizielle',
                               'fr' => 'officiel');
    
    
    /**
    * @desc regex parts matching words commonly used for the 'external links' sections
    */
    var $externalLinkSections = array("en" => "External links?",
                                      "de" => "Weblinks?",
                                      "fr" => "(?:Lien externe|Liens externes|Liens et documents externes)",
                                      );
    
    
    private $allPredicates;
    
    public function start($language) {
        $this->language = $language;
        $this->allPredicates = new ExtractionResult("PredicateCollection", $this->language, $this->getExtractorID());
    }
    
   
    
    public function extractPage($pageID, $pageTitle, $pageSource) {
        
        $result = new ExtractionResult($pageID, $this->language, $this->getExtractorID());
            
        $link = $this->findLink($pageSource);
        
        if ($link) {
            
            $this->log(DEBUG,"Found link $link");
                            
            $result->addTriple(
                    $this->getPageURI(), 
                    RDFtriple::URI(FOAF_HOMEPAGE, false),
                    // already validated
                    RDFtriple::URI($link,false));
                    
        }
        
        return $result;
        
    }
            
    private function findLink($pageSource) {
        
        /* Look in infoboxes */
        $infoboxes = $this->getInfoboxes($pageSource);
        foreach ($infoboxes[1] as $box)  {           
            $boxProperties = $this->getBoxProperties($box);
            
            foreach ($this->knownHomepagePredicates as $pred)  {
                
                    if (isset($boxProperties[$pred])) {
                        $found = $this->parseLink($boxProperties[$pred]);
                        if ($found) return $found;
                    }
            }
        }
        
        
        /* Find and process "External links" section */
        if (isset($this->externalLinkSections[$this->language])) {
            // Note: we could extract the 'external links' section with one regex,
            // but that regex would be complex and could lead to stack overflows..
            
            // 1. split page source into two parts: before 'external links' header and after
            $sections = preg_split('~^=+\s*' . $this->externalLinkSections[$this->language] . '\s*=+\s*$~mi', $pageSource);
            if (isset($sections[1])) {
                // 2. use stuff after 'external links' header and before next header
                $externalLinkSection = preg_split("~^=.*=\s*$~m", $sections[1], 1);
                if (strlen($externalLinkSection[0]) > 0) {
                    $linkDesignationsPattern = '/\b'.$this->knownPatterns[$this->language].'\b/i';
                    // find stuff after '*' until end of line
                    preg_match_all('/\*\s*([^\n]*)/', $externalLinkSection[0], $links);
                    foreach ($links[1] as $link) {
                        // on en.wiki, match template 'official' (or its redirect 'offical') 
                        // TODO: add other languages.
                        if ($this->language == 'en' && preg_match('~\{\{\s*(?:official|offical)\s*\|\s*(https?://[^}\s]+)\s*\}\}~i', $link, $pieces)) {
                            
                            // Note: The template allows a little less whitespace than we do.
                            // Note: The template can deal with strange cases that we can't parse, e.g.
                            // {{official|1=http://good.good}}
                            // {{official|bad=bad|1=http://good.good}}
                            // {{official|http://bad.bad|1=http://good.good}}
                            
							$url = $pieces[1];
							if(URI::validate($url)) {
								return $url;
								}

                        } else if (preg_match($linkDesignationsPattern, $link)) {
                            $found = $this->parseLink($link);
                            if ($found) return $found;
                        }
                    }
                }
            }
        }

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
    	// FIXME: [^\{\}] is wrong - single curly braces are allowed within templates
        preg_match_all('/\{{2}((?>[^\{\}]+)|(?R))*\}{2}/x', $pageSource, $infoboxes);
        return $infoboxes;
    }

    /**
     * Retrieves properties defined in an infobox as an associative array.
     * All predicate keys are converted to lowercase.
     * 
     * @param   $box    Infobox code
     * @return  Associative array with predicates as keys
     */
    private function getBoxProperties($box) {
        
        /* Remove outside curly brackets */
        $box = substr($box, 1, strlen($box) - 2);
        
        /* Remove HTML comments */
        // FIXME: use Util::removeHtmlComments
        $box = preg_replace('/<\!--[^>]*->/mU', '', $box);
        
        /* Split triples; ignoring triples in subtemplates */
        $triples = preg_split('/\| (?! [^{]*\}\} | [^[]*\]\] )/x',$box);  
       
        $a = array();
        
        foreach ($triples as $triple) {
                $predObj = explode('=',$triple,2);
                
                if (count($predObj) == 2 && ($pred = trim($predObj[0])) != "" && ($obj = trim($predObj[1])) != "")
                {
                    $key = strtolower($pred);
                    $a[$key] = $obj;
                }
        }
        
        return $a;
    }
    
    /**
     * Tries to convert link formats found in Wiki source to plain URLs
     * 
     * @param   $link       Link entry from in Wiki source (various formats possible)
     * @return  Plain URL or null
     */
    private function parseLink($link) {
        
        /*
         * Some template values are 'None', 'unknown' etc., which would be converted to 'http://None'
         * below. We simply reject URLs that don't contain a single '.' (and hope that no one uses 
         * 'None.' or '...')
         */
        if (strpos($link, '.') === false) return null;
        
        
        /*
         * URLs may be provided in raw form within templates (website = http://hu-berlin.de) 
         * or even without http prefix (Website = www.alabama.gov)
         */
        foreach (array($link, "http://".$link) as $variant) {
            if (URI::validate($variant)) {
                return $variant;
            }
        }
        
        // match external link using normal wiki syntax
        if (preg_match('~\[(https?://\S+)\s?([^]]+)?\]~i', $link, $pieces)) {
            $url = $pieces[1];
                
            if (count($pieces) == 3) {
                $title = $pieces[2];
                // Try to find nice base URL: if the link title looks like it contains a host name, 
                // and the link title is contained in the URL, we use the link title. This cuts of 
                // '/index.html' cruft. TODO: But we may cut off important stuff...
                if (preg_match('/\w+\.\w+/', $title) && stristr($url, $title) !== false) {
                    /* TBD: Add 'www' prefix, if not provided? */
                    $url = "http://" . strtolower($title);
                }
            }
             
            if (URI::validate($url)) {
                return $url;
            }
        }
        
        return null;
    }
    
}

