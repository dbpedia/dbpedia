<?php

/**
 * Gets the Wikipedia PageSource directly from Wikipedia over Http.
 * 
 * 
 */

class LiveWikipedia implements PageCollection {
    private $language;
    public function __construct($language) {
        $this->language = $language;
    }
    public function getLanguage() {
        return $this->language;
    }
    public function getSource($pageID) {
        $url = "http://wikicompany.org/wiki/Special:Export/$pageID";
		$context = stream_context_create(
                array(
                        "http" => array(
                                "user_agent" => "DBpedia"
                        )
                ));
        $xml = simplexml_load_string(file_get_contents($url, false, $context));
        return $xml->page->revision->text;
    }
}


