<?php
/**
 * The DumpTest class is used by the SimpleTest PHP unit tester.
 * It is a PHP unit test and web test framework. (http://simpletest.org)
 * 
 * @author	Paul Kreis (FU Berlin) <mail@paulkreis.de>
 * 
 */
require_once('FileManager.php');

//set_time_limit(300);
//ini_set('memory_limit', '20000M');


class DumpTest extends UnitTestCase {

	/**
	 * This function tests the replaceWikiLinks function in the Util class.
	 * It uses the testData in testUtilStrings_wikiLinks.txt and compares the 
 	 * output from the replaceWikiLinks function with the expected results in
	 * the testUtilStrings_wikiLinks_expectedResults.txt
	 */
	function testCompareLines() {

		// LOADTIME
		$time = microtime();
		$time = explode(' ', $time);
		$time = $time[1] + $time[0];
		$start = $time;

		$handleInput = fopen ('../testData/2009-01-15-english-infobox.nt', 'r');
		$handleExpRes = fopen ('../testData/2009-01-06-english-infobox.nt', 'r');

		$x=1;
		while (!feof($handleInput) && !feof($handleExpRes) && $x < 1001) {
			$bufferInput = FileManager::getNextFileLine($handleInput);
			$bufferExpRes = FileManager::getNextFileLine($handleExpRes);
			$this->assertEqual($bufferExpRes, $bufferInput, 'Line:'.$x);
			echo 'Input: ';
			var_dump(htmlspecialchars($bufferInput));
			echo '<br />ExpRes: ';
			var_dump(htmlspecialchars($bufferExpRes));
			echo '<br />';
			$x++;
		}
		fclose ($handleInput);
		fclose ($handleExpRes);

		// LOADTIME
		$time = microtime();
		$time = explode(' ', $time);
		$time = $time[1] + $time[0];
		$finish = $time;
		$total_time = round(($finish - $start), 4);
		echo '<p>Page generated in '.$total_time.' seconds</p>';
	}

}

