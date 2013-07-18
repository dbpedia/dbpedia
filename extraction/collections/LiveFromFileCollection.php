<?php

/**
 * Gets the Wikipedia PageSource directly from Wikipedia over Http.
 * 
 * 
 */

class LiveFromFileCollection implements PageCollection {
    private $language;
    private $currentArticleFile;
	
	
	
    public function __construct($language, $currentArticleFile) {
        $this->language = $language;
        $this->currentArticleFile = $currentArticleFile;
    }
    public function getLanguage() {
        return $this->language;
    }
    public function getSource($pageID) {
		Timer::start('LiveFromFileCollection');
		$content = file_get_contents($this->currentArticleFile);
		Timer::stop('LiveFromFileCollection');
        return $content;
    }
}


