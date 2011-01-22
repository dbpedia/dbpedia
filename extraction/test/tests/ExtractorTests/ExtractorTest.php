<?php
/**
 * Extractor Test. Template Class for extractor Tests.
 * This class does not work by itself, but needs a derived class, including the function
 * createExtratcor() => returning an instance of the specific extractor
 *
 * @author	Piet Hensel
 *
 */

class ExtractorTest extends UnitTestCase {

	protected $extractor;
	protected $extractorName;
	protected $language;

	const resourceName = 'testresource'; // The standard resource name which will appear in triples if no page ID is passed to extractPage();

	function __construct($language="en") {
		$this->language = $language;
		$this->extractor =  $this->createExtractor();
		$this->extractorName = get_class($this->extractor);
		$this->extractor->start($this->language);
		$this->UnitTestCase();
	}

	/**
	 * Extracts a given InputString and returns the Extraction Result as NTriples
	 * @param pageID: Optional, the Wikipedia PageID
	 * @param pageTitle: Optional, the Wikipedia Pagetitle
	 * @param input: A String containig the MediWiki-Code to extract
	 * @return: A String containing the NTriples written by the Extractor
	 */

	function extractPage($input, $pageID="http://dbpedia.org/resource/testresource",$pageTitle="testresource") {
    		$results = $this->extractor->extractPage($pageID,$pageTitle,$input)->getTriples();
    		$resultString = "";
    		foreach($results as $result) {
    			$resultString .= $result->toNtriples();
    		}
    		return $resultString;
   	}

    function testLoadExternalTestCases() {
        // Get all pages from the files stored in "pageSources" and "expectedResults".
        $sourcePath = "test/pageSources/";
        $resultPath = "test/expectedResults/".$this->extractorName."/";
        foreach ( glob( $sourcePath."en-*.txt") as $inputFilename) {
            $outputFilename = $resultPath.basename($inputFilename);
            if ( file_exists($outputFilename) ) {
                preg_match('~^en-(.*).txt$~', basename($inputFilename), $match);
                $pageID = $match[1];
                $this->assertEqual(
                trim($this->extractPage(file_get_contents($inputFilename),$pageID,basename($inputFilename))),
                trim(file_get_contents($outputFilename))
                );
            }
        }
    }
}

