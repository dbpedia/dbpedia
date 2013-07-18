<?php
/*
 * Created on 30.08.2007
 *
 * Author: piethensel
 * 
 * Subject: Unit Test for the SemanticExtractor
 */
 
 require_once(EXTRACTOR_TEST_FILENAME);
 
 class SemanticExtractorTest extends ExtractorTest {
 	 	
 	function createExtractor() {
 		return new SemanticExtractor();
 	}

	function testEmptyLinks() {
		$input = "[[partner::]]";
		$output = $this->extractPage($input);
		$expected = '';
		$this->assertEqual(trim($output),trim($expected));
	}
	
		function testGoodLinks() {
		$input = "[[partner::xyz]] [[attribut:=value]]";
		$output = $this->extractPage($input);
		$expected = '<http://www4.wiwiss.fu-berlin.de/wikicompany/resource/testResource> <http://dbpedia.org/property/partner> <http://www4.wiwiss.fu-berlin.de/wikicompany/resource/xyz> .
<http://www4.wiwiss.fu-berlin.de/wikicompany/resource/testResource> <http://dbpedia.org/property/attribut> "value"@en .
';
		$this->assertEqual(trim($output),trim($expected));
	}
	
	
	
	
	
 }
