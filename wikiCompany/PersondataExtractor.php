<?php

/**
 * This Extractor extracts Persondata such as date of birth, place of death, etc.
 * from Wikipedia
 * 
 */

class PersondataExtractor implements Extractor 
{
	const extractorID = "http://dbpedia.org/extractors/PersondataExtractor";
    private $language;
    public function getExtractorID() {
        return self::extractorID;
    }
    public function start($language) {
        $this->language = $language;
    }
    public function getLanguage() {
    	return $this->language;
    }
    
    public function extractPage($pageID, $pageTitle, $pageSource) {
        $result = new ExtractionResult(
                $pageID, $this->language, self::extractorID);
                
		$PersonData = $this->extractPersondata($pageSource, $this->language);
				
		//var_dump($PersonData);
		if ($PersonData != null)
		{
		
		// preg_match("/\[\[en:(.*)\]\]/", $pageSource, $LangLinkmatch);
		// $PersonData['enPageID'] = str_replace(" ","_",$LangLinkmatch[1]);
				
			
		$WikiDB = new DatabaseWikipedia($this->language);
		
		preg_match("/\[\[([^\]]*)\]\]/", $PersonData['birthplace'], $Birthplacematch);
		$Birthplacematch = $this->getLinkForLabeledLink($Birthplacematch);
		$mySource = $WikiDB->getSource($Birthplacematch);
		
		preg_match("/\[\[en:(.*)\]\]/", $mySource, $LangLinkmatch);
		$BirthPlace = $LangLinkmatch[1];
		
		
		preg_match("/\[\[([^\]]*)\]\]/", $PersonData['deathplace'], $Deathplacematch);
		$Deathplacematch = $this->getLinkForLabeledLink($Deathplacematch);
		$mySource = $WikiDB->getSource($Deathplacematch);
		
		preg_match("/\[\[en:(.*)\]\]/", $mySource, $LangLinkmatch);
		$DeathPlace = $LangLinkmatch[1];
				
		//var_dump($PersonData);
		//var_dump($BirthPlace);
		//var_dump($DeathPlace);
		//var_dump($Deathplacematch);
                    
				
		$result->addTriple(
                RDFtriple::page($pageID), 
                RDFtriple::URI("http://xmlns.com/foaf/0.1/name"),
                RDFtriple::Literal($PersonData['name'],null,"de"));   
				
				
		$result->addTriple(
                RDFtriple::page($pageID), 
                RDFtriple::URI("http://xmlns.com/foaf/0.1/givenname"),
                RDFtriple::Literal($PersonData['givenname'],null,"de"));  
				
				
		$result->addTriple(
                RDFtriple::page($pageID), 
                RDFtriple::URI("http://xmlns.com/foaf/0.1/surname"),
                RDFtriple::Literal($PersonData['surname'],null,"de"));  
			
				
		if($BirthPlace != "")
		{		
        $result->addTriple(
                RDFtriple::page($pageID), 
                RDFtriple::predicate("birthPlace"),
                RDFtriple::page($BirthPlace));   
		
		// $result->addTriple(
                // RDFtriple::page($pageID), 
                // RDFtriple::URI("http://purl.org/vocab/bio/0.1/event"),
                // RDFtriple::URI("http://dbpedia.org/resource/" . URI::wikipediaEncode($pageID) . "/Birth"));

		// $result->addTriple(
                // RDFtriple::URI("http://dbpedia.org/resource/" . URI::wikipediaEncode($pageID) . "/Birth"); 
                // RDFtriple::URI("http://purl.org/vocab/bio/0.1/place"),
                // RDFtriple::page($BirthPlace));   
		}
		
		

						
		if($PersonData['birthdate'] != "")
		{				
		$result->addTriple(
                RDFtriple::page($pageID), 
                RDFtriple::predicate("birth"),
                RDFtriple::Literal($PersonData['birthdate'],"http://www.w3.org/2001/XMLSchema#date",null));  				
		}		
		
		if($DeathPlace != "")
		{
		$result->addTriple(
                RDFtriple::page($pageID), 
                RDFtriple::predicate("deathPlace"),
                RDFtriple::page($DeathPlace)); 
		}
		
		
		if($PersonData['deathdate'] != "")
		{		
		$result->addTriple(
                RDFtriple::page($pageID), 
                RDFtriple::predicate("death"),
                RDFtriple::Literal($PersonData['deathdate'],"http://www.w3.org/2001/XMLSchema#date",null));  				
		}	
 

			
		if($PersonData['description'] != "")
		{	
		$result->addTriple(
                RDFtriple::page($pageID), 
                RDFtriple::URI("http://purl.org/dc/elements/1.1/description"),
                RDFtriple::Literal($PersonData['description'],null,"de"));  
		}		
		
		
		$result->addTriple(
                RDFtriple::page($pageID), 
                RDFtriple::URI("http://www.w3.org/1999/02/22-rdf-syntax-ns#type"),
                RDFtriple::URI("http://xmlns.com/foaf/0.1/Person")); 
						
	
		}	
        return $result;
		
    }
    public function finish() { 
        return null;
    }
	
	
	
	public function extractPersondata($pageSource, $language)
	{
	if ($language == "en")
	{
		$PersondataName = "Persondata";
	}
	
	if ($language == "de")
	{
		$PersondataName = "Personendaten";
	}
	
	preg_match("/\{\{($PersondataName(?>[^{}]+)|(?R))*\}\}/", $pageSource, $match);
	
	if (count($match) == 0)
	{
	return null; 
	}
	else
	{
	
	preg_match_all("/\|\s*([A-Z]+)=(.*)/", $match[0], $props, PREG_SET_ORDER);
	
	$results = array();
		foreach ($props as $keyvalue) {
			//echo $keyvalue[1] . ': ' . trim($keyvalue[2]) . "\n";
			if ($keyvalue[1]== "NAME")
				{
				$results['name'] = mysql_escape_string(trim($keyvalue[2]));
				}
			
			if ($keyvalue[1]== "ALTERNATIVNAMEN")
				{
				$results['altname'] = mysql_escape_string(trim($keyvalue[2]));
				}
			
			if ($keyvalue[1]== "KURZBESCHREIBUNG")
				{
                //$PersonDesc = mysql_escape_string(preg_replace_callback("/\[\[([^|]*?)(\|.*?)?\]\]/", array(&$this, 'getLabelForLink'),trim($keyvalue[2])));
				$results['description'] = preg_replace_callback("/\[\[([^|]*?)(\|.*?)?\]\]/",array(&$this, 'getLabelForLink'), trim($keyvalue[2]));

				}
			
			if ($keyvalue[1]== "GEBURTSDATUM")
				{
					$results['birthdate'] = $this->StringToDate(mysql_escape_string(trim($keyvalue[2])));

				}
			
			if ($keyvalue[1]== "GEBURTSORT")
				{
				$results['birthplace'] = mysql_escape_string(trim($keyvalue[2]));
				}
			
			if ($keyvalue[1]== "STERBEDATUM")
				{
				$results['deathdate'] = $this->StringToDate(mysql_escape_string(trim($keyvalue[2])));

				}
			
			if ($keyvalue[1]== "STERBEORT")
				{
				$results['deathplace'] = mysql_escape_string(trim($keyvalue[2]));
				}
			
			
		}
		
		preg_match_all("/^([^,]+),([^,]+)$/", $results['name'], $name, PREG_SET_ORDER);
		
		$results['surname'] = trim($name[0][1]);
		$results['givenname'] = trim($name[0][2]);

		//var_dump($pageSource);

			
		//var_dump($results);
		return $results;
	}
	
	}
	
	public function getLabelForLink($text2) {
		return str_replace("]]","",str_replace("[[","",preg_replace("/.*\|/", "[[", $text2[0]))) ;
	}
	
	public function getLinkForLabeledLink($text2) {
		return str_replace(" ","_",str_replace("]]","",str_replace("[[","",preg_replace("/\|.*/", "]]", $text2[0])))) ;
	}
	
	public function StringToDate($string)
{
	
	preg_match_all("/\d\d?./", $string, $meinDatumTag, PREG_SET_ORDER);
	preg_match_all("/[A-Z]*[a-z]+/", $string, $meinDatumMonat, PREG_SET_ORDER);
	preg_match_all("/\d\d\d\d?/", $string, $meinDatumJahr, PREG_SET_ORDER);
	
	
	$temp_Monat = "00";
	if ($meinDatumMonat[0][0] == "Januar")
	{
		$temp_Monat = "01";
	}
	if ($meinDatumMonat[0][0] == "Februar")
	{
		$temp_Monat = "02";
	}
	if ($meinDatumMonat[0][0] == "März") 
	{
		$temp_Monat = "03";
	}
	if ($meinDatumMonat[0][0] == "April")
	{
		$temp_Monat = "04";
	}
	if ($meinDatumMonat[0][0] == "Mai")
	{
		$temp_Monat = "05";
	}
	if ($meinDatumMonat[0][0] == "Juni")
	{
		$temp_Monat = "06";
	}
	if ($meinDatumMonat[0][0] == "Juli")
	{
		$temp_Monat = "07";
	}
	if ($meinDatumMonat[0][0] == "August")
	{
		$temp_Monat = "08";
	}
	if ($meinDatumMonat[0][0] == "September")
	{
		$temp_Monat = "09";
	}
	if ($meinDatumMonat[0][0] == "Oktober")
	{
		$temp_Monat = "10";
	}
	if ($meinDatumMonat[0][0] == "November")
	{
		$temp_Monat = "11";
	}
	if ($meinDatumMonat[0][0] == "Dezember")
	{
		$temp_Monat = "12";
	}
	
	//echo $meinDatumMonat[0][0];
	if ($temp_Monat == "00")
	{
		
		return null;
	}
	else
	{
		$Tag = str_replace(".","",$meinDatumTag[0][0]);
		if (strlen($Tag)==1)
		{
		$Tag = "0" . $Tag;
		}
		
		return $meinDatumJahr[0][0] . "-" . $temp_Monat . "-" . $Tag;
	}
	
	
}
    
    
    
}


