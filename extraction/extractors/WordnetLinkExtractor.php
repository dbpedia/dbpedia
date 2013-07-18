<?php

class WordnetLinkExtractor extends Extractor {
	private $DBlink;

	//this array holds all classes for one Article to
	private $ClassArray = array();

	public function start($language) {
		$this->language = $language;
		include ("databaseconfig.php");

		// Todo: Database Prefix in Config
		$wikiDB = $dbprefix.$this->language;

		$this->DBlink = mysql_connect($host, $user, $password, true)
		or die("No database connection established: " . mysql_error());
		//echo "Verbindung zum Datenbankserver erfolgreich" . "\n";
		mysql_select_db($wikiDB, $this->DBlink) or die("WordnetLinkExtractor: Database selection failed.\n");

		mysql_query("SET NAMES utf8", $this->DBlink);

	}

	public function extractPage($pageID, $pageTitle,  $pageSource) {
		$result = new ExtractionResult($pageID, $this->language, $this->getExtractorID());

		if ($this->language == "en") {
			$query = "select wsl.url from templatelinks tl inner join page p on p.page_id = tl.tl_from
						inner join dbpedia_develop.wordnet_mapping wm on tl.tl_title = wm.infobox
						inner join dbpedia_develop.wordnet_synsets_links wsl on wm.ID1 = wsl.synset30ID
						where p.page_title = '" .  mysql_escape_string($pageID) . "' and p.page_namespace = 0";
			$queryresult = mysql_query($query, $this->DBlink) or die("Query failed:\n$query\n" .  mysql_error());

			while ($row = mysql_fetch_array($queryresult, MYSQL_ASSOC)) {
				$result->addTriple(
				RDFtriple::page($pageID),
				RDFtriple::predicate("wordnet_type"),
				RDFtriple::URI($row["url"] ));
			}

		}

		return $result;
	}

	function camel( $in, $delim ) {
		define( "USE_UNDERSCORE", false );

		$parts = explode( $delim, $in );
		$out = "";
		foreach( $parts as $k => $w ) {
			if ( USE_UNDERSCORE && $k != 0 )
			$out .= "_";
			$out .= strtoupper( $w[0] ) . substr( $w, 1 );
		}
		return $out;
	}
}


