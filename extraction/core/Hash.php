<?php
define ("ODBC_MAX_LONGREAD_LENGTH", 8000);
define ("TABLENAME", 'dbpedia_triples');
define ("FIELD_OAIID", 'oaiid');
define ("FIELD_RESOURCE", 'resource');
define ("FIELD_JSON_BLOB", 'content');

class Hash{
   
/*
   public $subjectURI;
   public $subjectOAIidentifier;
*/
   public $oaiId;
   private $odbc;
   private $hashesFromStore=null;
   private $hashesFromExtractor = null;
   private $hasHash = false;
   private $active = false;
  
   //private $updateNecessary = false;
   public $newJSONObject = array();
   
   //normal rdftriples
   private $addTriples = null;
   //special array with sparulpattern
   private $deleteTriples = null;
   
   
   //used for special handling
/*
   private $deleteExtractors = array();
 	private $addExtractors = array();
*/
   
   
   
   public static function initDB($clearhashtable = false){
	   	if(Options::getOption('LiveUpdateDestination.useHashForOptimization')) {
	   		$odbc = ODBC::getDefaultConnection();
			if($clearhashtable) {
				$sql = "DROP TABLE ".TABLENAME;
				$result = $odbc->exec($sql, 'Hash::initDB');
				if(false!=$result){
					Logger::info('dropped hashtable');
				}
			}
			//test if table exists
			$test = "SELECT TOP 1 * FROM ".TABLENAME;
			
			$result = $odbc->exec($test, 'Hash::initDB');
			
			if(false===$result){
				$sql = "CREATE TABLE ".TABLENAME." ( ".FIELD_OAIID." INTEGER NOT NULL PRIMARY KEY, ".FIELD_RESOURCE." VARCHAR(510) ,".FIELD_JSON_BLOB." LONG VARCHAR  ) ";
				//echo $sql;
				$resultmake = $odbc->exec($sql, 'Hash::initDB');
				$resultsecondtest = $odbc->exec($test, 'Hash::initDB');
				if(false===$resultmake||false===$resultsecondtest ){
					die('could not create table '.TABLENAME );
				}else{
					Logger::info('created table '.TABLENAME);
				}
			}else{
				Logger::info('Hash:: table '.TABLENAME.' found');	
			}
		}	
	}	
	   
   
   public function __construct($oaiId,  $subject){
	   		$this->subject = $subject;
	   		//$this->subjectOAIidentifier = $subjectOAIidentifier;
	  	 	//	$this->language = $language;
	   		$this->oaiId = $oaiId;
			$this->log(INFO, "_construct: ".$this->oaiId." ".$this->subject );	
            
			if(Options::getOption('LiveUpdateDestination.useHashForOptimization')){
				$this->odbc = ODBC::getDefaultConnection();
				$this->hasHash = $this->_retrieveHashValues();
				$this->active = true;
			}
	   }
	
/*
 * get hash info from db
 * saves for internal processing
 * name: _retrieveHashValues
 * @param
 * @return true if an entry is in db
 */
	private function _retrieveHashValues(){
		
$sql = 
'Select '.FIELD_JSON_BLOB.' 
From '.TABLENAME.' 
Where '.FIELD_OAIID.' = '.$this->oaiId;
            
			$odbc_result = $this->odbc->exec($sql, 'Hash::_retrieveHashValues');
			
			$num = odbc_num_rows  ( $odbc_result  );
			if($num <= 0){
				$this->log(INFO,$this->subject.' : no hash found');
				return false;
				}
			
			odbc_longreadlen ($odbc_result, ODBC_MAX_LONGREAD_LENGTH);
			odbc_fetch_row($odbc_result);
			$data=false;
			do {
  				$temp = odbc_result($odbc_result, 1);
  				if ($temp != null) $data .= $temp;
			} while ($temp != null);
            
            $this->hashesFromStore =  json_decode ($data , true);
			if(!is_array($this->hashesFromStore)){
				$this->log(WARN,'conversion to JSON failed, not using hash this time');
				$this->log(WARN,$data);
				return false;
				}

			$this->log(INFO,$this->subject.' retrieved hashes from '.count(array_keys($this->hashesFromStore)).' extractors ');
			
			return true;

			
	 	}
	
	public function updateDB(){
		if($this->active == false ){
			return;
			}
		
			Timer::start('Hash::updateDB');
			$json = json_encode($this->newJSONObject);

		$sql = 
'Update '.TABLENAME.' 
Set '.FIELD_RESOURCE.' = ? , '.FIELD_JSON_BLOB.' = ?
Where '.FIELD_OAIID.' = '.$this->oaiId;

            $stmt = $this->odbc->prepare($sql , 'Hash::updateDB');
			$odbc_result = odbc_execute  ( $stmt , array($this->subject,  $json) );
			$needed = Timer::stopAsString('Hash::updateDB');
			if(false == $odbc_result){
				$this->log(ERROR,'FAILED update hashes '.$sql);
			}else{
				$this->log(INFO,$this->subject.' updated hashes for '.count($this->newJSONObject).' extractors '.$needed);
				
			}
		
	}
		
		
	public function insertIntoDB(){
			if(!$this->active){
				return;
				}
			Timer::start('Hash::insertIntoDB');
			$json = json_encode($this->newJSONObject);
			$sql = 
'Insert Into '.TABLENAME.'  
('.FIELD_OAIID.', '.FIELD_RESOURCE.' , '.FIELD_JSON_BLOB.' ) 
Values ( ?, ? , ?  ) ';

			$stmt = $this->odbc->prepare($sql , 'Hash::insertIntoDB');
			$odbc_result = odbc_execute  ( $stmt , array($this->oaiId, $this->subject , $json) );
			$needed = Timer::stopAsString('Hash::insertIntoDB');
			if($odbc_result == false){
				$this->log(WARN,'FAIL insert hashes for '.count($this->newJSONObject).' extractors');	
			}else{
				$this->log(DEBUG,$this->subject. ' inserted hashes for '.count($this->newJSONObject).' extractors '. $needed);
/*
				foreach(odbc_fetch_array($odbc_result) as $one){
					$this->log(DEBUG,$one);
					}
*/
			 }
			
		}

	   
  	private function _compareHelper($extractorID, $triples){

		 $matchcount = 0;
		 $addcount = 0;
		 $delcount = 0 ;
		  $total = 0;
		 
			//init
			if(!empty($triples)){
				$this->newJSONObject[$extractorID] = array();
			}
		
			
			$newHashSet = array();
			foreach ($triples as $triple ){
				$tmp = array();
				$tmp['s'] = $triple->getSubject()->toSPARULPattern();
				$tmp['p'] = $triple->getPredicate()->toSPARULPattern();
				$tmp['o'] = $triple->getObject()->toSPARULPattern();
				$tmp['triple'] = $triple;
				//using keys guarantees set properties
				$newHashSet[$triple->hashcode()] = $tmp;
				}
		 
		
		 foreach($newHashSet as $hash=>$tripleArray){
			 $total++;
			 	//triple exists
				if(isset($this->hashesFromStore[$extractorID][$hash])){
					$this->newJSONObject[$extractorID][$hash] = $this->hashesFromStore[$extractorID][$hash];
					unset($this->hashesFromStore[$extractorID][$hash]);
					$matchcount++;
				}else{
					//add it
					$this->addTriples[$hash] = $tripleArray['triple'];
					unset($tripleArray['triple']);
					$this->newJSONObject[$extractorID][$hash] = $tripleArray;
					$addcount++;	
				}
			 }
		foreach($this->hashesFromStore[$extractorID] as $hash => $triple) {
				$this->deleteTriples[$hash] = $triple;
				$delcount++;
			}
		
		$percent = ($total==0)?0:round(($matchcount/$total)*100,2);
		$this->log(DEBUG,  str_replace(DB_META_NS,'',$extractorID).", stats for diff (match, add, del, total): ($matchcount , $addcount, $delcount, $total [$percent%] )");
		 
	  }
	   
/*
 * returns the diff of the extractionresult with the db
 * used for collecting triples and compare them to the hash
 * name: compare
 * @param $extractionResult
 * @return
 */
	public function compare(ExtractionResult  $extractionResult){
			if($this->active == false){
				return;
				}
			
			Timer::start('Hash::compare');
			$extractorID = $extractionResult->getExtractorID();
			$triples = $extractionResult->getTriples();
			$nicename = str_replace(DB_META_NS,'',$extractorID);

			//first of all some exceptions, e.g. MetaInformationextractor
			if(!$this->hasHash){ //no saved hash, add to Hash
				//add
				$this->_addToJSON($extractorID, $triples);
			}else  if($this->_isExtractorInHash($extractorID)){
				//do the normal thing
				$this->_compareHelper($extractorID, $triples);
			}else {
				//add
				$this->_addToJSON($extractorID, $triples);
			}
			
			Timer::stop('Hash::compare');
	}
			
			/*
			 * Below applies if a hash is already in db
			 * */
			 	
			//extractor not found before, just add	
/*
			}else if(!$this->_isExtractorInHash($extractorID)){
				$this->_addToAddExtractors($extractorID, $triples);
			//extractor found in hash
			//but no triples
			}else if(){
				//don't do anything
			
			//triple number does not match	
			}else if ( isset($this->hashesFromStore[$extractorID]) && 
						($c1 = count($this->hashesFromStore[$extractorID])) != ($c2 = count($this->_generateHashSet($triples))) ){
				$this->_addToAddExtractors($extractorID, $triples);
				$this->log(DEBUG, $nicename.' scheduled for renewal, reason: different triple count. new: '.$c2.' in hash: '.$c1);
*/
/*
				$tmp = array();
				foreach ($triples as $triple){
					$nt =  $triple->toNTriples();
					$tmp[$nt]=true;
					echo $nt;
					}
				echo count(array_keys($tmp))."\n";
*/
			//all unmatched extractors from hash will be deleted anyhow	
/*
			}else if (isset($this->hashesFromStore[$extractorID])) {
				Timer::start('Hash::compare::hash_compare_keys');
				//assumption here that they are all unique
				$fromDB = $this->hashesFromStore[$extractorID];
				$fromDBflipped = array_flip($fromDB);
				//could be more elaborate here
				//there are some cases that could boost performance
				//e.g. only insert new triples for one extractor
				Timer::start('Hash::compare::hash_compare_keys::each_hashvalue');
				$test = array();
				foreach ($triples as $triple ){
					$test[$triple->hashcodeWithOaiId($this->oaiId)] = $triple;
				}
				foreach ($test as $hash=>$triple ){
					if(isset($fromDBflipped[$hash])){
						$found[$hash] = $triple;
						unset($fromDBflipped[$hash]);
					}else{
						$notfound[$hash] = $triple;
					}
				}
				Timer::stop('Hash::compare::hash_compare_keys::each_hashvalue');
				
				//hash is in sync
				if(empty($notfound) && empty ($fromDBflipped)){
					$this->_addToJSON($extractorID, $triples);
					$this-> _keepInStore($extractorID);
					$this->log(DEBUG, $nicename.' in accordance with hash');
				}else{
					$this->log(DEBUG, $nicename.' scheduled for renewal, reason: hashvalues changed');
					$this->_addToAddExtractors($extractorID, $triples);
				}
				Timer::stop('Hash::compare::hash_compare_keys');
			}
			
*/
		
		
		
/*
	private  _specialHandling(ExtractionResult $extractionResult){
			$extractorID = $extractionResult->getExtractorID();
			$triples = $extractionResult->getTriples();
			$retval = false;
			switch ($extractorID){
				case DB_META_NS.'MetaInformationExtractor':{
					//not added to JSONObject
					//but renew as it changes every time
					//no hash is needed
					$this->addExtractors[$extractorID] =  $triples;
					$this->deleteExtractors[] = $extractorID;
					$this->log(DEBUG,'MetaInformationExtractor, special handling, added to add extractors, scheduled for deletion');
					$retval = true;
					break;
					}
				default:{
					$retval = false;
					}
				
				}
			return $retval;
			
		}
*/
	
/*
	private  _keepInStore($extractorID){
			$nicename = str_replace(DB_META_NS,'',$extractorID);
			unset($this->hashesFromStore[$extractorID]);
			$this->log(INFO, $nicename.' will be kept in store');
		}
*/
	
	public function getTriplesToAdd(){
			$this->log(INFO, 'removing '.count($this->deleteTriples). ' previous triples ');
			return $this->addTriples;
		}
	public function getTriplesToDelete(){
			$this->log(INFO, 'adding '.count($this->addTriples). ' triples ');
			
			return $this->deleteTriples;
		}
	
	
	private function _isExtractorInHash($extractorID){
			return isset($this->hashesFromStore[$extractorID]);
		}
	
/*
 * 
 * name: _addToAddExtractors
 * @param
 * @return
 */
/*
	private  _addToAddExtractors($extractorID, $triples){
		if(count($triples)>0){
			$this->addExtractors[$extractorID] =  $triples;
			$nicename = str_replace(DB_META_NS,'',$extractorID);
			$this->log(DEBUG, $nicename.' added for loading');
			$this->_addToJSON($extractorID, $triples);
		}
	}
*/
	
/*
 * name: _addToJSON
 * @param
 * @return
 */
	private function _addToJSON($extractorID, $triples){
		Timer::start('Hash::compare::_addToJSON');
			if(!empty($triples)){
				$this->newJSONObject[$extractorID] = $this->_generateHashSet($triples);
				$nicename = str_replace(DB_META_NS,'',$extractorID);
				$this->log(DEBUG, ' added : '.count(array_keys($this->newJSONObject[$extractorID])).' of '. count($triples).' triples to JSON object for '.$nicename. '[removed duplicates]' );	
			}
		Timer::stop('Hash::compare::_addToJSON');
		}
		
	private function _generateHashSet( $triples){
			if(empty($triples))return array();
			
			Timer::start('Hash::compare::_generateHashSetFromTriples');
				$newHashSet = array();
				foreach ($triples as $triple ){
					$tmp = array();
					$tmp['s'] = $triple->getSubject()->toSPARULPattern();
					$tmp['p'] = $triple->getPredicate()->toSPARULPattern();
					$tmp['o'] = $triple->getObject()->toSPARULPattern();
					//using keys guarantees set properties
					$newHashSet[$triple->hashcode()] = $tmp;
					}
			Timer::stop('Hash::compare::_generateHashSetFromTriples');
			return $newHashSet;
	}
	

	public function hasHash(){
			return $this->hasHash;
		}
	
	private  function log($lvl, $message){
				Logger::logComponent('core', get_class($this), $lvl , $message);
		}
	
}


