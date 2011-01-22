<?php
class sqlDestination implements Destination{
	
    /**
	*
	* inherited from Destination
	* no further implementation here 
	* 
	*/
    public function start(){
    return null;
    }
   
    /**
     * @param extractionResult: the result of the extraction which has to be written on screen
     * @param revisionId:	the revision id 
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
    	echo $sql.'\n';
    	}
    }
	/**
	 * inherited from Destination 
	 * 
	 * no further implementation here
	 * 
	 */
	public function finish()
    {
    return null;
   	}

}
