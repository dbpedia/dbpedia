<?php
/**
 * Defines the interface RDFnode.
 * Implementations are RDFliteral, URI, RDFblankNode
 * 
 */
interface RDFnode {
	public function myValidate();
    public function isURI();
    public function isBlank();
    public function isLiteral();
    public function getURI();
    public function getBlankNodeLabel();
    public function getLexicalForm();
    public function getLanguage();
    public function getDatatype();
    public function toNTriples();
	public function toCSV();
}


