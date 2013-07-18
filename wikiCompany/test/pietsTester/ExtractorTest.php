<?php
/*
 * Created on 22.08.2007
 *
 * Author: piethensel
 * 
 * Subject: Test InfoboxExtractorResults for validity (including correct serialization)
 */
 
 
 // Load Extraction Framework
 
require_once dirname(__FILE__) . '/../extraction/extractTemplates.php';
require_once 'TestWikipedia.php';
require_once 'TestDestination.php';
require_once 'TestUnit.php';
require_once ( dirname(__FILE__) . '/../dbpedia.php');
include ( dirname(__FILE__) . '/../extraction/config.inc.php');
		
function __autoload($class_name) {
    require_once dirname(__FILE__) . '/../' . $class_name . '.php';
}

// Flag, whether current Extraction process should be written to the Console
define ('SHOW_OUTPUT', false);


// Setup the language version of Wikipedia
$language = "en";

// Array holding the corresponding extractor foreach extractor name
$extractorObjects = array(
					"InfoBoxExtractor"=>new InfoboxExtractor(),
					"ShortAbstractExtractor"=>new ShortAbstractExtractor(),
					"LongAbstractExtractor"=>new LongAbstractExtractor(),
					"ImageExtractor" => new ImageExtractor()
					 );

// Array, which will hold the files to extract
$sourceFiles = array();
$resultFiles = array();

foreach ($extractorObjects as $extractor => $extractorObject) {
	// Get all pages from the files stored in "pageSources" ans "expectedResults".
	$sourcePath = dirname(__FILE__)."/pageSources";
	$resultPath = dirname(__FILE__)."/expectedResults/$extractor";

	foreach ( glob( $sourcePath. "/*.txt") as $filename) {
		if ( file_exists($resultPath."/".basename($filename)) ) {
			$sourceFiles[basename($filename)] = basename($filename);
			$resultFiles[$extractorObject->getExtractorID()][basename($filename)] = $resultPath."/".basename($filename);
		}
	}

}

// var_dump($sourceFiles);
// var_dump($resultFiles);


// Instantiate a new ExtractionJob
$job = new ExtractionJob(
       new TestWikipedia($language),
       new ArrayObject($sourceFiles));
		

// Create one ExtractionGroup for each Extractor

foreach ($extractorObjects as $extractor) {
		// Do not use extractors without test files
		if ($resultFiles[$extractor->getExtractorID()]==null) 
		echo "\ncontinue";
		$currentExtractor = $extractor;
		$group = new ExtractionGroup(new TestDestination(SHOW_OUTPUT));
		$group->addExtractor($currentExtractor);
		$job->addExtractionGroup($group);
}


// Execute the ExtractionJob
$manager = new ExtractionManager();
$manager->execute($job);
 


// Cycle over all ExtractionGroups
$testResult = "Logfile for DBpedia Data Extraction (" . date("D M j Y  G:i:s T") . ")\n";

// Cycle over all ExtractionGroups 
foreach ( $job->getExtractionGroups() as $currentGroup ) {
	
	// Cycle over all extractors
	foreach ($currentGroup->getExtractors() as $extractor) {
		$testResult .= "\nResults for: ".$extractor->getExtractorID() . "\n";
		foreach ( $sourceFiles as $key => $page ) {
			// Only get files, which are available for the extractor
			if ( !isset($resultFiles[$extractor->getExtractorID()][$key]) )
				continue;
				
			$currentResult = trim( $currentGroup->getDestination()->getExtractionResult($page) );
			$expectedResult = trim ( file_get_contents($resultFiles[$extractor->getExtractorID()][$key]));
						
			$testUnit = new TestUnit($currentResult,$expectedResult);
	
			
			$testResult .= "\n$page: ";
			if ( $testUnit->compare() )
				$testResult .= "PASSED.";
			else
				$testResult .= "FAILED.\nErrors: " . $testUnit->getErrorCount() 
				. "\n" . $testUnit->getErrors();
			
		}
	}
}

echo $testResult;
$testResultHTML = 	"<html>\n" .
					"<head>\n<style>\n" .
					"body { font-family:sans-serif; font-size:13px; }\n" .
					".passed {color:green;}\n" .
					".failed {color:red;}\n" .
					"</style>\n" .
					"<title>DBpedia Extraction Test".date("D M j Y  G:i:s T")."</title>\n</head>\n<body>";
					
$testResultHTML .= str_replace("\n","<br />", htmlspecialchars($testResult) );
$testResultHTML = str_replace("PASSED","<span class=\"passed\">PASSED</span>", $testResultHTML);
$testResultHTML = str_replace("FAILED","<span class=\"failed\">FAILED</span>", $testResultHTML);
$testResultHTML .= "\n</body>\n</html>";
file_put_contents(dirname(__FILE__)."/testlog.html",$testResultHTML); 

 
