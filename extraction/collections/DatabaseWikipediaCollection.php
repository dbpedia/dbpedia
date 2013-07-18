<?php

/**
 * Connects to a local Wikipedia-Database-Dump and returns the pagesource
 * for a specific page id. See ArticlesSqlIterator.php if you want to 
 * extract the whole DBpedia Dataset
 */

class DatabaseWikipediaCollection implements PageCollection {
		
	private /* final */ /* MySQL */ $mysql;
	
    private /* final */ /* string */ $language;
    
	public function __construct($language) {
		require ("databaseconfig.php");
		$this->mysql = new MySQL($host, $user, $password, $dbprefix.$language);
        $this->language = $language;
    }
    
    public function getLanguage() {
        return $this->language;
    }
    
    public function getSource($pageID) {

		$PageNamespace = 0;
		$PageTitle = $pageID;
		if (preg_match("/Category:(.*)/", $pageID, $match)) {
			$PageNamespace = 14;
			$PageTitle = str_replace("Category:","",$PageTitle);
		}
		
		// if $pageID starts with Template: (added in TemplatesSqlIterator)
		// the pageNamespace is 10
		if (strpos($pageID, "Template:") === 0) {
			$PageNamespace = 10;
			$pageID = substr($pageID, 9); // "Template:" has 9 characters
			$PageTitle = $pageID;
		}

		$query_lang = "select old_text from text t inner join page p on (p.page_latest = t.old_id) where p.page_title = '" . mysql_escape_string($PageTitle) . "' and page_namespace = $PageNamespace ";
		$result_lang = $this->mysql->query($query_lang);
		
		while ($row = mysql_fetch_array($result_lang, MYSQL_ASSOC)) {
			$returnString = $row["old_text"];
		}
		
		if(isset($returnString))
			return $returnString;
		else
			return '';
    }
}


