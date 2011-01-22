<?php
/**
 * Unit Test for the DisambiguationExtractor
 *
 * @author	Paul Kreis <mail@paulkreis.de>
 *
 *
 */

class DisambiguationExtractorTest extends ExtractorTest {

	function createExtractor() {
		return new DisambiguationExtractor();
	}

	function testEncoding() {
		$output = $this->extractPage(file_get_contents('test/pageSources/en-Madonna.txt'), "Madonna","Madonna");
		$expected = file_get_contents('test/expectedResults/DisambiguationExtractor/en-Madonna.txt');
		//echo '<br>OUTPUT: '.htmlspecialchars($output);
		//echo '<br>EXPECTED: '.htmlspecialchars($expected).'<br>';
		$this->assertEqual(trim($output),trim($expected));
	}
}
