<?php
/**
 * The UnitValueParserTest class is used by the SimpleTest PHP unit tester.
 * It is a PHP unit test and web test framework. (http://simpletest.org)
 *
 * This is the test for the UnitValueParser class.
 * The test uses the testData in testUnitValues.xml and compares the
 * output from the UnitValueParser class with the expected results in
 * the testUnitValues.xml
 *
 * @author	Paul Kreis <mail@paulkreis.de>
 *
 */
require_once('parsers/Parser.php');
require_once('parsers/UnitValueParser.php');

class UnitValueParserTest extends UnitTestCase {

	function testParsing() {
		$parser = new UnitValueParser();
		$dom = new DOMDocument();
		$dom->load("test/testData/testUnitValues.xml");
		foreach ($dom->getElementsByTagName('testValue') as $dateNode) {
			$input = $dateNode->nodeValue;
			$check = $dateNode->getAttribute('value');
			$check = $check*1;
			$language = $dateNode->getAttribute('lang');
			$unit = $dateNode->getAttribute('unit');
			$unitType = $dateNode->getAttribute('unitType');
			$output = $parser->parseValue($input, $language, array(PAGEID=>'testPage',PROPERTYNAME=>'testProperty',UNITTYPE=>$unitType, UNITEXACTTYPE=>null, TARGETUNIT=>null));
			// php bug: php cant compare floats
			if (is_double($check) || is_double($output[0][0])) {
			    $check = ''.$check;
			    $output[0][0] = ''.$output[0][0];
			}
			//var_dump($check);
			//var_dump($output[0][0]);
			$this->assertEqual($check,$output[0][0],'valueTest - language:"'.$language.'" input:"'.str_replace('%', '%%', $input).'" required:"'.$check.'" actual:"'.$output[0][0].'"');
			$this->assertEqual($unit,$output[0][1],'unitTest - language:"'.$language.'" input:"'.str_replace('%', '%%', $input).'" required:"'.$unit.'" actual:"'.$output[0][1].'"');
		}
	}
}
