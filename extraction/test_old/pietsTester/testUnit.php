<?php
/*
 * Created on 22.08.2007
 *
 * Author: piethensel
 * 
 * Subject:
 */
 
 class TestUnit {
 	
 	private $errorString;
 	private $errorCount;
 	private $currentResult;
 	private $expectedResult;
 	
 	
 	public function __construct($currentResult, $expectedResult) {
 		$this->currentResult = $currentResult;
 		$this->expectedResult = $expectedResult;
 		$this->errorString = "";
 		$this->errorCount = 0;
 	}
 	
 	
 	public function compare() {
	
		$currentResultLines = explode("\n", $this->currentResult);
		$expectedResultLines = explode("\n", $this->expectedResult);
		
		// Compare Number of Lines
		$currentLength = count($currentResultLines);
		$expectedLength = count($expectedResultLines);
	
		if ( $currentLength > $expectedLength ) {
			$this->errorString .= "\nThe current result has too many Lines (" . ($currentLength+1) . " vs. " . ($expectedLength+1) .").\n";
			$this->errorCount++;
		} else if ( $currentLength < $expectedLength ) {
			$this->errorString .= "\nThe current result has too few Lines (" . ($currentLength+1) . " vs. " . ($expectedLength+1) .").\n";
			$this->errorCount++;
		}
	
		// Compare Line by Line
		$maxLines = min( count($currentResultLines), count($expectedResultLines) );
		for ($i=0; $i < $maxLines; $i++ ) {
			if ( $currentResultLines[$i] != $expectedResultLines[$i] ) {
				$this->errorString .= "\nLine " . ($i+1) . ":\n"
									. "Expected:\t\"" . $expectedResultLines[$i] . "\".\n"
									. "Current:\t\"" . $currentResultLines[$i] . "\".\n";
				$this->errorCount++;
			}
		}
		 
		return ( $this->errorCount == 0 );
	} 
 	
 	public function getErrors() {
 		return $this->errorString;
 	}
 	
 	public function getErrorCount() {
 		return $this->errorCount;
 	}
 	
 	
 }
 
