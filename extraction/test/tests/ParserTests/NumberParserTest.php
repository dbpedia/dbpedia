<?php
/**
 * The NumberParserTest class is used by the SimpleTest PHP unit tester.
 * It is a PHP unit test and web test framework. (http://simpletest.org)
 *
 * This is the test for the NumberParser class.
 * The test uses the testData in testNumberValues.xml and compares the
 * output from the NumberParser class with the expected results in
 * the testNumberValues.xml
 *
 * @author	Paul Kreis <mail@paulkreis.de>
 *
 */
require_once('parsers/Parser.php');
require_once('parsers/NumberParser.php');

class NumberParserTest extends UnitTestCase {

	function testParsing() {
		$parser = new NumberParser();
		$dom = new DOMDocument();
		$dom->load("test/testData/testNumberValues.xml");
		foreach ($dom->getElementsByTagName('testValue') as $dateNode) {
			$input = $dateNode->nodeValue;
			$check = $dateNode->getAttribute('value');
			$language = $dateNode->getAttribute('lang');
			$type = $dateNode->getAttribute('type');
			$output = $parser->parseValue($input, $language, array($type))*1;
			$this->assertEqual($check,$output);
			if ($type == 'integer') {
				$this->assertTrue(is_int($output));
			}
			if ($type == 'float') {
				$this->assertTrue(is_float($output));
			}
		}
	}
}
