<?php
/****
*This Extractor assigns Articles to Classes. The Classes are known from the CategoriesClassesExtractor. The Extractor would only create Output when the temp_cats Table from CategoriesClassesExtractor exists 
*Needs: databaseconfig.inc.php and classes/configCategoriesClasses.php and temp_cats Table from CategoriesClassesExtractor
******/

class CategoriesClassesToArticlesExtractor extends Extractor 
{
	
	/*
	 * Overrides default method
	 * */
    public function start($language) {
        $this->language = $language;
		include("databaseconfig.php");
		$this->link = mysql_connect($host, $user, $password, true)
			or die("Keine Verbindung m?glich: " . mysql_error());

		mysql_select_db($dbprefix.$this->language, $this->link) or die("CategoriesClassesToArticlesExtractor: Auswahl der Datenbank fehlgeschlagen\n");		
		include("classes/configCategoriesClasses.php");
		$this->replaceMentArray=$replaceMentArray;
		$this->tempTableName=$tempTableName;
    }
    public function extractPage($pageID, $pageTitle,  $pageSource) {
        $result = new ExtractionResult(
       
				$pageID, $this->language, $this->getExtractorID());
                
				
				$res=mysql_query("SELECT page_id from page WHERE page_title='".mysql_real_escape_string($pageTitle,$this->link)."' and page_namespace=0 and page_is_redirect=0",$this->link);
				$rows=mysql_fetch_array($res);
				$realID=$rows['page_id'];
				$tempExtractionResult=$this->extractClasses($pageTitle,$realID);
				
				for ($i=0;$i<count($tempExtractionResult);$i++)
    			{
    			if (isset($tempExtractionResult[$i]['object']) && strlen($tempExtractionResult[$i]['object'])>0)
	    			{ 
	    			if (!isset($tempExtractionResult[$i]['datatype']) || strlen($tempExtractionResult[$i]['datatype'])==0)
	    				{ 
	    				$result->addTriple(
						RDFtriple::URI('http://dbpedia.org/resource/'.urlencode($tempExtractionResult[$i]['subject'])),
						RDFtriple::URI("http://www.w3.org/1999/02/22-rdf-syntax-ns#type"),
						RDFtriple::URI('http://dbpedia.org/resource/'.urlencode($tempExtractionResult[$i]['object'])));
	    				}
	    			else
	    				{
	    				$result->addTriple(
						RDFtriple::URI('http://dbpedia.org/resource/'.urlencode($tempExtractionResult[$i]['subject'])),
						RDFtriple::URI($tempExtractionResult[$i]['predicate']),
						RDFtriple::literal($tempExtractionResult[$i]['object'],$tempExtractionResult[$i]['datatype']));
	    				}	
	    			}
    			} // end for extractionResult
				
        return $result;
    }
	public function extractClasses($pageTitle,$pageID) {
		$ExtractionResultArray=array("");
		$tempExtractionResult=$this->get_superclass($pageID,$pageTitle,NULL,0,3);
    		$t_resultarray=array();
    		for ($i=0;$i<count($tempExtractionResult);$i++)
				{
				if (isset($tempExtractionResult[$i]['object']) && strlen($tempExtractionResult[$i]['object'])>0)
					{
					$alreadyInArray=false;
					for ($j=0;$j<count($t_resultarray);$j++)
						{
						if ($tempExtractionResult[$i]['predicate']==$t_resultarray[$j]['predicate'] && $tempExtractionResult[$i]['object']==$t_resultarray[$j]['object'])
							{
							$alreadyInArray=true;
							break;
							}
						}
					if ($alreadyInArray==false)
						{
						$k=count($t_resultarray);
						$t_resultarray[$k]['subject']=$pageTitle;
						$t_resultarray[$k]['predicate']=$tempExtractionResult[$i]['predicate'];
						$t_resultarray[$k]['object']=$tempExtractionResult[$i]['object'];
						if(isset($tempExtractionResult[$i]['datatype']))
							$t_resultarray[$k]['datatype']=$tempExtractionResult[$i]['datatype'];
						}
					}
				}
    		$ExtractionResultArray=array_merge($ExtractionResultArray,$t_resultarray);
		return $ExtractionResultArray;	
	}
	public function get_superclass($pageid,$pagetitle,$con,$count,$searchdepth=3) {
		
		
		$tempString='';
		$result1=NULL;
		$result=NULL;
		$rootClasses=array("");
		$resultarray=array();
		$t_resultarray=array();
		$temp_searcharray=array();
		$replaceMentArray=$this->replaceMentArray;
		// verhindert im "Kreis drehen"
		if ($count>$searchdepth) {return array("");}

		$query1="select cl_to from categorylinks where cl_from='".$pageid."' and cl_to<>'".mysql_real_escape_string($pagetitle,$this->link)."'";
		
		$result1=mysql_query($query1,$this->link);
		for ($z=0;$z<mysql_num_rows($result1);$z++) {
			$row=mysql_fetch_array($result1);
			
			$tempString.='page_title="'.mysql_real_escape_string($row['cl_to'],$this->link).'" OR ';
		}
		if (strlen($tempString)>0) {
			$tempString=substr($tempString,0,-3);
			$query2='select page_title,page_id,isClass from '.$this->tempTableName.' where '.$tempString;
			#echo $query2."\n";
			$result=mysql_query($query2,$this->link);
		}
		else {
			if (!in_array($pagetitle,$rootClasses))
				{
				for($i=0;$i<count($rootClasses);$i++)
					{
					$resultarray[$i]['object']=$rootClasses[$i];
					$resultarray[$i]['predicate']='http://www.w3.org/1999/02/22-rdf-syntax-ns#type';
					}
				}
			else
				{
				$resultarray[0]['object']=$pagetitle;
				$resultarray[0]['predicate']='http://www.w3.org/1999/02/22-rdf-syntax-ns#type';	
				}
			return $resultarray;
			}
		for($j=0;$j<mysql_num_rows($result);$j++)
			{
			// alle potentiellen Oberklassen sammeln
			$t_resultarray[$j]=mysql_fetch_assoc($result);
			}
			#print_r($t_resultarray);
		
		for ($i=0;$i<count($t_resultarray);$i++)
			{
			// falls keine Klassse gefunden
			
			if ($t_resultarray[$i]["isClass"]==2 || $t_resultarray[$i]["isClass"]==0)
				{
				for ($j=0;$j<count($replaceMentArray);$j++)
					{
					$temp_replacement=split($replaceMentArray[$j][2],$t_resultarray[$i]['page_title']);
			
					if (count($temp_replacement)>1)
						{
						if (preg_match('/^[0-9]{4}/',$temp_replacement[$replaceMentArray[$j][3]])) {
						
						
							unset($t_resultarray[$i]);
							$resultarray[$i]['predicate']=$replaceMentArray[$j][0];
							$resultarray[$i]['object']=$temp_replacement[$replaceMentArray[$j][3]];
							$resultarray[$i]['datatype']=$replaceMentArray[$j][1];
							break; // fertig keine weiteren Replacements
							}
						}
					} // Ende replacements
				$resultarray=@array_merge($resultarray,$this->get_superclass($t_resultarray[$i]['page_id'],$t_resultarray[$i]['page_title'],NULL,++$count));	
				}
			else
				{
				$resultarray[$i]['object']=$t_resultarray[$i]['page_title'];
				$resultarray[$i]['predicate']='http://www.w3.org/1999/02/22-rdf-syntax-ns#type';		
				}	
			} // ende for t_resultarray
			
		unset($t_resultarray);
		for ($i=0;$i<count($resultarray);$i++)
			{
			// falls bereits Oberklassen vorhanden sind -> dann nicht unter die Top-Level Klasse(n) h�ngen
			if (isset($resultarray[$i]['object']) && in_array($resultarray[$i]['object'],$rootClasses))
				{
				$t_resultarray[$i]['object']=""; 
				$t_resultarray[$i]['predicate']="";
				}
			else
				{
				if(isset($resultarray[$i]['object']))
					$t_resultarray[$i]['object']=$resultarray[$i]['object'];
				if(isset($resultarray[$i]['predicate']))
					$t_resultarray[$i]['predicate']=$resultarray[$i]['predicate'];	
				if(isset($resultarray[$i]['datatype']))
					$t_resultarray[$i]['datatype']=$resultarray[$i]['datatype'];
				}	
			}
			$emptyResultset=true;
			for ($i=0;$i<count($resultarray);$i++)
				{
				if (isset($t_resultarray[$i]['object']) && strlen(trim($t_resultarray[$i]['object']))>0)
					{
					$emptyResultset=false;
					break;
					}
				}
			if ($emptyResultset==true && !in_array($pagetitle,$rootClasses))
				{
				for ($k=0;$k<count($rootClasses);$k++)
					{
					$resultarray[$k]['object']=$rootClasses[$k];
					$resultarray[$k]['predicate']='http://www.w3.org/1999/02/22-rdf-syntax-ns#type';
					}
				}
			else
				{
				$resultarray=$t_resultarray;
				}	
	return $resultarray;
	}
	
    public function finish() { 
        mysql_close($this->link);
		return null;
    }
    
}
