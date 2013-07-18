<?php
/**
 * contains the Extraction of the classes
 *
 */
class CategoriesClassesExtraction {
	const extractorID = "http://dbpedia.org/extractors/CategoriesClassesExtractor";
    private $language;
    private $link; // MySQL Connection
    private $result=array(""); // Resultarray der Klassen 
	private $rootClasses=array("");
	private $extractionResult;
    private $tempTableName="";
    private $writeClassInstances=false;
	private $destination;
        
	function __construct($tempTableName,$extractionResult,$result,$destination){
		include("configCategoriesClasses.php");
		$this->tempTableName=$tempTableName;
		$this->extractionResult=$extractionResult;
		$this->result=$result;
		$this->destination = $destination;
	}
	
/**
 *fetches the superclasses to a class
 *
 * @param int $pageid: - the pageid of the current class
 * @param string $pagetitle - the pagetitle of the current class
 * @param resource $con - the database connection, which is used
 * @param int $count - the current searchdepth
 * @param int $searchdepth - the maxsearchdepth
 * @return array the superlasses of the given class
 */
function get_superclass($pageid,$pagetitle,$con,$count,$searchdepth=10)
	{
	$tempString='';
	$resultarray=array();
	$t_resultarray=array();
	$temp_searcharray=array();
	// verhindert im "Kreis drehen"
	if ($count>$searchdepth) {return array();}
	$query1="select cl_to from categorylinks where cl_from=$pageid and cl_to<>'".mysql_real_escape_string($pagetitle,$con)."'";
	$result1=mysql_query($query1,$this->link);
	for ($z=0;$z<mysql_num_rows($result1);$z++) {
		$row=mysql_fetch_array($result1);
		
		$tempString.='page_title="'.mysql_real_escape_string($row['cl_to'],$con).'" OR ';
	}
	
	if (strlen($tempString)>0) {
		$tempString=substr($tempString,0,-3);
		$query2='select page_title,page_id,isClass from '.$this->tempTableName.' where '.$tempString;
		#echo $query2."\n";
		$result=mysql_query($query2,$this->link);
	}
	else
		{
		if (!in_array($pagetitle,$this->rootClasses))
			{
			// keine Definierte Topklasse -> unter die Topklasse(n) hängen
			$resultarray=$this->rootClasses;
			}
		else
			{  
			$resultarray[]="";	
			}
		return $resultarray;
	}
	
	#$query="select page_title,page_id,isClass from $this->tempTableName as p inner join (select cl_to from categorylinks where cl_from=$pageid and cl_to<>'".mysql_real_escape_string($pagetitle,$con)."') as tt on tt.cl_to=p.page_title order by isClass desc";
	#echo $query."\n";
	#$rows=0;
	#$result=mysql_query($query,$this->link);
	#$rows=@	mysql_num_rows($result);
		
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
			$resultarray=array_merge($resultarray,$this->get_superclass($t_resultarray[$i]['page_id'],$t_resultarray[$i]['page_title'],$con,++$count));	
			}
		else
			{
			$resultarray[]=$t_resultarray[$i]['page_title'];	
			}
		}
	unset($t_resultarray);
	for ($i=0;$i<count($resultarray);$i++)
			{
			// falls bereits Oberklassen vorhanden sind -> dann nicht unter die Top-Level Klasse(n) hängen
			if (in_array($resultarray[$i],$this->rootClasses))
				{
				$t_resultarray[$i]=""; 
				}
			else
				{
				$t_resultarray[$i]=$resultarray[$i];	
				}	
			}
	$emptyResultset=true;
	for ($i=0;$i<count($resultarray);$i++)
		{
		if (strlen(trim($t_resultarray[$i]))>0)
			{
			$emptyResultset=false;
			break;
			}
		}
	if ($emptyResultset==true && !in_array($pagetitle,$this->rootClasses))
		{
		$resultarray=$this->rootClasses;
		}
	else
		{
		$resultarray=$t_resultarray;
		}
	
	return $resultarray;
	}

    
  	/**
  	 * starts the extraction
  	 *
  	 */
    public function extractClasses(){
    	include ("./extractors/infobox/config.inc.php");
    	include("./databaseconfig.php");
    	echo "Start der Klassenberechnung... Zu bearbeitende Klassen (isClass=1):".mysql_num_rows($this->result)."\n";
		while ($row=mysql_fetch_assoc($this->result))
    		{
			if(isset($outputcounter))
				$outputcounter++;
			else
				$outputcounter=0;
			if ($outputcounter%1000==0) {
				// write the extracted triples => commented out, because it seems to cause problems
				// $this->destination->accept($this->extractionResult,1);
				// $this->extractionResult->clear();
				echo "1000 Klassen fertig bearbeitet, gesamt: $outputcounter\n";				
			}
			$t_resultarray=array("");
    		$pageTitle=$row['page_title'];
    		$pageId=$row['page_id'];
    		// definieren irgendwas ist eine Klasse
    		$this->extractionResult->addTriple(RDFtriple::URI($GLOBALS['W2RCFG']['wikipediaBase'].urlencode($pageTitle)),RDFtriple::URI("http://www.w3.org/1999/02/22-rdf-syntax-ns#type"),RDFtriple::URI("http://www.w3.org/2002/07/owl#Class"));
    		$tempExtractionResult=$this->get_superclass($pageId,$pageTitle,$this->link,0,10);
    		$tempExtractionResult=array_unique($tempExtractionResult);
			#print_r($tempExtractionResult);	
			foreach ($tempExtractionResult as $key => $value)
			{
			$t_resultarray[]=$value;
			}
			$tempExtractionResult=$t_resultarray;
    		for ($i=0;$i<count($tempExtractionResult);$i++)
    			{
    			if (strlen($tempExtractionResult[$i])>0)
					{
					$this->extractionResult->addTriple(RDFtriple::URI($GLOBALS['W2RCFG']['wikipediaBase'].urlencode($pageTitle)),RDFtriple::URI("http://www.w3.org/2000/01/rdf-schema#subClassOf"),RDFTriple::URI($GLOBALS['W2RCFG']['wikipediaBase'].urlencode($tempExtractionResult[$i])));
    				}
				}
    		}
/*
		echo "Start der Artikelzuordnung zu Klassen...\n";	
    if ($this->writeClassInstances==true)
    	{
    	$articlesToClasses=new ArticlesToClasses($this->tempTableName);
    	$articlesToClasses->setLink($this->link);
    	$tempExtractionResult=$articlesToClasses->extractClasses();
    	//print_r($tempExtractionResult);	
    	for ($i=0;$i<count($tempExtractionResult);$i++)
    			{
    			if (strlen($tempExtractionResult[$i]['object'])>0)
	    			{ 
	    			if (strlen($tempExtractionResult[$i]['datatype'])==0)
	    				{ 
	    				$this->extractionResult->addTriple(RDFtriple::URI($GLOBALS['W2RCFG']['wikipediaBase'].urlencode($tempExtractionResult[$i]['subject'])),RDFtriple::URI("http://www.w3.org/1999/02/22-rdf-syntax-ns#type"),RDFtriple::URI($GLOBALS['W2RCFG']['wikipediaBase'].urlencode($tempExtractionResult[$i]['object'])));
	    				}
	    			else
	    				{
	    				$this->extractionResult->addTriple(RDFtriple::URI($GLOBALS['W2RCFG']['wikipediaBase'].urlencode($tempExtractionResult[$i]['subject'])),RDFtriple::predicate($tempExtractionResult[$i]['predicate']),RDFtriple::literal($tempExtractionResult[$i]['object'],$tempExtractionResult[$i]['datatype']));
	    				}	
	    			}
					if ($i%1000==0) {
						$this->destination->accept($this->extractionResult,1);
						$this->extractionResult->clear();
						echo "1000 Zuordnungen geschrieben, gesamt: $i\n";				
					}
    			} // end for extractionResult
    	} // end writeClassInstances=true
*/
    }
    
    /**
     * set the database connection which is used for extraction
     *
     * @param resource $link - the resource to set
     */
    public function setLink($link)
    	{
    	$this->link=$link;
    	}
    
    /**
     * sets the resultset which contains all classes
     *
     * @param array $result the resultset to set
     */
    public function setResult($result)
    	{
    	$this->result=$result;
    	}
    
    /**
     * sets the extractiuonResult, which contains the results of the extraction
     *
     * @param extractionResult $extractionResult
     */
    public function setExtractionResult($extractionResult){
    	$this->extractionResult=$extractionResult;	
    }
	
    /**
     * gets the extractionResult
     *
     * @return extractionResult
     */
    public function getExtractionResult(){
    	return $this->extractionResult;
    }
    
    /**
     * finishes the extraction. no implementation
     *
     * @return null
     */
    public function finish() { 
        return null;
    }
}
