<?php
/**
 * Writes Triples to NTriple files.
 * 
 */
class NTripleDumpDestination implements Destination {
	private $DumpFile;
	private $FileName;
	private $counter;
		
	public function __construct($filename) {
		$this->FileName = $filename;
		$this->counter = 0;
	}
	
	public function start() { 
		$this->DumpFile = fOpen($this->FileName,"a");
	}
	
	
	public function accept($extractionResult) {
		foreach (new ArrayObject($extractionResult->getTriples()) as $triple) {
			// write triple to file
			fWrite($this->DumpFile, $triple->toString());
			//print($triple->toString());
		}
		
		$this->counter++;
		echo $this->counter . "\n";
	}
	
	public function finish(){ 
		fClose($this->DumpFile);
	}
	

	
}

