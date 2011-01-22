<?php
/**
 * Unit Test for the AbstractExtractor
 *
 * @author	Piet Hensel
 * @author	Paul Kreis <mail@paulkreis.de>
 *
 */

class AbstractExtractorTest extends ExtractorTest {

	function createExtractor() {
		return new AbstractExtractor();
	}

	// Add specific Tests here
	function testExtractorID() {
		$this->assertEqual($this->extractor->getExtractorID(),'http://dbpedia.org/extractors/'.$this->extractorName);
	}
	function testExtractor() {
		$output = $this->extractPage(file_get_contents('test/pageSources/en-Berlin.txt'), "Berlin","Berlin");
		$expected = file_get_contents('test/expectedResults/AbstractExtractor/en-Berlin.txt');
		//echo '<br>OUTPUT: '.htmlspecialchars($output);
		//echo '<br>EXPECTED: '.htmlspecialchars($expected).'<br>';
		$this->assertEqual(trim($output),trim($expected));
	}

}
