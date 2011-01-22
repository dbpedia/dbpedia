<?php
/*
 * Created on 30.08.2007
 *
 * Author: piethensel
 * 
 * Subject: Unit Test for the WikipageExtractor
 */
 
 require_once(EXTRACTOR_TEST_FILENAME);
 
 class WikipageExtractorTest extends ExtractorTest {
 	 	
 	function createExtractor() {
 		return new WikipageExtractor();
 	}

	function testEnglish() {
		if ($this->language == "en") {
			$output = $this->extractPage("","Page ID", "Page Title");
			$expected = '<http://dbpedia.org/resource/Page_ID> <http://xmlns.com/foaf/0.1/page> <http://en.wikipedia.org/wiki/Page_Title> .';
			$this->assertEqual(trim($output),trim($expected));
		}
	}
	
	function testOtherlanguage() {
		if ($this->language != "en") {
			$output = $this->extractPage("","Page ID", "Page Title");
			$expected = '<http://dbpedia.org/resource/Page_ID> <http://dbpedia.org/property/wikipage-'.$this->language.'> <http://'.$this->language.'.wikipedia.org/wiki/Page_Title> .';
			$this->assertEqual(trim($output),trim($expected));			
		}
	}
	
	
 }
