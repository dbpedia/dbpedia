<?php

/**
 * Writes Triples to the console
 * 
 */

class SimpleDumpDestination implements Destination {
	
	
    public function start() { }
    public function accept($extractionResult) {
		
        foreach (new ArrayObject($extractionResult->getTriples()) as $triple) {
            print($triple->toString());
			
/*
 * 			not working, because og missing oaiId, this is the page id
			if(Options::getOption('generateOWLAxiomAnnotations')){
					$annotations = $triple->getOWLAxiomAnnotations();
					Statistics::increaseCount( 'Total','createdAnnotations', count($annotations));
					if(count($annotations)>0){
						foreach($annotations as $one){
							
							print ($one->toString());
						}
					}
				}//endif
*/
        }//end foreach
    }
    public function finish() { }
}

