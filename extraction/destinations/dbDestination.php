<?php
/**
 * writes an instance of extractionResult to a given database
 * ./extraction/config.inc.php contains the details of the databse configuration
 */
class dbDestination implements Destination{
	private $link;
	private $tableName;
    
	/**
	 * checks whether the table exists or not
	 * if it doesn't exits the table will be created
	 * 
	 */
	private function createTable(){
	$sql="CREATE TABLE IF NOT EXISTS `$this->tableName` (
	  `modelId` int(11) NOT NULL,
	  `subject` varchar(255) NOT NULL,
	  `predicate` varchar(255) NOT NULL,
	  `object` varchar(255) NOT NULL,
	  `l_lang` varchar(5) NOT NULL,
	  `l_datatype` varchar(255) NOT NULL,
	  `subject_is` varchar(255) NOT NULL,
	  `object_is` varchar(255) NOT NULL,
	  `id` bigint(20) NOT NULL auto_increment,
	  PRIMARY KEY  (`id`),
	  KEY `subject_predicate_object` (`object`,`subject`,`predicate`),
	  KEY `subject` (`subject`,`object`,`predicate`)
	  ) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=0";
	mysql_query($sql,$this->link);
	if (mysql_errno!=0)
		{
		die ("error while creating table $this->tableName with errorcode ".mysql_errno()." and errordescription ".mysql_error());
		}
	}
	
	/**
	 * creates connection to the destination database
	 */
	public function start()
    {
    include("./configCategoriesClasses.php");
    $link=mysql_connect($sqlDestHost,$sqlDestUser,$sqlDestPwd,true) or die ("Error while connecting to SQLDestination: $sqlDestHost");
    mysql_select_db($sqlDestDb,$link) or die ("Unable to select database $sqlDestDb");
    $this->link=$link;
    $this->tableName=$tableName;
    $this->createTable();
    }
    
   
    /**
     * writes an instance of extractionResult to a given database
     * @param extractionResult - the extractionResult which has to be written
     * @param revisionID - the revisionID of the extractionResult
     * 
     */
    public function accept($extractionResult)
    {
    
    foreach (new ArrayObject($extractionResult->getTriples()) as $triple) 
    	{
		$sql="insert into $this->tableName (subject,predicate,object,l_lang,l_datatype,subject_is,object_is) ";
    	$dType=null;
		$lang=null;
		$objectIs="r";
		$subjectIs="r";
    	$tripleString = explode(">",$triple->toString());
		$s = trim(str_replace("<","",$tripleString[0]));
		$p = trim(str_replace("<","",$tripleString[1]));
		$o = trim(str_replace("<","",$tripleString[2]));
		$dtypePos = strpos($o, "^^");
		$langPos = strpos($o, "@");
		$o_new=$o;
		if ( $dtypePos ) 
			{
			$dtype=trim(substr($o,$dtypePos,$langpos));
			$o_new = substr($o, 0,$dtypePos);
			$objectIs="l";
			}
		if ( $langPos )
			{
			$lang = substr($o,$langPos);
			}
		$o=$o_new;
		$o = preg_replace('/(^")|("$)/',"",$o);
		if ( !preg_match('/^[0-9\.,]+$/', $o) ) $o = "\"" . $o . "\"";
		if (trim($o)=="" || strlen($o)==0) 
			{
			$objectIs="b";
			}
		$sql.=" VALUES('$s','$p','$o','$lang','$dType','$subjectIs','$objectIs')";
    	mysql_query($sql,$this->link);
    	if (mysql_errno()!=0)
		{
		die ("error while inserting into table $this->tableName with errorcode ".mysql_errno()." and errordescription ".mysql_error()." and query $sql");
		}
    	}
    }
    
	/**
	 * only closes the database connection
	 * 
	 */
	public function finish()
    {
    mysql_close($this->link);
   	}

}
