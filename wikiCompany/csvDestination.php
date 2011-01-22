<?php

/**
 * Prints out triples as comma seperated values
 * 
 * 
 */
class csvDestination implements Destination {

	private $delimiter;	

    public function start() { 
		$this->delimiter = "\t";
		}

    public function accept($extractionResult) {
		foreach (new ArrayObject($extractionResult->getTriples()) as $triple) {
			$tripleString = explode(">",$triple->toString());
			$s = trim(str_replace("<","",$tripleString[0]));
			$p = trim(str_replace("<","",$tripleString[1]));
			$o = trim(str_replace("<","",$tripleString[2]));
			$dtypePos = strpos($o, "^^");
			$langPos = strpos($o, "@");
			if ( $dtypePos ) $o = substr($o, 0,$dtypePos);
			if ( $langPos ) $o = substr($o, 0,$langPos);
			$o = preg_replace('/(^")|("$)/',"",$o);
			if ( !preg_match('/^[0-9\.,]+$/', $o) ) $o = "\"" . $o . "\"";
			
			print("\"" . $s . "\"" . $this->delimiter . "\"" . $p . "\"" .  $this->delimiter . $o . "\n");
		}	
    }
    
    public function finish() { 
		return null;
	}
}

