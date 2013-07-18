<?php
/*
Exracts the languagelinks  from a Wikipedia article   (chenyue, 26.03.2008, leipzig)
*/
class InterlanguageExtractor extends Extractor 
{
	private $DBlink;
	private $org_language;// orginal Language

    public function start($language) {
	
        $this->language = $language;
		
		include ("databaseconfig.php");
			
		// Todo: Database Prefix in Config
		$wikiDB = $dbprefix.$this->language;
	
		$this->DBlink = mysql_connect($host, $user, $password, true)
			or die("Error:Could not connect to database! " . mysql_error());
		//echo "connect to database successful" . "\n";
		mysql_select_db($wikiDB, $this->DBlink) or die("abortive select database");

		mysql_query("SET NAMES utf8", $this->DBlink);
		
    }
    public function extractPage($pageID, $pageTitle,  $pageSource) {
	    $org_language=$this->language;
		
        $result = new ExtractionResult($pageID, $this->language, $this->getExtractorID());
		
		$query = "select page_title, page_namespace, ll.ll_lang,replace(trim(ll_title), ' ', '_')as lang_title from page p inner join langlinks ll on p.page_id = ll.ll_from where p.page_title= '" .  mysql_escape_string($pageID) . "' and p.page_namespace <> 14";
		
		$queryresult = mysql_query($query, $this->DBlink) or die(" search unsuccessful: " . mysql_error());
	
		while ($row = mysql_fetch_array($queryresult, MYSQL_ASSOC)) 
			  {  
			    //$object="http://xxx.dbpedia.org/resource/".URI::wikipediaEncode($row["lang_title"] );
			    $result->addTriple(
				
				RDFtriple::URI("http://".$org_language.".dbpedia.org/resource/" . URI::wikipediaEncode($pageID)), 
				
				RDFtriple::URI(OWL_SAMEAS,false),
				
				// Note: language code in column ll_lang uses '-', not '_', which is correct here
				RDFtriple::URI("http://".$row["ll_lang"].".dbpedia.org/resource/".URI::wikipediaEncode($row["lang_title"])));  
		
			   }
						
        return $result;
    }
   
      
}


