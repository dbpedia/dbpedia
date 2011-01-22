<?php

/**
 * Prints out triples as comma seperated values file
 *
 *
 */
class csvNTripleDecodeDestination implements Destination {
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
			$array_1 = array ( '\\', '"', " ", "	" );
			$array_2 = array ( '\\\\', '\"', "_", "_" );

			$subj = urldecode($triple->getSubject());
			for ( $i = 0; $i < count($array_1); $i++ )
			{
				$subj = str_replace ( $array_1[$i], $array_2[$i], $subj );
			}
			$tString = $subj." ";

            $pred = urldecode($triple->getPredicate());
            if (substr($pred, -2) == "_>" || substr($pred, -2) == ">_") {
            	$pred = substr($pred, 0, -2).">";
            }
            for ( $i = 0; $i < count($array_1); $i++ )
			{
				$pred = str_replace ( $array_1[$i], $array_2[$i], $pred );
			}
			$tString .= $pred." ";

            $obj = $triple->getObject();
            if($obj instanceOf RDFliteral){
				$tmp = $obj->getLexicalForm();
				$array_1 = array ( '\\', '"');
				$array_2 = array ( '\\\\', '\"' );
				for ( $i = 0; $i < count($array_1); $i++ )
				{
					$tmp = str_replace ( $array_1[$i], $array_2[$i], $tmp );
				}

                $tString .= "\"".$tmp."\"";
                if ($obj->getDatatype()) {
					$tString .= "^^<".$obj->getDatatype().">";
				}else{
					$lang = $obj->getLanguage();
					if ($lang) {
						$tString .= "@".$obj->getLanguage();
					}
				}
            }else{
            	$obj = urldecode($obj);
            	for ( $i = 0; $i < count($array_1); $i++ )
				{
					$obj = str_replace ( $array_1[$i], $array_2[$i], $obj );
				}
                $tString .= $obj." ";
            }
			fWrite($this->DumpFileA, preg_replace("/\r|\n/s", "", $tString)." .\n");

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
			fWrite($this->DumpFileB, urldecode($s) . $this->delimiter . urldecode($p) . $this->delimiter . urldecode($o) . $this->delimiter . $object_is . "\n");
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

