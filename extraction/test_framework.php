<?php
/**
 * The test_framework.php is using the SimpleTest PHP unit tester.
 * It is a PHP unit test and web test framework. (http://simpletest.org)
 *
 * This is the main test file.
 * Uncomment the tests you want to run and start this script.
 *
 * @author	Paul Kreis <mail@paulkreis.de>
 *
 */

// SELECT THE OUTPUT FORMAT:
// html: html output with detailed information to all tests
// lesshtml: html output only with details for the failed tests
// line: command line reporter, only with details for the failed tests
$outputFormat = 'line';

if (! defined('SIMPLE_TEST')) {
	define('SIMPLE_TEST', 'test/simpletest/');
}

require_once(SIMPLE_TEST . 'unit_tester.php');
require_once(SIMPLE_TEST . 'reporter.php');
require_once('test/tests/ShowPasses.php');
require_once('dbpedia.php');

require_once('test/tests/ExtractorTests/ExtractorTest.php');



$test = &new TestSuite('All tests');

// Add test cases for parsers
// ---------------------------------------------
//$test->addTestCase(new DateTimeParserTest());
//$test->addTestCase(new NumberParserTest());
//$test->addTestCase(new GeoParserTest());
$test->addTestCase(new UnitValueParserTest());
//$test->addTestCase(new ObjectTypeParserTest());

// Add test cases for extractors
// ---------------------------------------------
//$test->addTestCase(new ArticleCategoriesExtractorTest());
//$test->addTestCase(new DisambiguationExtractorTest());
//$test->addTestCase(new ExternalLinksExtractorTest("en"));
//$test->addTestCase(new MappingBasedExtractorTest());
//$test->addTestCase(new GeoExtractorTest());
//$test->addTestCase(new HomepageExtractorTest("de"));
//$test->addTestCase(new HomepageExtractorTest("en"));
//$test->addTestCase(new HomepageExtractorTest("fr"));
//$test->addTestCase(new ImageExtractorTest());
//$test->addTestCase(new InfoboxExtractorTest());
//$test->addTestCase(new LabelExtractorTest("en"));
//$test->addTestCase(new AbstractExtractorTest());
//$test->addTestCase(new PageLinksExtractorTest());
//$test->addTestCase(new PersondataExtractorTest("de"));
//$test->addTestCase(new PersondataExtractorTest("en"));
//$test->addTestCase(new RedirectExtractorTest());
//$test->addTestCase(new SkosCategoriesExtractorTest());
//$test->addTestCase(new WikipageExtractorTest("en"));
//$test->addTestCase(new WikipageExtractorTest("de"));

// Add other test cases
// ---------------------------------------------
//$test->addTestCase(new UtilTest());

switch ($outputFormat) {
	case 'html':
	$test->run(new ShowPasses());
	break;

	case 'lesshtml':
	$test->run(new HtmlReporter());
	break;

	case 'line':
	$test->run(new TextReporter());
	break;
}
