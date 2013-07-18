<?php
/**
 * Unit Test for the GeoExtractor
 *
 * @author	Piet Hensel
 * @author	Paul Kreis <mail@paulkreis.de>
 * 
 */

class GeoExtractorTest extends ExtractorTest {

	function createExtractor() {
		return new GeoExtractor();
	}


	function testCoord() {
		$input = '{{coor title dm|47|25|N|8|10|E|region:CH-AG_type:adm1st}}';
		$output = $this->extractPage($input);
		$expected = '<http://dbpedia.org/resource/testResource> <http://www.w3.org/2003/01/geo/wgs84_pos#lat> "47.4166666667"^^<http://www.w3.org/2001/XMLSchema#float> .
			<http://dbpedia.org/resource/testResource> <http://www.w3.org/2003/01/geo/wgs84_pos#long> "8.16666666667"^^<http://www.w3.org/2001/XMLSchema#float> .
			<http://dbpedia.org/resource/testResource> <http://www.geonames.org/ontology#featureClass> <http://www.geonames.org/ontology#A> .
			<http://dbpedia.org/resource/testResource> <http://www.geonames.org/ontology#featureCode> <http://www.geonames.org/ontology#A.ADM2> .';
		//echo '<br>OUTPUT: '.htmlspecialchars($output);
		//echo '<br>EXPECTED: '.htmlspecialchars($expected).'<br>';
		$this->assertEqual(trim($output),trim($expected));
	}

	function testCoor() {
		// Test fails, should be adopted
		/*
		$input = "{{coor dm|48|46.600|N|121|48.850|W|}}";
		$output = $this->extractPage($input);
		$expected = '...';
		$this->assertEqual(trim($output),trim($expected));
		*/
	}

	function testGeolinks() {
		// Add Test for Template "Geolinks" here
	}

	function testMapit() {
		// Add test for template Mapit here
	}

	function testKoordinate() {
		// Test fails, should be adopted
		/*
		$input = '{{Koordinate|Text|47|38||N|9|22||O|type=waterbody|region=AT-1|dim=60000}}';
		$output = $this->extractPage($input);
		$expected = '...';
		$this->assertEqual(trim($output),trim($expected));
		*/
	}
}
