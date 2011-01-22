<?php

/**
 * Prints out triples as comma seperated values file
 * 
 * 
 */
class csvDestination implements Destination {
	private $DumpFile;
	private $delimiter;	
	private $FileName;
	private $counter;

	public function __construct($filename) {
		$this->FileName = $filename;
		$this->counter = 0;
	}

	public function start() { 
		$this->delimiter = "\t";
		//datei oeffnen mit fopen und Parameter w fuer Write mit Dateianlegen
		$this->DumpFile = fOpen($this->FileName,"a");
		}

	public function accept($extractionResult) {
		foreach (new ArrayObject($extractionResult->getTriples()) as $triple) {
			
			// TODO: make sure that https://sourceforge.net/tracker/?func=detail&aid=2901137&group_id=190976&atid=935520 is fixed

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
			fWrite($this->DumpFile, $s . $this->delimiter . $p . $this->delimiter . $o . $this->delimiter . $object_is . "\n");
		}
		$this->counter ++;
		echo $this->counter . "\n";
	}
	
	public function finish() { 
		fClose($this->DumpFile);
	}
}

