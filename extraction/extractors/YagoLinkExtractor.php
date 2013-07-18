<?php


// THIS EXTRACTOR IS NOW DEPRECATED.
// A DBpedia converter for YAGO was created by Fabian Suchanek and Jens Lehmann,
// which is much better than this one.



//This extractor requires a YAGO database
class YagoLinkExtractor extends Extractor 
{
	
    private $language;
	private $DBlink;
	
	//this array holds all classes for one Article to 
	private $ClassArray = array();

    public function getExtractorID() {
        return self::extractorID;
    }
    public function start($language) {

	
        $this->language = $language;
		
		include ("databaseconfig.php");
			
		// Todo: Database Prefix in Config
		$wikiDB = $dbprefix."yago";
	
		$this->DBlink = mysql_connect($host, $user, $password, true)
			or die("Keine Verbindung m�glich: " . mysql_error());
		//echo "Verbindung zum Datenbankserver erfolgreich" . "\n";
		mysql_select_db($wikiDB, $this->DBlink) or die("YagoLinkExtractor: Auswahl der Datenbank fehlgeschlagen");

		mysql_query("SET NAMES utf8", $this->DBlink);
		
    }
    public function extractPage($pageID, $pageTitle,  $pageSource) {
        $result = new ExtractionResult(
                $pageID, $this->language, self::extractorID);
                
		//Look if the Article has a category
		if(!preg_match("/".WIKIMEDIA_CATEGORY.":/",$pageID,$match))
		{
			//match all categories
			if (preg_match_all("/\[\[".WIKIMEDIA_CATEGORY.":(.*)\]\]/",$pageSource,$matches, PREG_SET_ORDER))
			{
			
			//empty ClassArray
			$this->ClassArray = array();
			
				foreach ($matches as $match)
				{
					//remove the category-label
					$Category = preg_replace("/\|.*/","",$match[1]);
					
					$query = "select Arg2 from facts where Relation = 'subClassOf' and Arg1 = 'wikicategory_" . mysql_escape_string(str_replace(" ","_",$Category)) . "'";
					$queryresult = mysql_query($query, $this->DBlink) or die("Anfrage fehlgeschlagen: " . mysql_error());
			
					while ($row = mysql_fetch_array($queryresult, MYSQL_ASSOC)) 
					{
						$this->ClassArray[$row["Arg2"]] = true;					
					}
				}	

				foreach ( $this->ClassArray as $subject => $bool ) {
		
				$YagoClass = str_replace("wordnet_", "", $subject);
		
					$result->addTriple(
						RDFtriple::page($pageID), 
						RDFtriple::URI(RDF_TYPE,false),
						RDFtriple::URI(DB_YAGO_NS . $this->camel($YagoClass, "_") ));  
				
				}							

			}
		}
				
        return $result;
    }
    public function finish() { 
        return null;
    }
    

function camel( $in, $delim ) {
$USE_UNDERSCORE = false;

        $parts = explode( $delim, $in );
        $out = "";
        foreach( $parts as $k => $w ) {
                if ( $USE_UNDERSCORE && $k != 0 )
                        $out .= "_";
                $out .= strtoupper( $w[0] ) . substr( $w, 1 );
        }
        return $out;
}
    
    
}


