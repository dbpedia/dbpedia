
<?php

/**
 * Generates valid blanknodes from a given String
 * 
 */

class RDFblankNode implements RDFnode {
    private $label;
    public function __construct($label) { 
		$this->label = $label; 
		}
		
	public function myValidate(){
		if(strpos($this->label,'/')!==false || strpos($this->label,'%')){
			Logger::warn('bnode labels should not contain / or : or %, but i will patch it for now (converting to _)');
			//shouldnot start with number
			$this->label = str_replace('/','_',
					str_replace('%','_',
					str_replace(':','_',
					$this->label)));
			}
			return true;
		}	
		
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
	public function toCSV() { return "_:{$this->label}"; }
	
	 public function toSPARULPattern($storespecific = VIRTUOSO) {
			return str_replace('%','_',$this->toNTriples());
			//return '?'.$this->label;
     }
	
}

