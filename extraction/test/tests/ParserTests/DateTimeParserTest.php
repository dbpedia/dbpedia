<?php
/**
 * The DateTimeParserTest class is used by the SimpleTest PHP unit tester.
 * It is a PHP unit test and web test framework. (http://simpletest.org)
 *
 * This is the test for the DateTimeParser class.
 * The test uses the testData in testDates.xml and compares the
 * output from the DateTimeParser class with the expected results in
 * the testDates.xml
 *
 * @author	Paul Kreis <mail@paulkreis.de>
 *
 */
require_once('parsers/Parser.php');
require_once('parsers/DateTimeParser.php');

class DateTimeParserTest extends UnitTestCase {

    function testParsing() {
        $parser = new DateTimeParser();
        $dom = new DOMDocument();
        $dom->load("test/testData/testDates.xml");
        foreach ($dom->getElementsByTagName('testDate') as $dateNode) {
            $lang = $dateNode->getAttribute('lang');
            $input = $dateNode->nodeValue;
            $unit = $dateNode->getAttribute('unit');
            $expected = $dateNode->getAttribute('expected');
            $output = $parser->parseValue($input, $lang, array(PAGEID=>'testPage',PROPERTYNAME=>'testProperty',UNITTYPE=>null, UNITEXACTTYPE=>null, TARGETUNIT=>null));
            $this->assertEqual($expected,$output[0],'VALUE test - Input: '.htmlspecialchars($input).' # Output: '.$output[0].' # Expected: '.$expected);
            $this->assertEqual($expected,$output[0],'UNIT test - Input: '.htmlspecialchars($input).' # Output: '.$output[1].' # Expected: '.$unit);
        }
    }
}
