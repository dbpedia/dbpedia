<?php
/**
 * Unit Test for the ImageExtractor
 *
 * @author	Piet Hensel
 * @author	Paul Kreis <mail@paulkreis.de>
 * 
 */

class ImageExtractorTest extends ExtractorTest {

	function createExtractor() {
		return new ImageExtractor();
	}

	function testJPG() {
		$input = 'ABCD [[Image:abcd.jpeg]]';
		$output = $this->extractPage($input);
		$expected='<http://dbpedia.org/resource/TestResource> <http://xmlns.com/foaf/0.1/depiction> <http://upload.wikimedia.org/wikipedia/commons/thumb/0/0f/abcd.jpeg/200px-abcd.jpeg> .
			<http://dbpedia.org/resource/TestResource> <http://xmlns.com/foaf/0.1/img> <http://upload.wikimedia.org/wikipedia/commons/0/0f/abcd.jpeg> .';
		$this->assertEqual(trim($output),trim($expected));

		$input = '{{ DummyTemplate |property  = xyz.jpg }}';
		$output  = $this->extractPage($input);
		$expected='<http://dbpedia.org/resource/TestResource> <http://xmlns.com/foaf/0.1/depiction> <http://upload.wikimedia.org/wikipedia/commons/thumb/f/f5/xyz.jpg/200px-xyz.jpg> .
			<http://dbpedia.org/resource/TestResource> <http://xmlns.com/foaf/0.1/img> <http://upload.wikimedia.org/wikipedia/commons/f/f5/xyz.jpg> .';
		$this->assertEqual(trim($output),trim($expected));
	}

}
