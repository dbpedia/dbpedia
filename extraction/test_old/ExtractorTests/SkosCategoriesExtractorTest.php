<?php
/*
 * Created on 30.08.2007
 *
 * Author: piethensel
 * 
 * Subject: Unit Test for the SkosCategoriesExtractor
 */
 
 require_once(EXTRACTOR_TEST_FILENAME);
 
 class SkosCategoriesExtractorTest extends ExtractorTest {
 	 	
 	function createExtractor() {
 		return new SkosCategoriesExtractor();
 	}


	function testCategory() {
		$input = 'Some Text [[Category:EFGH]] some more text';
		$output = $this->extractPage($input,'Category:ABCD','Category:ABCD');
		$expected = '<http://dbpedia.org/resource/Category:ABCD> <http://www.w3.org/2004/02/skos/core#prefLabel> "ABCD"@en .
<http://dbpedia.org/resource/Category:ABCD> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://www.w3.org/2004/02/skos/core#Concept> .
<http://dbpedia.org/resource/Category:ABCD> <http://www.w3.org/2004/02/skos/core#broader> <http://dbpedia.org/resource/Category:EFGH> .
';
		$this->assertEqual(trim($output),trim($expected));
		
	}
	
 }
