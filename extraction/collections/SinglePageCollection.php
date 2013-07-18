<?php

/**
 * Dummy PageCollection for tests. Always returns the same page source.
 * @author sahnwaldt
 *
 */

class SinglePageCollection implements PageCollection {
		
    private /* final */ /* string */ $language;
    
    private /* final */ /* string */ $pageSource;
    
    private /* final */ /* string */ $pageID;
    
    /**
     * @param $language language
     * @param $pageSource page source
     * @param $pageID If a page ID is given, getSource() will return the page source only 
     * for that page ID. For all other page IDs, getSource() will return an empty string.
     * If no page ID is given, getSource() will return the page source for all page IDs.
     */
    public function __construct($language, $pageSource, $pageID = null) {
        $this->language = $language;
        $this->pageSource = $pageSource;
        $this->pageID = $pageID;
    }
    
    public function getLanguage() {
        return $this->language;
    }
    
    public function getSource($pageID) {
		if(isset($this->pageID) && $this->pageID === $pageID)
			return $this->pageSource;
		else
			return '';
    }
}


