<?php

/**
 * Connects to a local Wikipedia-Database-Dump and returns the pagesource
 * for a specific page id. See AllArticlesSqlIterator.php if you want to 
 * extract the whole DBpedia Dataset
 */

class DatabaseWikipedia implements PageCollection {

		
    private $language;
	private $DBlink;
	
    public function __construct($language) {
			include ("extraction/config.inc.php");
        $this->language = $language;
		$this->DBlink = mysql_connect($host, $user, $password, true)
			or die("Keine Verbindung moeglich: " . mysql_error());
		
    }
    public function getLanguage() {
        return $this->language;
    }
    public function getSource($pageID) {
		$returnString = null;

		// Todo: Database Prefix in Config
		$wikiDB = "wikiCompany";

		$PageNamespace = 0;
		$PageTitle = $pageID;
		if (preg_match("/Category:(.*)/", $pageID, $match))
		{
			$PageNamespace = 14;
			$PageTitle = str_replace("Category:","",$PageTitle);
		}
		

		//echo "Verbindung zum Datenbankserver erfolgreich" . "\n";
		mysql_select_db($wikiDB, $this->DBlink) or die("Auswahl der Datenbank fehlgeschlagen");

		mysql_query("SET NAMES utf8", $this->DBlink);
		// $query_lang = "select old_text from wc_text t inner join wc_page p on (p.page_latest = t.old_id) where p.page_title = '" . mysql_escape_string($PageTitle) . "' and page_namespace = $PageNamespace; ";
		$query_lang = "select cur_text from wc_cur where cur_title = '" . mysql_escape_string($PageTitle) . "' and cur_namespace = $PageNamespace; ";
		$result_lang = mysql_query($query_lang, $this->DBlink) or die("Anfrage fehlgeschlagen: " . mysql_error());
		
		while ($row = mysql_fetch_array($result_lang, MYSQL_ASSOC)) 
		{
			$returnString = $row["cur_text"];
		}
	
		return $returnString;

    }
    
}


