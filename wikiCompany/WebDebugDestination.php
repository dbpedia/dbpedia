<?php

/**
 * This Destination presents extraction results on a web interface.
 * Requires RAP - Rdf API for PHP
 * 
 */

class WebDebugDestination implements Destination {
    
    
    public function start() { 
		}
    public function accept($extractionResult) {
        $model1 = ModelFactory::getDefaultModel( "http://dbpedia.org/" ); // RAP model
 		$count = 0;
		foreach (new ArrayObject($extractionResult->getTriples()) as $triple) {
          		$count++;
			$tripleString = explode(">",$triple->toString());
			$s = str_replace("<","",$tripleString[0]);
			$p = str_replace("<","",$tripleString[1]);
			// $s = preg_replace("/<|>/","",$triple->getSubject());
			// $p = preg_replace("/<|>/","",$triple->getPredicate());
			$o = $tripleString[2];
			
			$subject = new Resource($s);
			$predicate = new Resource($p);
			
			if ( strpos($o,"<") ) {
				// echo "<br>" . $s. $p . $p;
				if ( !strpos($o,"^^") ) {
					// echo " a";
					$o = str_replace("<","",$o);
					$object = new Resource($o);
				} else {
					// echo " b";
					$pos = strpos($o,"^^");
					$literal = substr($o,0,$pos);
					$object = new Literal($literal);
				 	$object->setDatatype(substr($o,$pos+3,strlen($o)-$pos-3));
				}
			 } else {
					// $lang = "en";
					if ( preg_match("/(.*)(@)([a-zA-Z]+) \.$/",$o,$match) ) {
						$o = $match[1];
						$lang = $match[3];
						$object = new Literal($o,$lang);
					} else
						$object = new Literal($o);
			} 
			
			$statement = new Statement($subject, $predicate, $object);
			$model1->add($statement);
        }
		if ($count > 0) {
			echo "<br><br><h3>". $extractionResult->getExtractorID() . "</h3>"; 
			echo $model1->writeAsHtmlTable();
		}
    }
    public function finish() { 
		
		// $model1->writeAsHtmlTable();
	}
	
	
	
}

