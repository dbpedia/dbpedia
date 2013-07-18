<?php
/*
 * Created on 27.08.2007
 *
 * Author: piethensel
 * 
 * Subject: Extractor Test. Template Class for extractor Tests.
 * This class does not work by itself, but needs a derived class, including the function
 * createExtratcor() => returning an instance of the specific extractor
 */

class ExtractorTest extends UnitTestCase {
	
	protected $extractor;	
	protected $extractorName; 
	protected $language; 
	
	const resourceName = 'testResource'; // The standard resource name which will appear in triples if no page ID is passed to extractPage();
		
	function __construct($language="en") {
		$this->language = $language;
		$this->extractor =  $this->createExtractor();
		$this->extractorName = get_class($this->extractor);
		$this->extractor->start($this->language);
		$this->UnitTestCase();
	}

	/**
	 * Extracts a given InputString and returns the Extraction Result as NTriples
	 * @param input: A String containig the MediWiki-Code to extract
	 * @param pageID: Optional, the Wikipedia PageID
	 * @param pageTitle: Optional, the Wikipedia Pagetitle
	 * @return: A String containing the NTriples written by the Extractor
	 */ 
	 
	function extractPage($input,$pageID="testResource",$pageTitle="testResource") {
    		$results = $this->extractor->extractPage($pageID,$pageTitle,$input)->getTriples();
    		$resultString = "";
    		foreach($results as $result) {
    			$resultString .= $result->toNtriples();
    		}
    		return $resultString;
   	}
		
	/**
	 * Creates test cases from files:
	 * $sourcePath: Location of page sources containing MediaWiki code to extract
	 * $expectedResults: Location of Textfiles containing the Expected Results.
	 * (Files must be stored in expectedResults/<ExtractorName>)
	 * 
	 * @return: void
	 */	
	function testLoadExternalTestCases() {
		// Get all pages from the files stored in "pageSources" ans "expectedResults".
		$sourcePath = dirname(__FILE__)."/../pageSources/";
		$resultPath = dirname(__FILE__)."/../expectedResults/".$this->extractorName."/";
		foreach ( glob( $sourcePath."*.txt") as $inputFilename) {
			$outputFilename = $resultPath.basename($inputFilename);
			if ( file_exists($outputFilename) ) {
				$this->assertEqual(
					trim($this->extractPage(file_get_contents($inputFilename),basename($inputFilename),basename($inputFilename))),
					trim(file_get_contents($outputFilename))
				);
			}
		}

	}	
	
	

}

