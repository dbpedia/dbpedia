<?php
/*
 * Created on 30.08.2007
 *
 * Author: piethensel
 * 
 * Subject: Unit Test for the ExternalLinksExtractor
 */
 
 require_once(EXTRACTOR_TEST_FILENAME);
 
 class InfoboxExtractorTest extends ExtractorTest {
 	 	
 	function createExtractor() {
 		return new InfoboxExtractor();
 	}

	function testPropertiesSameNameWithNumber() {
		// Test wheter properties like lik leaderName1, leaderName2, ...
		// are renamed to leaderName. Properties which are just a number
		// however must not be renamed
		$input = '{{ Dummy Template | ' .
				'leaderName1 = nameA |' .
				'leaderName2 = nameB |' .
				'leaderName13 = nameC |' .
				'1 = nameD |' .
				'12 = nameE }}';
		$output = $this->extractPage($input);
		$expected = '<http://dbpedia.org/resource/testResource> <http://dbpedia.org/property/leadername> "nameA"@en .
<http://dbpedia.org/resource/testResource> <http://dbpedia.org/property/leadername> "nameB"@en .
<http://dbpedia.org/resource/testResource> <http://dbpedia.org/property/leadername13> "nameC"@en .
<http://dbpedia.org/resource/testResource> <http://dbpedia.org/property/_1> "nameD"@en .
<http://dbpedia.org/resource/testResource> <http://dbpedia.org/property/_12> "nameE"@en .
<http://dbpedia.org/resource/testResource> <http://dbpedia.org/property/wikiPageUsesTemplate> <http://dbpedia.org/resource/Template:dummy_template> .';
		$this->assertEqual(trim($output),trim($expected));
	}
 }
