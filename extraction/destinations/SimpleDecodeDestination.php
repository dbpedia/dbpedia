<?php

/*
 * Transforms Triples back to Korean and writes Triples to the console
 */

class SimpleDecodeDestination implements Destination {

    public function start() { }
    public function accept($extractionResult) {

        foreach (new ArrayObject($extractionResult->getTriples()) as $triple) {
        	$array_1 = array ( '\\', '"', " ", "	" );
			$array_2 = array ( '\\\\', '\"', "_", "_" );

            $subj =  urldecode($triple->getSubject());
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
            print preg_replace("/\r|\n/s", "", $tString)." .\n";
       }
    }


    public function finish() { }
}

