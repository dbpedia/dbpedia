<?php

/**
 * Gets the Wikipedia PageSource directly from Wikipedia over Http.
 * 
 * 
 */

class TestWikipedia implements PageCollection {
    private $language;
    public function __construct($language) {
        $this->language = $language;
    }
    public function getLanguage() {
        return $this->language;
    }
    public function getSource($filename) {
        return file_get_contents(dirname(__FILE__)."/pageSources/".$filename);
	}
	
}


