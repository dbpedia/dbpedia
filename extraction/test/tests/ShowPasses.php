<?php
/**
 * The show_passes.php is using the SimpleTest PHP unit tester.
 * It is a PHP unit test and web test framework. (http://simpletest.org)
 *
 * This file creates alternative output with details for all tests.
 * The code is from the tutorial at http://simpletest.org/en/display_subclass_tutorial.html
 *
 */
if (! defined('SIMPLE_TEST')) {
	define('SIMPLE_TEST', 'simpletest/');
}
require_once(SIMPLE_TEST . 'reporter.php');

class ShowPasses extends HtmlReporter {

	function ShowPasses() {
		$this->HtmlReporter();
	}
	function paintPass($message) {
		parent::paintPass($message);
		print "<span class=\"pass\">Pass</span>: ";
		$breadcrumb = $this->getTestList();
		array_shift($breadcrumb);
		print implode("-&gt;", $breadcrumb);
		print "-&gt;$message<br />\n";
	}
}
