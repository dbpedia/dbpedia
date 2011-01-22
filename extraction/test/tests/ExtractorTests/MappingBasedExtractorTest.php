<?php
/**
 * Unit Test for the GenericExtractor
 *
 * @author	Paul Kreis <mail@paulkreis.de>
 *
 */

class MappingBasedExtractorTest extends ExtractorTest {

    function createExtractor() {
        return new MappingBasedExtractor();
    }

    //	function testEncoding() {
    //		$output = $this->extractPage(file_get_contents('test/pageSources/en-Berlin.txt'), "Berlin","Berlin");
    //		$expected = file_get_contents('test/expectedResults/GenericExtractor/en-Berlin.txt');
    //		//echo '<br>OUTPUT: '.htmlspecialchars($output);
    //		//echo '<br>EXPECTED: '.htmlspecialchars($expected).'<br>';
    //		$this->assertEqual(trim($output),trim($expected));
    //	}

    /**
	 * Creates test cases from files:
	 * $sourcePath: Location of page sources containing MediaWiki code to extract
	 * $expectedResults: Location of Textfiles containing the Expected Results.
	 * (Files must be stored in expectedResults/<ExtractorName>)
	 *
	 * @return: void
	 */

}


