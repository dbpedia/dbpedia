<?php

/**
 * computes the classes from the categories
 *
 */
class CategoriesToClasses {
	private $language="en";
	private $tempTableName="";
	// private $tempTableType="MyIsam"; // MyIsam, Heap, Temporary
	private $tempTableType="temporary"; // MyIsam, Heap, Temporary
	private $link=null; // link to mysql resource
	private $patterns=null;
	private $whitelist=null;
	
	/**
	 * constructor of CategoriesToClasses
	 *
	 * @param resource $link a connection to a database
	 * @param String $tempTableName the name of the table
	 */
	function __construct($link,$tempTableName)
		{
		
		include("configCategoriesClasses.php");
		
		$this->link=$link;
		$this->tempTableName=$tempTableName;
		$this->patterns=$patterns;
		$this->whitelist=$whitelist;
		$this->tempTableType=$tempTableType;
		}
		
	/**
	 * copies all categories to the tempTable for computing the classes
	 *
	 */
	function copyCategoriesToTempTable()
		{
		if ($this->link==null || !is_resource($this->link))
			{
				die("Fehler bei der Abfrage der Kategorien");
			}
		else
			{
			$this->createTempTable();
			$query="insert into $this->tempTableName (page_id,page_title) (select page_id,page_title from page where page_namespace=14 and page_is_redirect=0)";
 			$result=mysql_query($query,$this->link);
			}
		}
	/**
	 * creates the tempTable if neccessary
	 */
	function createTempTable()
		{
		mysql_query("DROP TABLE IF EXISTS $this->tempTableName", $this->link);
		if ($this->tempTableType!="temporary")
			{
			$query="CREATE TABLE IF NOT EXISTS $this->tempTableName (
	 		id bigint(20) NOT NULL auto_increment,
	 		page_id bigint(20) NOT NULL,
	 		page_title varchar(255) collate utf8_unicode_ci NOT NULL,
	 		isClass int(11) NOT NULL default '0',
	 		PRIMARY KEY  (id),
	 		KEY page_title (page_title)
	 		) ENGINE=$this->tempTableType DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
	 		";
			}
		else 
			{
			$query="CREATE Temporary TABLE IF NOT EXISTS $this->tempTableName (
	 		id bigint(20) NOT NULL auto_increment,
	 		page_id bigint(20) NOT NULL,
	 		page_title varchar(255) collate utf8_unicode_ci NOT NULL,
	 		isClass int(11) NOT NULL default '0',
	 		PRIMARY KEY  (id),
	 		KEY page_title (page_title)
	 		) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
	 		";
			}
		$result=mysql_query($query,$this->link);
		} 
	
	/**
	 * processes the patterns on the categegories
	 */
	function processPatterns()
		{
		for($i=0;$i<count($this->patterns);$i++)
			{
			$sql="update $this->tempTableName set isClass=2 where isClass<>2 and page_title ".$this->patterns[$i];
			mysql_query($sql, $this->link);
			}
		}

	/**
	 * sets the patterns
	 *
	 * @param array $patterns
	 */
	function setPatterns($patterns)
		{
		$this->patterns=$patterns;
		}
	/**
	 * computes the classes from the categories
	 */
	function computeClasses()
		{
		$query="UPDATE $this->tempTableName AS tmp SET tmp.isClass=1 WHERE
				tmp.isClass=0 AND tmp.page_title IN (
					SELECT cl.cl_to
					FROM categorylinks AS cl INNER JOIN page AS p ON cl.cl_from=p.page_id
					WHERE cl.cl_to<>p.page_title AND p.page_namespace=0
				)";
		mysql_query($query, $this->link);	
		/********
		$query="select page_title as title,page_id as id from $this->tempTableName where isClass=0";
		$result=mysql_query($query, $this->link);
		for ($i=0;$i<mysql_num_rows($result);$i++)
			{
			$row=mysql_fetch_assoc($result);
			$id=$row['id'];
			echo "verarbeite id $id\n";
			$name=$row['title'];
			$query="select p.page_namespace from categorylinks as cl inner join page as p on cl.cl_from=p.page_id where cl.cl_to='".mysql_real_escape_string($name, $this->link)."' and p.page_title<>'".mysql_real_escape_string($name, $this->link)."' and p.page_namespace=0 limit 0,1";
			//echo "$query\n";
			$result_1=mysql_query($query, $this->link);
			//echo "Zeilen: ".mysql_num_rows($result_1)."\n";
			if (mysql_num_rows($result_1)>0)
				{
				echo "update durchgeführt bei id $id\n";
				$sql="update $this->tempTableName set isClass=1 where page_id=$id";
				mysql_query($sql, $this->link);
				}
			}************************/
		}
	
	/**
	 * computes the whitelist
	 */	
	function processWhiteList()
		{
		$i=0;
		if ($this->whitelist==null) return;
		while($i<count($this->whitelist))
			{
			// echo "update durchgeführt auf Kategorie: ".$this->whitelist[$i]."\n";
			$sql="update $this->tempTableName set isClass=1 where isClass<>1 and page_title = BINARY '".$this->whitelist[$i]."'";
			mysql_query($sql, $this->link);
			$i++;
			}
		}

	/**
	 * sets the whitelist
	 *
	 * @param array $whitelist
	 */	
	function setWhitelist($whitelist){
		$this->whitelist=$whitelist;
	}

	/**
	 * returns the database connection
	 *
	 * @return unknown
	 */
	function getLink()
		{
		return $this->link;
		}
	
	/**
	 * returns all classes
	 *
	 * @return array
	 */
	function getClasses()
		{
		$query="select page_title ,page_id from $this->tempTableName where isClass=1"; 
		$result=mysql_query($query, $this->link);
		return $result;
		}
	
	/**
	 * starts the compution of the classes
	 */
	function start(){
	
		$this->copyCategoriesToTempTable();
		$this->processPatterns();
		$this->computeClasses();
		$this->processWhiteList();
		
	}
}


