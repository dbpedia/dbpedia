<?php

/**
 * This class constructs RDFtriples.
 * 
 * author: Georgi Kobilarov (FU-Berlin)
 */

class RDFtriple {
    static function page($pageID) {
		
		$encPageID = URI::wikipediaEncode($pageID);

		$returnPageID = strtoupper(substr($encPageID,0,1)) . substr($encPageID,1);
	
        return new URI("http://dbpedia.org/resource/" . $returnPageID);
    }
	
	/**
	* PageID Parameter must be ENCODED!
	**/
	static function resolveRedirect($pageID)
	{
		include ("databaseconfig.php");

		$DBlink = mysql_connect($host, $user, $password, true)
		or die("Keine Verbindung moeglich: " . mysql_error());

		mysql_select_db('dbpedia_extraction', $DBlink) or die("RDFtriple: Auswahl der Datenbank fehlgeschlagen");

		mysql_query("SET NAMES utf8", $DBlink);
		
		$decPageID = str_replace("/","%2F",$pageID);
		$decPageID = str_replace(":","%3A",$decPageID);
		$decPageID = mysql_escape_string(urldecode(str_replace("_"," ",trim($decPageID))));
		
		$redirectquery = "select page_to from redirects where page_from = '$decPageID'";

		$redirectqueryresult = mysql_query($redirectquery, $DBlink) or die("RDFtriple: Anfrage redirectqueryresult fehlgeschlagen: " . mysql_error());
		$row = mysql_fetch_array($redirectqueryresult, MYSQL_ASSOC);
		$pageto = $row['page_to'];
		
		if(isset($pageto))
		{
			$returnPageID = URI::wikipediaEncode($pageto);
		}
		else
		{
			$returnPageID = $pageID;
		}
		
		return $returnPageID;
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
	
	
	
	function getSubject()
	{
	return $this->subject;
	}
	
	function getPredicate()
	{
		return $this->predicate;
	}
	
	function getObject()
	{
		return $this->object;
	}
	
    function __construct($subject, $predicate, $object) {
        $this->subject = $subject;
        $this->predicate = $predicate;
		if(false)//$object->isURI())
		{
				if(preg_match("/http\:\/\/dbpedia.org\/resource\//",$object->getURI()) == 1)
				{		
				$this->object = self::URI("http://dbpedia.org/resource/" . self::resolveRedirect(str_replace("http://dbpedia.org/resource/","",$object->getURI())));
				}
				else
				{
				$this->object = $object;
				}
		}
		else
		{
		 $this->object = $object;
		}
    }
    function toNTriples() {
        return $this->subject->toNTriples() . " " . 
                $this->predicate->toNTriples() . " " . 
                $this->object->toNTriples() . " .\n";
    }
    function toString() {
        return $this->toNTriples();
    }
	function toStringNoEscape() {
		return $this->subject->toNTriples() . 
                $this->predicate->toNTriples() . 
                $this->object->toCSV() . " \n";
	}
	
}


