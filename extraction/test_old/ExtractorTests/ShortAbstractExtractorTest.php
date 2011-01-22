<?php
/*
 * Created on 30.08.2007
 *
 * Author: piethensel
 * 
 * Subject: Unit Test for the ExternalLinksExtractor
 */
 
 require_once(EXTRACTOR_TEST_FILENAME);
 
 class ShortAbstractExtractorTest extends ExtractorTest {
 	 	
 	function createExtractor() {
 		return new ShortAbstractExtractor();
 	}

	// Add specific Tests here
	/*
	function testExtractorID() {
		$this->assertEqual($this->extractor->getExtractorID(),'http://dbpedia.org/extractors/'.$this->extractorName);
	}
	*/
 }
