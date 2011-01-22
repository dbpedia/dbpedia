<?php
/**
 * The UtilTest class is used by the SimpleTest PHP unit tester.
 * It is a PHP unit test and web test framework. (http://simpletest.org)
 *
 * This is the test for the Util class.
 * The test uses testData and compares the output from the Util class
 * with the expected results.
 * The expectedResults for the Util test are in expectedResults/Util/
 *
 * @author	Paul Kreis (FU Berlin) <mail@paulkreis.de>
 *
 */

class UtilTest extends UnitTestCase {

	/**
	 * Returns a single line from a file
	 *
	 * @param string $filename
	 * @param int $line
	 * @return string
	 */
	function getFileLine($filename, $line) {
		$data = file($filename);
		return $data[$line];
	}

	/**
	 * This function compares the number of rows in the stringsWithXxx.txt
	 * with the number of rows in the stringsWithXxx_expRes.txt
	 * The test will fail if the files have a different number of rows.
	 */
	function testTestDataFiles() {
		$this->assertEqual(count(file('test/pageSources/stringsWithHtmlTags.txt')), count(file('test/expectedResults/Util/stringsWithHtmlTags_expRes.txt')));
		$this->assertEqual(count(file('test/pageSources/stringsWithWikiLinks.txt')), count(file('test/expectedResults/Util/stringsWithWikiLinks_expRes.txt')));
	}

	/**
	 * This function tests the removeHtmlTags function in the Util class.
	 * It uses the testData in testUtilStrings_tags.txt and compares the
 	 * output from the removeHtmlTags function with the expected results in
	 * the testUtilStrings_tags_expectedResults.txt
	 */
	function testRemoveHtmlTags() {
		$rows = count(file('test/pageSources/stringsWithHtmlTags.txt'));
		for ($x=0;$x<$rows;$x++) {
			$input = $this->getFileLine('test/pageSources/stringsWithHtmlTags.txt', $x);
			$input = Util::removeHtmlTags($input ,'ref');
			$input = Util::removeHtmlTags($input ,'sup');
			$input = Util::removeHtmlTags($input ,'nowiki');
			$input = Util::removeHtmlTags($input ,'small');
			$input = Util::removeHtmlTags($input ,'big');
			$input = Util::removeHtmlTags($input ,'a');
			$input = Util::removeHtmlTags($input ,'br');
			$expectedResult = $this->getFileLine('test/expectedResults/Util/stringsWithHtmlTags_expRes.txt', $x);
			$this->assertEqual($expectedResult, $input);
		}
	}

	/**
	 * This function tests the replaceWikiLinks function in the Util class.
	 * It uses the testData in testUtilStrings_wikiLinks.txt and compares the
 	 * output from the replaceWikiLinks function with the expected results in
	 * the testUtilStrings_wikiLinks_expectedResults.txt
	 */
	function testReplaceWikiLinks() {
		$rows = count(file('test/pageSources/stringsWithWikiLinks.txt'));
		for ($x=0;$x<$rows;$x++) {
			$input = $this->getFileLine('test/pageSources/stringsWithWikiLinks.txt', $x);
			$input = Util::replaceWikiLinks($input);
			$expectedResult = $this->getFileLine('test/expectedResults/Util/stringsWithWikiLinks_expRes.txt', $x);
			$this->assertEqual($expectedResult, $input);
		}
	}

	/**
	 * This function tests the removeComments function in the Util class.
	 */
	function testRemoveComments() {
		$input = Util::removeComments('abc <!-- comment --> <tag> def </tag> <!-- comment --> ghi');
		// QUESTION: should the whitespace removed?
		$expectedResult = 'abc<tag> def </tag>ghi';
		$this->assertEqual($expectedResult, $input);
	}

	/**
	 * This function tests the removeHtmlComments function in the Util class.
	 */
	function testRemoveHtmlComments() {
		$input = Util::removeHtmlComments('abc <!-- comment --> <tag> def </tag> <!-- comment --> ghi');
		$expectedResult = 'abc  comment  <tag> def </tag>  comment  ghi';
		$this->assertEqual($expectedResult, $input);
	}

	/**
	 * This function tests the removeWikiEmphasis function in the Util class.
	 */
	function testRemoveWikiEmphasis() {
		$input = Util::removeWikiEmphasis("'''''''abc''''' '''def''' ''ghi''''");
		// QUESTION: what is with '''' and ' ?
		$expectedResult = 'abc def ghi';
		$this->assertEqual($expectedResult, $input);
	}
}

