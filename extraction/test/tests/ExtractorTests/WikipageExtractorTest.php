<?php
/**
 * Unit Test for the WikipageExtractor
 *
 * @author	Piet Hensel
 * @author	Paul Kreis <mail@paulkreis.de>
 * 
 */

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
