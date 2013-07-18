<?php
/*
 * Created on 30.08.2007
 *
 * Author: piethensel
 * 
 * Subject: Unit Test for the DBpediaLinkExtractor
 */
 
 require_once(EXTRACTOR_TEST_FILENAME);
 
 class DBpediaLinkExtractorTest extends ExtractorTest {
 	 	
 	function createExtractor() {
 		return new DBpediaLinkExtractor();
 	}

	function testWikipedia() {
		$input = '{{wikipedia}}';
		$output = $this->extractPage($input);
		$expected = '';
		$this->assertEqual(trim($output),trim($expected));
	}

	function testCnote() {
		$input = '{{wikipedia-c-note}}';
		$output = $this->extractPage($input);
		$expected = '<http://www4.wiwiss.fu-berlin.de/wikicompany/resource/testResource> <http://www.w3.org/2002/07/owl#sameAs> <http://dbpedia.org/resource/testResource> .';
		$this->assertEqual(trim($output),trim($expected));
	}
	
	function testC() {
		$input = '{{wikipedia-c}}';
		$output = $this->extractPage($input);
		$expected = '<http://www4.wiwiss.fu-berlin.de/wikicompany/resource/testResource> <http://www.w3.org/2002/07/owl#sameAs> <http://dbpedia.org/resource/testResource> .';
		$this->assertEqual(trim($output),trim($expected));
	}
	
 }
