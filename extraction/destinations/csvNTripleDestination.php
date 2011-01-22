<?php

/**
 * Prints out triples as comma seperated values file
 * 
 * 
 */
class csvNTripleDestination implements Destination {
	private $DumpFileA;
	private $DumpFileB;
	private $delimiter;	
	private $FileNameA;
	private $FileNameB;
	private $counter;

	public function __construct($filename) {
		$this->FileNameA = $filename.".nt";
		$this->FileNameB = $filename.".csv";
		$this->counter = 0;
	}

	public function start() { 
	$this->delimiter = "\t";
		//datei oeffnen mit fopen und Parameter w fuer Write mit Dateianlegen
		$this->DumpFileA = fOpen($this->FileNameA,"a");
		$this->DumpFileB = fOpen($this->FileNameB,"a");
		}

	public function accept($extractionResult) {
		// $extractedTriples = new ArrayObject($extractionResult->getTriples());
		$extractedTriples = $extractionResult->getTriples();
		foreach ($extractedTriples as $triple) {
			
			// TODO: make sure that https://sourceforge.net/tracker/?func=detail&aid=2901137&group_id=190976&atid=935520 is fixed

			$tString=$triple->toString();
			fWrite($this->DumpFileA, $tString);
			
			$tripleString = explode(">",$triple->toStringNoEscape());	
			$s = trim(str_replace("<","",$tripleString[0]));
			$s=preg_replace('~^'.DB_RESOURCE_NS.'~',"",$s);
			$p = trim(str_replace("<","",$tripleString[1]));
			$p=preg_replace('~^'.DB_PROPERTY_NS.'~',"",$p);
			if (preg_match('/^<http:/',$tripleString[2]))
				$object_is='r';
			else
				$object_is='l';
			$o = trim(str_replace("<","",$tripleString[2]));
			$dtypePos = strpos($o, "^^");
			$langPos = strpos($o, "@");
			if ( $dtypePos ) $o = substr($o, 0,$dtypePos);
			if ( $langPos ) $o = substr($o, 0,$langPos);
			$o = preg_replace('/(^")|("$)/',"",$o);
			$o=preg_replace('~^'.DB_RESOURCE_NS.'~',"",$o);
			
			#if ( !preg_match('/^[0-9\.,]+$/', $o) ) $o = "\"" . $o . "\"";
			
			#print("\"" . $s . "\"" . $this->delimiter . "\"" . $p . "\"" .  $this->delimiter . $o . "\n");
			 //triple in Datei schreiben
			fWrite($this->DumpFileB, $s . $this->delimiter . $p . $this->delimiter . $o . $this->delimiter . $object_is . "\n");
		}
		$this->counter ++;
		if($this->counter % 1000 == 0) {
			echo $this->counter, PHP_EOL;
		}
	}
	
	public function finish() { 
		fClose($this->DumpFileA);
		fClose($this->DumpFileB);
	}
}

