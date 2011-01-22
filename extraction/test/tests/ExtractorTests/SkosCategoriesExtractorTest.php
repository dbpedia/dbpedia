<?php
/**
 * Unit Test for the SkosCategoriesExtractor
 *
 * @author	Piet Hensel
 * @author	Paul Kreis <mail@paulkreis.de>
 * 
 */

class SkosCategoriesExtractorTest extends ExtractorTest {

	function createExtractor() {
		return new SkosCategoriesExtractor();
	}

	function testCategory() {
		$output = $this->extractPage('Some Text [[Category:EFGH]] some more text','Category:ABCD','Category:ABCD');
		$expected = '<http://dbpedia.org/resource/Category:ABCD> <http://www.w3.org/2004/02/skos/core#prefLabel> "ABCD"@en .
<http://dbpedia.org/resource/Category:ABCD> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://www.w3.org/2004/02/skos/core#Concept> .
<http://dbpedia.org/resource/Category:ABCD> <http://www.w3.org/2004/02/skos/core#broader> <http://dbpedia.org/resource/Category:EFGH> .';
		//echo '<br>OUTPUT: '.htmlspecialchars($output);
		//echo '<br>EXPECTED: '.htmlspecialchars($expected).'<br>';
		$this->assertEqual(trim($output),trim($expected));

	}

}
