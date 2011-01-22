<?php
/**
 * 
 *
 */
class ArticlesToClasses extends CategoriesClassesExtraction{
private $replaceMentArray=array();
	
	/**
	 * constructor of ArticlesToClasses
	 *
	 * @param String tempTableName: -the Name of the table which contains all classes
	 */
	function __construct($tempTableName){
	include("configCategoriesClasses.php");
	$this->replaceMentArray=$replaceMentArray;
	$this->tempTableName=$tempTableName;
	}


/**
 * fetches the classes to an article
 *
 * @param int $pageid: - the pageid of the article
 * @param string $pagetitle - the pagetitle of the article
 * @param resource $con - the database connection, which is used
 * @param int $count - the current searchdepth
 * @param int $searchdepth - the maxsearchdepth
 * @return array the superlasses of the given article
 */
function get_superclass($pageid,$pagetitle,$con,$count,$searchdepth=3)
	{
	$tempString='';
	$rootClasses=array("");
	$resultarray=array();
	$t_resultarray=array();
	$temp_searcharray=array();
	// verhindert im "Kreis drehen"
	if ($count>$searchdepth) {return array("");}
	// TODO: temporären Tabellennamen einfügen
	$query1="select cl_to from categorylinks where cl_from=$pageid and cl_to<>'".mysql_real_escape_string($pagetitle, $this->link)."'";
	$result1=mysql_query($query1, $this->link);
	for ($z=0;$z<mysql_num_rows($result1);$z++) {
		$row=mysql_fetch_array($result1);
		
		$tempString.='page_title="'.mysql_real_escape_string($row['cl_to'], $this->link).'" OR ';
	}
	if (strlen($tempString)>0) {
		$tempString=substr($tempString,0,-3);
		$query2='select page_title,page_id,isClass from '.$this->tempTableName.' where '.$tempString;
		$result=mysql_query($query2, $this->link);
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
	for ($i=0;$i<count($t_resultarray);$i++)
		{
		// falls keine Klassse gefunden
		if ($t_resultarray[$i]["isClass"]==2 || $t_resultarray[$i]["isClass"]==0)
			{
			for ($j=0;$j<count($this->replaceMentArray);$j++)
				{
				$temp_replacement=split($this->replaceMentArray[$j][2],$t_resultarray[$i]['page_title']);
				if (count($temp_replacement)>1)
					{
					$temp_array=split;
					unset($t_resultarray[$i]);
					$resultarray[$i]['predicate']=$this->replaceMentArray[$j][0];
					$resultarray[$i]['object']=$temp_replacement[$this->replaceMentArray[$j][3]];
					$resultarray[$i]['datatype']=$this->replaceMentArray[$j][1];
					break; // fertig keine weiteren Replacements
					}
				} // Ende replacements
			$resultarray=array_merge($resultarray,$this->get_superclass($t_resultarray[$i]['page_id'],$t_resultarray[$i]['page_title'],NULL,++$count));	
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
		// falls bereits Oberklassen vorhanden sind -> dann nicht unter die Top-Level Klasse(n) hängen
		if (in_array($resultarray[$i]['object'],$rootClasses))
			{
			$t_resultarray[$i]['object']=""; 
			$t_resultarray[$i]['predicate']="";
			}
		else
			{
			$t_resultarray[$i]['object']=$resultarray[$i]['object'];	
			$t_resultarray[$i]['predicate']=$resultarray[$i]['predicate'];	
			$t_resultarray[$i]['datatype']=$resultarray[$i]['datatype'];
			}	
		}
		$emptyResultset=true;
		for ($i=0;$i<count($resultarray);$i++)
			{
			if (strlen(trim($t_resultarray[$i]['object']))>0)
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
	
	 
	/**
	 * starts the extraction for all articles
	 *
	 * @return the results of the extraction as instance of extractionResult
	 */
	public function extractClasses(){
    	$ExtractionResultArray=array("");
	 	$query="select page_title,page_id from page where page_namespace=0 and page_is_redirect=0";
    	$result=mysql_query($query, $this->link);
    	while ($row=mysql_fetch_assoc($result))
    		{
    		$pageTitle=$row['page_title'];
    		$pageId=$row['page_id'];
    		$tempExtractionResult=$this->get_superclass($pageId,$pageTitle,NULL,0,3);
    		$t_resultarray=array();
    		for ($i=0;$i<count($tempExtractionResult);$i++)
				{
				if (strlen($tempExtractionResult[$i]['object'])>0)
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
						$t_resultarray[$k]['datatype']=$tempExtractionResult[$i]['datatype'];
						}
					}
				}
    		$ExtractionResultArray=array_merge($ExtractionResultArray,$t_resultarray);
    		} // End While
    	return $ExtractionResultArray;
    }
    
}
