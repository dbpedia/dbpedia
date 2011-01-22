<?php
/**
 * Unit Test for the ExternalLinksExtractor
 *
 * @author	Piet Hensel
 * @author	Paul Kreis <mail@paulkreis.de>
 * 
 */

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
		$expected = '<http://dbpedia.org/resource/TestResource> <http://dbpedia.org/property/reference> <http://test.org/xyz/> .
<http://dbpedia.org/resource/TestResource> <http://dbpedia.org/property/reference> <http://www.test.com/abc.html> .
<http://dbpedia.org/resource/TestResource> <http://dbpedia.org/property/reference> <http://www.test.com/abcd.html> .';
		//echo '<br>OUTPUT: '.htmlspecialchars($output);
		//echo '<br>EXPECTED: '.htmlspecialchars($expected).'<br>';
		$this->assertEqual(trim($output),trim($expected));
	}

}
