<?php

/**
 * Defines the interface Destination.
 * Destinations in the DBpedia framework are used to
 * store the triples or print them out.
 * 
 */

interface Destination {

    public function start();
    public function accept($extractionResult);
    public function finish();
}


