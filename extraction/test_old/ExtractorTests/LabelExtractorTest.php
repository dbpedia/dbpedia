<?php
/*
 * Created on 30.08.2007
 *
 * Author: piethensel
 * 
 * Subject: Unit Test for the LabelExtractor
 */
 
 require_once(EXTRACTOR_TEST_FILENAME);
 
 class LabelExtractorTest extends ExtractorTest {
 	 	
 	function createExtractor() {
 		return new LabelExtractor();
 	}

	function testEncoding() {
		$output = $this->extractPage("","%ßå¥€:-_,(|[üöä","%ßå¥€:-_,(|[üöä","%ßå¥€:-_,(|[üöä","%ßå¥€:-_,(|[üöä");
		$expected = '<http://dbpedia.org/resource/%25%C3%9F%C3%A5%C2%A5%E2%82%AC:-_%2C%28%7C%5B%C3%BC%C3%B6%C3%A4> <http://www.w3.org/2000/01/rdf-schema#label> "- ,(|[\u00FC\u00F6\u00E4"@en .';
		$this->assertEqual(trim($output),trim($expected));
	}
 }
