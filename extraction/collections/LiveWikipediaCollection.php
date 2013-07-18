<?php

/**
 * Gets the Wikipedia PageSource directly from Wikipedia over Http.
 * 
 * 
 */

class LiveWikipediaCollection implements PageCollection {
    private $language;
    public function __construct($language) {
        $this->language = $language;
    }
    public function getLanguage() {
        return $this->language;
    }
    public function getSource($pageID) {
        $url = "http://{$this->language}.wikipedia.org/wiki/Special:Export/$pageID";
		//echo $url; die;
		$context = stream_context_create(
                array(
                        "http" => array(
                                "user_agent" => "DBpedia"
                        )
                ));
		//echo $context;die;
        $xml = simplexml_load_string(file_get_contents($url, false, $context));
		//echo  $xml->page->revision->text;die;
        return $xml->page->revision->text;
    }
}


