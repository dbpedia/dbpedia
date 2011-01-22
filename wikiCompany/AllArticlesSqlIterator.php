<?php
/**
 * The AllArticlesSqlIterator cycles over all Articles
 * in the DBpedia MySQL database. Database settings have to be
 * specified in extraction/config.inc.php.
 * 
 */
class AllArticlesSqlIterator implements Iterator
{
    protected $row = null;
    protected $query = null;

    public function __construct($language)
    {
			include ("extraction/config.inc.php");
			
		// Todo: Database Prefix in Config
		$wikiDB = "wikiCompany";
		$link = mysql_connect($host, $user, $password, true)
			or die("Keine Verbindung moeglich: " . mysql_error());
		//echo "Verbindung zum Datenbankserver erfolgreich" . "\n";
		mysql_select_db($wikiDB, $link);// or die("Auswahl der Datenbank fehlgeschlagen".mysql_error());

		mysql_query("SET NAMES utf8", $link);
	
	if($language == "en") {	
		$query = "select page_title, page_namespace from wc_page where (page_namespace = 0) and page_is_redirect = 0";
	}
        if(is_string($query)) {
            $this->query = mysql_query($query);
        } else if(is_resource($query)) {
            $this->query = $query;
        }
    }

    public function key() { } // Not Implemented

    public function current()
    {
        if($this->row != null)
        {
        $PageTitle =  $this->row['page_title'];
		$PageNamespace = $this->row['page_namespace'];
		if($PageNamespace == 14)
		{
			$PageTitle = "Category:" . $PageTitle;
		}
		return $PageTitle;
        }
    }

    public function next()
    {
        $this->row = mysql_fetch_assoc($this->query);
        $PageTitle =  $this->row['page_title'];
		$PageNamespace = $this->row['page_namespace'];
		if($PageNamespace == 14)
		{
			$PageTitle = "Category:" . $PageTitle;
		}
		return $PageTitle;
    }

    public function rewind()
    {
        $this->row = mysql_data_seek($this->query, 0);
        $PageTitle =  $this->row['page_title'];
		$PageNamespace = $this->row['page_namespace'];
		if($PageNamespace == 14)
		{
			$PageTitle = "Category:" . $PageTitle;
		}
		return $PageTitle;
    }

    public function valid()
    {
        if($this->row == false) {
            return false;
        }

        return true;
    }
	
	
	
	
}

