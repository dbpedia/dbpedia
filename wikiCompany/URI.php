<?php
/**
 * Generates valid URIs (tests if a string is a valid URI before)
 * 
 */
class URI implements RDFnode {
    private $uri;
    public function __construct($uri) { 
        if (!$this->validate($uri)) {
            trigger_error("Not a valid URI: '$uri'", E_USER_ERROR);
        }
    	$this->uri = $uri;
    }

    public function isURI() { return true; }
    public function isBlank() { return false; }
    public function isLiteral() { return false; }
    public function getURI() { return $this->uri; }
    public function getBlankNodeLabel() { return null; }
    public function getLexicalForm() { return null; }
    public function getLanguage() { return null; }
    public function getDatatype() { return null; }
    public function toNTriples() { return "<{$this->uri}>"; }
    public function toString() { return $this->toNTriples(); }
	
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

    /** Catches some kinds of malformed URIs.
        @param string $uri A candidate string
        @return boolean true if it looks like a valid URI
    */
    public static function validate($uri) {
        // Check if the string starts with a valid scheme name, and the rest only
        // contains characters that are allowed in URIs.
        // Also checks for correct %-encoding.
        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9+.-]*:([-a-zA-Z0-9_.~!*\'();:@&=+$,\/?#[\]]|%[a-fA-F0-9]{2})*$/', $uri)) {
            return false;
        }
        // We could do more syntax validation here for specific schemes ...
        return true;
    }
}

