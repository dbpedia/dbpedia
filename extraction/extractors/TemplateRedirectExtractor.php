<?php

class TemplateRedirectExtractor extends Extractor {
	private $redirectTemplateCounter;
	
    public function extractPage($pageID, $pageTitle,  $pageSource) {
        $result = new ExtractionResult(
                $pageID, $this->language, $this->getExtractorID());
        
		if (Util::isRedirect($pageSource, $this->language)) {
			if (preg_match_all("/\[\[([^\]]*)\]\]/",$pageSource,$matches, PREG_SET_ORDER)) {
				foreach ($matches as $match) {
					$s = RDFtriple::page($pageID); 
					$p = RDFtriple::predicate("redirect");
					$o = RDFtriple::page($this->getLinkForLabeledLink($match[1]));
					$templateredirecturi = str_replace("template:", "Template:", str_replace("'", "\'", str_replace(" ", "_", mb_strtolower($match[1]))));
					
					$templateredirecturi = DB_RESOURCE_NS.$templateredirecturi;
					$query = "select * from template_uri where uri = '$templateredirecturi'";
					$dbresult = mysql_query($query, $this->link) or die("Query failed: " . mysql_error() . ' - ' . $query);
					$uri = "";
							
					while ($row = mysql_fetch_array($dbresult, MYSQL_ASSOC)) {
						$uri = $row['uri'];
						$template_id = $row['template_id'];
						/*
						echo "$this->redirectTemplateCounter: $pageID => $match[1]";
						echo " $uri (FOUND)";
						echo "\n";				
						*/
						$newtemplateuri = DB_RESOURCE_NS. str_replace("template:", "Template:", str_replace("'", "\'", str_replace(" ", "_", mb_strtolower($pageID))));
						$query_template_uri = "select * from template_uri where uri = '$newtemplateuri'";
						$dbresult_template_uri = mysql_query($query_template_uri, $this->link) or die("Query failed: " . mysql_error() . ' - ' . $query_template_uri);
						if (mysql_num_rows($dbresult_template_uri) > 0) {
							//echo "$pageID already in DB";
						} else {
							$insertquery = "INSERT INTO template_uri (template_id, uri) VALUES ('".$template_id."', '".$newtemplateuri."')";
							mysql_query($insertquery, $this->link) or die("Query failed: " . mysql_error() . ' - ' . $insertquery);
							$this->redirectTemplateCounter++;
							echo "$this->redirectTemplateCounter: $pageID => $match[1]";
							echo " $newtemplateuri (FOUND)";
							echo "\n";	
							
						}
					}				
				}
			}
		}
		
        
        return $result;
    }
	
	public function start($language) {
		$this->language = $language;
		$this->redirectTemplateCounter = 0;
		include ("databaseconfig.php");
		$extractionDB = $dbprefix.'extraction_'.$language;
		$this->link = mysql_connect($host, $user, $password, true)
			or die("No connection possible: " . mysql_error());
		mysql_select_db($extractionDB, $this->link) or die("Database selection failed: ($extractionDB) " . mysql_error());
		//mysql_query("SET NAMES utf8", $this->link);
	}

    
	function encode_title($s, $namespace = null) {
        $result = urlencode(str_replace(' ', '_', $s));
        if ($namespace) {
            $result = $namespace . ":" . $result;
        }
        return $result;
    }

    function decode_title($s) {
		if (is_null($s)) return null;
        return preg_replace("/^(".WIKIMEDIA_CATEGORY."|".WIKIMEDIA_TEMPLATE."):/", "", str_replace('_', ' ', $s));
    }
    
	function getLinkForLabeledLink($text2) {
		return preg_replace("/\|.*/", "", $text2) ;
	}
    
}


