<?php
/**
 * The ObjectTypeParserTest class is used by the SimpleTest PHP unit tester.
 * It is a PHP unit test and web test framework. (http://simpletest.org)
 *
 * This is the test for the ObjectTypeParser class.
 * The test uses the testData in testObjectTypes.xml and compares the
 * output from the ObjectTypeParser class with the expected results in
 * the testObjectTypes.xml
 *
 * @author	Paul Kreis <mail@paulkreis.de>
 *
 */
require_once('parsers/Parser.php');
require_once('parsers/ObjectTypeParser.php');

//
// UNDER CONSTRUCTION!
// ATTENTION: the databaseconfig.php includepath in the ObjectTypeParser.php
// must be changed from ("databaseconfig.php") to ("../../databaseconfig.php")
// otherwise the test will fail with a connecting error.
//

class ObjectTypeParserTest extends UnitTestCase {

	function testParsing() {
		$dom = new DOMDocument();
		$dom->load("test/testData/testObjectTypes.xml");
		foreach ($dom->getElementsByTagName('testValue') as $dateNode) {
			$checkList = array();
			foreach ($dateNode->getElementsByTagName('input') as $inputNode) {
				$input = $inputNode->nodeValue;
			}
			foreach ($dateNode->getElementsByTagName('expectedResult') as $expectedResultNode) {
				$checkList[] = $expectedResultNode->nodeValue;
			}
			//
			// ATTENTION: the restrictions are disabled in the ObjectTypeParser.php
			// if restrictions are enabled the encoding has to be considered
			//
			$output = ObjectTypeParser::parseValue($input, 'en', '');
			$countCheckArray = count($checkList);
			$countOutputArray = count($output);
			$this->assertEqual($countCheckArray,$countOutputArray,'number of expected results: '.$countCheckArray.' - number of actual results: '.$countOutputArray);
			if ($countCheckArray >= $countOutputArray) $lim = $countCheckArray; else $lim = $countOutputArray;
			for ($x=0;$x<$lim;$x++) {
				$this->assertEqual($checkList[$x],$output[$x]);
			}
		}
	}
}
