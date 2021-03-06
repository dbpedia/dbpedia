<?php
/**
 * Unit Test for the PageLinksExtractor
 *
 * @author	Piet Hensel
 * @author	Paul Kreis <mail@paulkreis.de>
 * 
 */

class PageLinksExtractorTest extends ExtractorTest {

	function createExtractor() {
		return new PageLinksExtractor();
	}


	function testLinks() {
		$input = '[[wikipediaPage | Description]]';
		$output = $this->extractPage($input);
		$expected = '<http://dbpedia.org/resource/TestResource> <http://dbpedia.org/property/wikilink> <http://dbpedia.org/resource/wikipediaPage> .';
		$this->assertEqual(trim($output),trim($expected));

		$input = '[[wikipediaPage]]';
		$output = $this->extractPage($input);
		$this->assertEqual(trim($output),trim($expected));

		$input = '[[öäöulß¿?´+}]]';
		$output = $this->extractPage($input);
		$expected = '<http://dbpedia.org/resource/TestResource> <http://dbpedia.org/property/wikilink> <http://dbpedia.org/resource/%C3%B6%C3%A4%C3%B6ul%C3%9F%C2%BF%3F%C2%B4%2B%7D> .';
		$this->assertEqual(trim($output),trim($expected));
	}

}
