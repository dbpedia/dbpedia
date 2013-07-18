<?php
/*
 * Created on 30.08.2007
 *
 * Author: piethensel
 * 
 * Subject: Unit Test for the WcGeokipageExtractor
 */
 
 require_once(EXTRACTOR_TEST_FILENAME);
 
 class WcGeoExtractorTest extends ExtractorTest {
 	 	
 	function createExtractor() {
 		return new WcGeoExtractor();
 	}

	function testGeo() {
		$input = "<geo>41.129409;-73.718344</geo>";
		$output = $this->extractPage($input);
		$expected = '<http://www4.wiwiss.fu-berlin.de/wikicompany/resource/testResource> <http://www.w3.org/2003/01/geo/wgs84_pos#lat> "41.129409"^^<http://www.w3.org/2001/XMLSchema#float> .
<http://www4.wiwiss.fu-berlin.de/wikicompany/resource/testResource> <http://www.w3.org/2003/01/geo/wgs84_pos#long> "-73.718344"^^<http://www.w3.org/2001/XMLSchema#float> .
';
		$this->assertEqual(trim($output),trim($expected));
	}
	
	
	
	
 }
