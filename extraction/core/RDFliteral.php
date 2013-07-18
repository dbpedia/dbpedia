<?php

/**
 * Generates RDF literals.
 * Needs a String as input, otherwise an Error will be thrown.
 * Optionally, language and datatype can be added to the literal
 * 
 */

class RDFliteral implements RDFnode {
    private $lexicalForm;
    private $datatypeURI;
    private $language;
	private $ntriple = null;
	private $SPARULpattern = null;
	
    public function __construct($lexicalForm, $datatypeURI = null, $language = null) {
       	//trigger_error("Not a string: \"$lexicalForm\" (".gettype($lexicalForm).")", E_USER_ERROR);
        $this->lexicalForm = $lexicalForm;
        $this->datatypeURI = $datatypeURI;
		// make sure that the language code uses '-', RDF requires it (see RFC 3066)
        if (isset($language)) $this->language = str_replace('_', '-', $language);
    }
    public function isURI() { return false; }
    public function isBlank() { return false; }
    public function isLiteral() { return true; }
    public function getURI() { return null; }
    public function getBlankNodeLabel() { return null; }
    public function getLexicalForm() { return $this->lexicalForm; }
    public function getLanguage() { return $this->language; }
    public function getDatatype() { return $this->datatypeURI; }

	public function myValidate() {
			if ( !is_string($this->lexicalForm) ) {
          	  throw new Exception("RDFliteral: Not a string: \"".$this->lexicalForm."\" (".gettype($this->lexicalForm).")", E_USER_ERROR);
			}
			return true;
		}

/*
 * 
 * name: equals
 * @param
 * @return
 */
	public function equals($literal){
			if(!($literal instanceOf RDFliteral)){
				return false;
				}
			
			return 	($this->toNTriples() == $literal->toNTriples());
		
		}


/*
 * lazy initialization and cache
 * returns ntriple form
 * name: toNTriples
 * @param
 * @return
 */
    public function toNTriples() {
		if(is_null($this->ntriple)){
    	
			if (is_null($this->datatypeURI) && is_null($this->language)) {
				$this->ntriple =  "\"" . RDFliteral::escape($this->lexicalForm) . "\"";
			}
			else if (is_null($this->datatypeURI)) {	
				$this->ntriple =  "\"" . RDFliteral::escape($this->lexicalForm) . "\"@$this->language";
			}
			else {
				$this->ntriple =  "\"" . RDFliteral::escape($this->lexicalForm) . 
				"\"^^<{$this->datatypeURI}>";
			}
		}
		return $this->ntriple;
    	
    }
	
	
	 public function toSPARULPattern() {
		Timer::start('RDFliteral::toSPARULPattern');
		if(is_null($this->SPARULpattern)){
			$storespecific = Options::getOption('Store.SPARULdialect');
			$quotes = ($storespecific== VIRTUOSO)?'"""':'"';

			if (is_null($this->datatypeURI) && is_null($this->language)) {
				$this->SPARULpattern = $quotes . RDFliteral::escape($this->lexicalForm) . $quotes;
			}
			else if (is_null($this->datatypeURI)) {	
				$this->SPARULpattern = $quotes . RDFliteral::escape($this->lexicalForm) . $quotes."@$this->language";
			}
			else {
				$this->SPARULpattern = $quotes . RDFliteral::escape($this->lexicalForm) . 
				$quotes."^^<{$this->datatypeURI}>";
			}
			
    	}
		Timer::stop('RDFliteral::toSPARULPattern');
		return $this->SPARULpattern;
    }
	
    public function toCSV() {
    	
    	if (is_null($this->datatypeURI) && is_null($this->language)) {
       		return RDFliteral::$this->lexicalForm . "\"";
    	}
    	else if (is_null($this->datatypeURI)) {	
    		return RDFliteral::$this->lexicalForm . "\"@$this->language";
    	}
    	else {
    		return RDFliteral::$this->lexicalForm . 
            "\"^^<{$this->datatypeURI}>";
    	}
    	
    }
    public function toString() { return $this->toNTriples(); }
    
    const error_character = '\\uFFFD';

    // Input is an UTF-8 encoded string. Output is the string in N-Triples encoding.
    // Checks for invalid UTF-8 byte sequences and replaces them with \uFFFD (white
    // question mark inside black diamond character)
    //
    // Sources:
    // http://www.w3.org/TR/rdf-testcases/#ntrip_strings
    // http://en.wikipedia.org/wiki/UTF-8
    // http://www.cl.cam.ac.uk/~mgk25/ucs/examples/UTF-8-test.txt
    private static function escape($str) {
        // Replaces all byte sequences that need escaping. Characters that can
        // remain unencoded in N-Triples are not touched by the regex. The
        // replaced sequences are:
        //
        // 0x00-0x1F   non-printable characters
        // 0x22        double quote (")
        // 0x5C        backslash (\)
        // 0x7F        non-printable character (Control)
        // 0x80-0xBF   unexpected continuation byte, 
        // 0xC0-0xFF   first byte of multi-byte character,
        //             followed by one or more continuation byte (0x80-0xBF)
        //
        // The regex accepts multi-byte sequences that don't have the correct
        // number of continuation bytes (0x80-0xBF). This is handled by the
        // callback.
        return preg_replace_callback(
                "/[\\x00-\\x1F\\x22\\x5C\\x7F]|[\\x80-\\xBF]|[\\xC0-\\xFF][\\x80-\\xBF]*/",
                array('RDFliteral', 'escape_callback'),
                $str);
    }

    private static function escape_callback($matches) {
        $encoded_character = $matches[0];
        $byte = ord($encoded_character[0]);
        // Single-byte characters (0xxxxxxx, hex 00-7E)
        if ($byte == 0x09) return "\\t";
        if ($byte == 0x0A) return "\\n";
        if ($byte == 0x0D) return "\\r";
        if ($byte == 0x22) return "\\\"";
        if ($byte == 0x5C) return "\\\\";
        if ($byte < 0x20 || $byte == 0x7F) {
            // encode as \u00XX
            return "\\u00" . sprintf("%02X", $byte);
        }
        // Multi-byte characters
        if ($byte < 0xC0) {
            // Continuation bytes (0x80-0xBF) are not allowed to appear as first byte
            return RDFliteral::error_character;
        }
        if ($byte < 0xE0) { // 110xxxxx, hex C0-DF
            $bytes = 2;
            $codepoint = $byte & 0x1F;
        } else if ($byte < 0xF0) { // 1110xxxx, hex E0-EF
            $bytes = 3;
            $codepoint = $byte & 0x0F;
        } else if ($byte < 0xF8) { // 11110xxx, hex F0-F7
            $bytes = 4;
            $codepoint = $byte & 0x07;
        } else if ($byte < 0xFC) { // 111110xx, hex F8-FB
            $bytes = 5;
            $codepoint = $byte & 0x03;
        } else if ($byte < 0xFE) { // 1111110x, hex FC-FD
            $bytes = 6;
            $codepoint = $byte & 0x01;
        } else { // 11111110 and 11111111, hex FE-FF, are not allowed
            return RDFliteral::error_character;
        }
        // Verify correct number of continuation bytes (0x80 to 0xBF)
        $length = strlen($encoded_character);
        if ($length < $bytes) { // not enough continuation bytes
            return RDFliteral::error_character;
        }
        if ($length > $bytes) { // Too many continuation bytes -- show each as one error
            $rest = str_repeat(RDFliteral::error_character, $length - $bytes);
        } else {
            $rest = '';
        }
        // Calculate Unicode codepoints from the bytes
        for ($i = 1; $i < $bytes; $i++) {
            // Loop over the additional bytes (0x80-0xBF, 10xxxxxx)
            // Add their lowest six bits to the end of the codepoint
            $byte = ord($encoded_character[$i]);
            $codepoint = ($codepoint << 6) | ($byte & 0x3F);
        }
        // Check for overlong encoding (character is encoded as more bytes than
        // necessary, this must be rejected by a safe UTF-8 decoder)
        if (($bytes == 2 && $codepoint <= 0x7F) ||
            ($bytes == 3 && $codepoint <= 0x7FF) ||
            ($bytes == 4 && $codepoint <= 0xFFFF) ||
            ($bytes == 5 && $codepoint <= 0x1FFFFF) ||
            ($bytes == 6 && $codepoint <= 0x3FFFFF)) {
            return RDFliteral::error_character . $rest;
        }
        // Check for UTF-16 surrogates, which must not be used in UTF-8
        if ($codepoint >= 0xD800 && $codepoint <= 0xDFFF) {
            return RDFliteral::error_character . $rest;
        }
        // Misc. illegal code positions
        if ($codepoint == 0xFFFE || $codepoint == 0xFFFF) {
            return RDFliteral::error_character . $rest;
        }
        if ($codepoint <= 0xFFFF) {
            // 0x0100-0xFFFF, encode as \uXXXX
            return "\\u" . sprintf("%04X", $codepoint) . $rest;
        }
        if ($codepoint <= 0x10FFFF) {
            // 0x10000-0x10FFFF, encode as \UXXXXXXXX
            return "\\U" . sprintf("%08X", $codepoint) . $rest;
        }
        // Unicode codepoint above 0x10FFFF, no characters have been assigned
        // to those codepoints
        return RDFliteral::error_character . $rest;
    }
}


