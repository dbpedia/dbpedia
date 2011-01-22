<?php

/**
 * This class constructs RDFtriples.
 * 
 */

class RDFtriple {
    static function page($pageID) {
        return new URI("http://www4.wiwiss.fu-berlin.de/wikicompany/resource/" . URI::wikipediaEncode($pageID));
    }
    static function URI($uri) {
        return new URI($uri);
    }
    static function predicate($predicate) {
        return new URI("http://dbpedia.org/property/$predicate");
    }
    static function blank($label) {
    	return new RDFblankNode($label);
    }
    static function literal($value, $datatype = null, $lang = null) {
       return new RDFliteral($value, $datatype, $lang);	
    	
    		
    }
    // TODO

    private $subject;
    private $predicate;
    private $object;
    function __construct($subject, $predicate, $object) {
        $this->subject = $subject;
        $this->predicate = $predicate;
        $this->object = $object;
    }
    function toNTriples() {
        return $this->subject->toNTriples() . " " . 
                $this->predicate->toNTriples() . " " . 
                $this->object->toNTriples() . " .\n";
    }
    function toString() {
        return $this->toNTriples();
    }
	
}


