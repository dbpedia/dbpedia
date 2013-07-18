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
        }
    }
    public function finish() { }
}

