<?php
/**
 * The GeoParserTest class is used by the SimpleTest PHP unit tester.
 * It is a PHP unit test and web test framework. (http://simpletest.org)
 *
 * This is the test for the GeoParser class.
 * The test uses the testData in testGeoCoords.xml and compares the
 * output from the GeoParser class with the expected results in
 * the testGeoCoords.xml
 *
 * @author	Paul Kreis <mail@paulkreis.de>
 *
 */
require_once('parsers/Parser.php');
require_once('parsers/GeoParser.php');

class GeoParserTest extends UnitTestCase {

	function testParsing() {
		$parser = new GeoParser();
		$dom = new DOMDocument();
		$dom->load("test/testData/testGeoCoords.xml");
		foreach ($dom->getElementsByTagName('testValue') as $dateNode) {
			$input = $dateNode->nodeValue;
			$lat = $dateNode->getAttribute('lat');
			$long = $dateNode->getAttribute('long');
			$output = $parser->parseValue($input, 'en', '');
			$this->assertEqual($lat,$output['lat'],'latTest - input: "'.$input.'" required: "'.$lat.'" actual: "'.$output['lat'].'"');
			$this->assertEqual($long,$output['long'],'longTest - input: "'.$input.'" required: "'.$long.'" actual: "'.$output['long'].'"');
		}
	}
}
