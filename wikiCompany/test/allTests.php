<?php
/*
 * Created on 27.08.2007
 *
 * Author: piethensel
 * 
 * Subject: Runs all Tests for the DBpedia Extraction Framework
 */
// Define DBpedia root folder 
define('DBPEDIA_PATH', dirname(__FILE__) . '/../');

// Filename of the file including the class ExtractorTest
define('EXTRACTOR_TEST_FILENAME', 'ExtractorTest.php');

 if (! defined('SIMPLE_TEST')) {
      define('SIMPLE_TEST', DBPEDIA_PATH . '/test/simpletest/');
}


require_once(SIMPLE_TEST . 'unit_tester.php');
require_once(SIMPLE_TEST . 'reporter.php');
require_once(DBPEDIA_PATH.'dbpedia.php');
require_once(DBPEDIA_PATH.'extraction/config.inc.php');
require_once(DBPEDIA_PATH.'extraction/ExtractTemplates.php');


// Load classes dynamically
function __autoload($class_name) {
    if ( file_exists(DBPEDIA_PATH . $class_name . '.php'))
    	require_once (DBPEDIA_PATH . $class_name . '.php');
    else
    	require_once (dirname(__FILE__) . '/ExtractorTests/' . $class_name . '.php');
}

// Ignore Notices for Testing
error_reporting(E_ALL ^ E_NOTICE);

$test = &new TestSuite('All tests');

// Add test case for each extractor
/*
$test->addTestCase(new ArticleCategoriesExtractorTest());
$test->addTestCase(new ExternalLinksExtractorTest("en"));
$test->addTestCase(new GeoExtractorTest());
$test->addTestCase(new HomepageExtractorTest("de"));
$test->addTestCase(new HomepageExtractorTest("en"));
$test->addTestCase(new HomepageExtractorTest("fr"));
$test->addTestCase(new ImageExtractorTest());
$test->addTestCase(new InfoboxExtractorTest());
$test->addTestCase(new LabelExtractorTest("en"));
$test->addTestCase(new LongAbstractExtractorTest());
$test->addTestCase(new PageLinksExtractorTest());
$test->addTestCase(new PersondataExtractorTest("de"));
$test->addTestCase(new PersondataExtractorTest("en"));
$test->addTestCase(new ShortAbstractExtractorTest());
$test->addTestCase(new SkosCategoriesExtractorTest());
$test->addTestCase(new WikipageExtractorTest("en"));
$test->addTestCase(new WikipageExtractorTest("de"));
*/
$test->addTestCase(new DBpediaLinkExtractorTest());
$test->addTestCase(new WcGeoExtractorTest());
$test->addTestCase(new SemanticExtractorTest());
$test->run(new HtmlReporter());



