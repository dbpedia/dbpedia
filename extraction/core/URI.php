<?php
/**
 * Generates valid URIs (tests if a string is a valid URI before)
 * 
 */

define('URI_MAX_LENGTH', 2000);

class URI implements RDFnode {
    private $uri;
	private $ntriple = null;
	public $doValidation = true;
	
    public function __construct($uri, $doValidation = true) { 
		Timer::start('URI::construct');
		$this->doValidation = ($doValidation && Options::getOption('dbpedia.useURIvalidation'));
		$this->uri = $uri;
		Timer::stop('URI::construct');
		//echo "a:$doValidation,b:".Options::getOption('dbpedia.useURIvalidation').", $uri\n";
	   
/*
		if ($doValidation &&  !(strpos($uri,'oai:')===0) ) {
			//echo "validation of $uri\n{$this->validate($uri)}\n";
			Timer::start('URI::construct::validate');
			if(!$this->validate($uri)){
				Timer::stop('URI::construct::validate');
				Timer::stop('URI::construct');
				throw new Exception('URI: Not a valid URI: '.$uri);
			}
			Timer::stop('URI::construct::validate');
        }
*/
    	
    }

    public function isURI() { return true; }
    public function isBlank() { return false; }
    public function isLiteral() { return false; }
    public function getURI() { return $this->uri; }
    public function getBlankNodeLabel() { return null; }
    public function getLexicalForm() { return null; }
    public function getLanguage() { return null; }
    public function getDatatype() { return null; }
    public function toNTriples() { 
		if(is_null($this->ntriple)){
			$this->ntriple = "<{$this->uri}>";
			}
		return $this->ntriple;
		}
    public function toSPARULPattern() { return $this->toNTriples(); }
	public function toCSV() { return "<{$this->uri}>"; }
    public function toString() { return $this->toNTriples(); }

    public function __toString() { return $this->toString(); }

	public function equals($uri){
			if($uri instanceOf URI && $this->getURI() == $uri->getURI()){
				return true;
			}else {
				return false;
				}
			
		
		}
	
	
	/* Encodes a page title into a form that can be used in Wikipedia URLs or DBpedia URIs, e.g
		"Madonna (entertainer)" to "Madonna_%28entertainer%29". The input can be any
		page title that can be used in internal Wikipedia links, e.g. [[Madonna (entertainer)]].
		Note: [[Madonna_(entertainer)]] works too. */
	public static function wikipediaEncode($page_title) {
		$string = urlencode(str_replace(" ","_",trim($page_title)));
		// Decode slash "/", colon ":", as wikimedia does not encode these
		$string = str_replace("%2F","/",$string);
		$string = str_replace("%3A",":",$string);
		return $string;
	}
	
	public static function validate($uri){
			Timer::start('URI::validate');
			//require and create Instance of PEAR Validate()-class
			require_once("Validate.php");
			$validate = new Validate();
			$bool = $validate->uri($uri);
			Timer::stop('URI::validate');
			return $bool;
		}

    /** Catches some kinds of malformed URIs.
        @param string $uri A candidate string
        @return boolean true if it looks like a valid URI
    */
	public function myValidate() {
		$uri = $this->getURI();
		// skip validation, ignore oai vocab as validation for it doesn't work any how
		if(false == $this->doValidation || (strpos($uri,'oai:')===0)){
			return true;
			}
		
		// Check if the string starts with a valid scheme name, and the rest only
		// contains characters that are allowed in URIs.
		// Also checks for correct %-encoding.
		// very long URIs may crash the RegEx - too long URIs are not valid
		if (strlen($uri)>URI_MAX_LENGTH) {
				throw new Exception('URI: more than '.URI_MAX_LENGTH.' chars '.$uri);
			}
		
		//-----------------------------------------
		//handmade uri validation
		//if (!preg_match('/^[a-zA-Z][a-zA-Z0-9+.-]*:([-a-zA-Z0-9_.~!*\'();:@&=+$,\/?#[\]]|%[a-fA-F0-9]{2})*$/D', $uri)) {
		//	return false;
		//-----------------------------------------

		
		if(!self::validate($uri)) {
			//create and write logfile if uri not accepted 
			try{
				if (is_writeable("log/"))
				{				
					$logFile = "rejectedUris.log";
					$fpointer = fopen($logFile, "a");
					$result = fwrite($fpointer,$uri."\r\n");
					fclose($fpointer);
				}
			}catch(Exception $e){}
			throw new Exception('URI: Not a valid URI: '.$uri);
		}
		// We could do more syntax validation here for specific schemes ...
		return true;
	}
	
	
	
}

