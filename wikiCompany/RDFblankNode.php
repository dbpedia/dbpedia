
<?php

/**
 * Generates valid blanknodes from a given String
 * 
 */

class BlankNode implements RDFnode {
    private $label;
    public function __construct($label) { $this->label = $label; }
    public function isURI() { return false; }
    public function isBlank() { return true; }
    public function isLiteral() { return false; }
    public function getURI() { return null; }
    public function getBlankNodeLabel() { return $this->label; }
    public function getLexicalForm() { return null; }
    public function getLanguage() { return null; }
    public function getDatatype() { return null; }
    public function toNTriples() { return "_:{$this->label}"; }
    public function toString() { return $this->toNTriples(); }
}

