<?php
/**
 * Unit Test for the LabelExtractor
 *
 * @author	Piet Hensel
 * @author	Paul Kreis <mail@paulkreis.de>
 * 
 */

class LabelExtractorTest extends ExtractorTest {

	function createExtractor() {
		return new LabelExtractor();
	}

	function testEncoding() {
		$output = $this->extractPage("","Berlin", "Berlin");
		$expected = '<http://dbpedia.org/resource/Berlin> <http://www.w3.org/2000/01/rdf-schema#label> "Berlin"@en .';
		//echo '<br>OUTPUT: '.htmlspecialchars($output);
		//echo '<br>EXPECTED: '.htmlspecialchars($expected).'<br>';
		$this->assertEqual(trim($output),trim($expected));
	}
}
