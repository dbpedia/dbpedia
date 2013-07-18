<?php
/**
 * Unit Test for the RedirectExtractor
 *
 * @author	Paul Kreis <mail@paulkreis.de>
 * 
 */

class RedirectExtractorTest extends ExtractorTest {

	function createExtractor() {
		return new RedirectExtractor();
	}

	function testEncoding() {
		$output = $this->extractPage("#REDIRECT [[European Union]] {{R from abbreviation}}", "EU","EU");
		$expected = '<http://dbpedia.org/resource/EU> <http://dbpedia.org/property/redirect> <http://dbpedia.org/resource/European_Union> .';
		//echo '<br>OUTPUT: '.htmlspecialchars($output);
		//echo '<br>EXPECTED: '.htmlspecialchars($expected).'<br>';
		$this->assertEqual(trim($output),trim($expected));
	}
}
