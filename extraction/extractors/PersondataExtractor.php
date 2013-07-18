<?php

/**
 * This Extractor extracts Persondata such as date of birth, place of death, etc.
 * from Wikipedia
 * 
 */

class PersondataExtractor extends Extractor 
{
    public function extractPage($pageID, $pageTitle, $pageSource) {
        $result = new ExtractionResult(
                $pageID, $this->language, $this->getExtractorID());
                
		$PersonData = $this->extractPersondata($pageSource, $this->language);

		//var_dump($PersonData);
		if ($PersonData != null)
		{
		
		// preg_match("/\[\[en:(.*)\]\]/", $pageSource, $LangLinkmatch);
		// $PersonData['enPageID'] = str_replace(" ","_",$LangLinkmatch[1]);
				
		if(Options::getOption('Persondata.usedb')){
			$WikiDB = new DatabaseWikipediaCollection($this->language);
		}
		$mysource="";
		
		if(isset($PersonData['birthplace'])) {
		preg_match("/\[\[([^\]]*)\]\]/", $PersonData['birthplace'], $Birthplacematch);
		if(isset($Birthplacematch[0])) {
			$Birthplacematch = $this->getLinkForLabeledLink($Birthplacematch);
			if(Options::getOption('Persondata.usedb')){
				$mySource = $WikiDB->getSource($Birthplacematch);
			}
		
		preg_match("/\[\[en:(.*)\]\]/", $mySource, $LangLinkmatch);
		if(isset($LangLinkmatch[1]))
			$BirthPlace = $LangLinkmatch[1];
		}
		}
		
		if(isset($PersonData['deathplace'])) {
		preg_match("/\[\[([^\]]*)\]\]/", $PersonData['deathplace'], $Deathplacematch);
		if(isset($Deathplacematch[0])) {
			$Deathplacematch = $this->getLinkForLabeledLink($Deathplacematch);
			if(Options::getOption('Persondata.usedb')){
				$mySource = $WikiDB->getSource($Deathplacematch);
			}
		preg_match("/\[\[en:(.*)\]\]/", $mySource, $LangLinkmatch);
		if(isset($LangLinkmatch[1]))
			$DeathPlace = $LangLinkmatch[1];
		}
		}
				
		//var_dump($PersonData);
		//var_dump($BirthPlace);
		//var_dump($DeathPlace);
		//var_dump($Deathplacematch);
                    
		if (isset($PersonData['name']) &&  $PersonData['name']!="") {		
		$result->addTriple(
				$this->getPageURI(),
                RDFtriple::URI(FOAF_NAME,false),
                RDFtriple::Literal($PersonData['name'],null,"de"));   
		}		
		if (isset($PersonData['givenname']) &&  $PersonData['givenname']!="") {		
		$result->addTriple(
 				$this->getPageURI(),
                RDFtriple::URI(FOAF_GIVENNAME,false),
                RDFtriple::Literal($PersonData['givenname'],null,"de"));  
		}
		if (isset($PersonData['surname']) &&  $PersonData['surname']!="") {		
		$result->addTriple(
 				$this->getPageURI(),
                RDFtriple::URI(FOAF_SURNAME,false),
                RDFtriple::Literal($PersonData['surname'],null,"de"));  
		}	
				
		if(isset($BirthPlace) && $BirthPlace != "")
		{		
        $result->addTriple(
  				$this->getPageURI(), 
				RDFtriple::URI(DB_BIRTHPLACE,false),
                RDFtriple::page($BirthPlace));
		
		// $result->addTriple(
                // RDFtriple::page($pageID), 
                // RDFtriple::URI("http://purl.org/vocab/bio/0.1/event"),
                // RDFtriple::URI("http://dbp     edia.org/resource/" . URI::wikipediaEncode($pageID) . "/Birth"));

		// $result->addTriple(
                // RDFtriple::URI("http://dbp     edia.org/resource/" . URI::wikipediaEncode($pageID) . "/Birth"); 
                // RDFtriple::URI("http://purl.org/vocab/bio/0.1/place"),
                // RDFtriple::page($BirthPlace));   
		}
		
		

						
		if(isset($PersonData['birthdate']) && $PersonData['birthdate'] != "")
		{				
		$result->addTriple(
 				$this->getPageURI(),
				RDFtriple::URI(DB_BIRTH,false), 
                RDFtriple::Literal($PersonData['birthdate'],XS_DATE,null));  				
		}		
		
		if(isset($DeathPlace) &&  $DeathPlace != "")
		{
		$result->addTriple(
 				$this->getPageURI(),
				RDFtriple::URI(DB_DEATHPLACE,false), 
                RDFtriple::page($DeathPlace)); 
		}
		
		
		if(isset($PersonData['deathdate']) &&  $PersonData['deathdate'] != "")
		{		
		$result->addTriple(
   				$this->getPageURI(),
				RDFtriple::URI(DB_DEATH,false), 
                RDFtriple::Literal($PersonData['deathdate'],XS_DATE,null));  				
		}	
 

			
		if(isset($PersonData['description']) && $PersonData['description'] != "")
		{	
		$result->addTriple(
				$this->getPageURI(),
                RDFtriple::URI(DC_DESCRIPTION,false),
                RDFtriple::Literal($PersonData['description'],null,"de"));  
		}		
		
		
		$result->addTriple(
    			$this->getPageURI(),
                RDFtriple::URI(RDF_TYPE,false),
                RDFtriple::URI(FOAF_PERSON,false)); 
						
	
		}	
        return $result;
		
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
					$results['name'] = addslashes(trim($keyvalue[2]));
				}
			if ($keyvalue[1]== "ALTERNATIVNAMEN")
				{
				$results['altname'] = addslashes(trim($keyvalue[2]));
				}
			
			if ($keyvalue[1]== "KURZBESCHREIBUNG")
				{
                //$PersonDesc = addslashes(preg_replace_callback("/\[\[([^|]*?)(\|.*?)?\]\]/", array(&$this, 'getLabelForLink'),trim($keyvalue[2])));
				$results['description'] = preg_replace_callback("/\[\[([^|]*?)(\|.*?)?\]\]/",array(&$this, 'getLabelForLink'), trim($keyvalue[2]));

				}
			
			if ($keyvalue[1]== "GEBURTSDATUM")
				{
					$results['birthdate'] = $this->StringToDate(addslashes(trim($keyvalue[2])));

				}
			
			if ($keyvalue[1]== "GEBURTSORT")
				{
				$results['birthplace'] = addslashes(trim($keyvalue[2]));
				}
			
			if ($keyvalue[1]== "STERBEDATUM")
				{
				$results['deathdate'] = $this->StringToDate(addslashes(trim($keyvalue[2])));

				}
			
			if ($keyvalue[1]== "STERBEORT")
				{
				$results['deathplace'] = addslashes(trim($keyvalue[2]));
				}
			
			
		}
		
		if(isset($results['name'])) {
		preg_match_all("/^([^,]+),([^,]+)$/", $results['name'], $name, PREG_SET_ORDER);
		
		if(isset($name[0][1]) && isset($name[0][2])) {
		$results['surname'] = trim($name[0][1]);
		$results['givenname'] = trim($name[0][2]);
		} else
			return null;

		// correct name if it contains a comma, see:
		// http://sourceforge.net/tracker/index.php?func=detail&aid=1860862&group_id=190976&atid=935520
		if(substr_count($results['name'],',')==1) {
			$parts = explode(',',$results['name']);
			if(isset($parts[1]))
				$results['name'] = trim($parts[1].' '.$parts[0]);
			else
				return null;
		}
		}

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
	if(isset($meinDatumMonat[0][0])) {
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
	}
	
	//echo $meinDatumMonat[0][0];
	if ($temp_Monat == "00")
	{
		
		return null;
	}
	else
	{
		if(isset($meinDatumTag[0][0])) {
		$Tag = str_replace(".","",$meinDatumTag[0][0]);
		if (strlen($Tag)==1)
		{
		$Tag = "0" . $Tag;
		}
		}
		else
			$tag = '0';
		
		if(isset($meinDatumJahr[0][0]))
			return $meinDatumJahr[0][0] . "-" . $temp_Monat . "-" . $Tag;
		else
			return $temp_Monat . "-" . $Tag;
	}
	
	
}
    
    
    
}


