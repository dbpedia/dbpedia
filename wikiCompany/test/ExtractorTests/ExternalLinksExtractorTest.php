<?php
/*
 * Created on 30.08.2007
 *
 * Author: piethensel
 * 
 * Subject: Unit Test for the ExternalLinksExtractor
 */
 
 require_once(EXTRACTOR_TEST_FILENAME);
 
 class ExternalLinksExtractorTest extends ExtractorTest {
 	 	
 	function createExtractor() {
 		return new ExternalLinksExtractor();
 	}

	function testExternalLinks() {
		$input = '== External links == ' .
				'{{a Template|has to be ignored}}
*[http://test.org/xyz/ Test Homepage]
*[http://www.test.com/abc.html ] broken brackets]
*[http://www.test.com/abcd.html xyz.htm ]';
		
		$output = $this->extractPage($input);
		$expected = '<http://dbpedia.org/resource/testResource> <http://dbpedia.org/property/reference> <http://test.org/xyz/> .
<http://dbpedia.org/resource/testResource> <http://dbpedia.org/property/reference> <http://www.test.com/abc.html> .
<http://dbpedia.org/resource/testResource> <http://dbpedia.org/property/reference> <http://www.test.com/abcd.html> .';
		$this->assertEqual(trim($output),trim($expected));
	}
	
 }
