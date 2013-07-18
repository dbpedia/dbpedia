<?php



class TripleDiff  {
	
	private $resource;
	private $language;
/*
	private $predicateFilterList = array();
	private $objectFilterList = array();
	private $predicateObjectFilterList = array();
*/
	private $producesFilterList = array();
	private $store;
	
	

public function __construct(URI $resource, $language,  $producesFilterList, $store){
		$this->resource = $resource;
		$this->language = $language;
		$this->producesFilterList = $producesFilterList;
		$this->store = $store;
	
	}
	
public function diff( $triplesFromExtractor){
		$query = $this->createSPARQLQuery();
		$triplesFromStore = $this->store->getRDFTriples($query);
		//print_r($this->resource);die;
		$result = array();
		$filteredoutExtractor = array(); //should be inserted
		$filteredoutStore = array(); //should be deleted, special handling of object
		$remainderExtractor = array();
		$remainderStore = array();
		$insert = array();
		$delete = array();
		$untouched_keys = array();
		$intersected = array();
		
		Timer::start("TripleDiff");
		// this is a first loop that filters all subject which are not the same as 
		// this article
		// all filtered objects are remembered
		//echo count($triplesFromExtractor)."\n";
		foreach ($triplesFromExtractor as $triple) {
			//filter all which do not have resource as subject
			if(!$this->resource->equals( $triple->getSubject())){
				$filteredoutExtractor[] = $triple;
			}else if($triple->getObject() instanceOf URI && self::isSubstring($this->resource->getURI(),$triple->getObject()->getURI())) {
				$filteredoutExtractor[] = $triple;
			}else {
				$remainderExtractor[] = $triple;
			}
		}// end for each
		
		foreach ($triplesFromStore as $triple) {
			//filter all which do not have resource as subject
			if($triple->getObject() instanceOf URI && self::isSubstring($this->resource->getURI(),$triple->getObject()->getURI() )){
				$filteredoutStore[] = $triple;
			}else {
				$remainderStore[] = $triple;
			}
		}// end for each
			//echo count($remainderExtractor)."\n";
		
			//i.e. property names for both
			$keysFromExt = $this->getPropertyNames($remainderExtractor);
			$keysFromStore = $this->getPropertyNames($remainderStore);
			$untouched_keys = array_diff($keysFromStore,  $keysFromExt ); ;

			//new properties
			//case 1 new property, just add
			$toBeAdded = array_diff($keysFromExt, $keysFromStore);
			//print_r($toBeAdded);die;
			$newRemainderExtractor = array();
			foreach($remainderExtractor as $triple){
					if(in_array($triple->getPredicate()->getURI(), $toBeAdded) ){
						$insert[] = $triple;
					}else{
						$newRemainderExtractor[] = $triple;
						}
				}
				
/*
			}
*/
			$remainderExtractor = $newRemainderExtractor;
			//echo count($remainderExtractor)."\n";
			
			
			// update existing properties
			// case 2 property known delete, insert
			// keep all from languages other than en
			$tobedeleted = array_intersect($keysFromExt, $keysFromStore) ;
			$newRemainderStore = array();
			$newRemainderExtractor = array();
			
			//print_r($tobedeleted);die;
			
			foreach($remainderStore as $triple){
				if(in_array($triple->getPredicate()->getURI(), $tobedeleted)){
						$delete[] = $triple;
				}else{
					$newRemainderStore[] = $triple;
				}
			}
			foreach($remainderExtractor as $triple){
					if(in_array($triple->getPredicate()->getURI(), $tobedeleted)){
						$insert[] = $triple;
					}else{
						$newRemainderExtractor[] = $triple;	
					}
				}
			$remainderExtractor = $newRemainderExtractor;
			$remainderStore = $newRemainderStore;
			//echo count($remainderExtractor)."\n";
			//echo count($remainderStore)."\n";
			//Timer::stop("TripleDiff");
			//	Timer::printTime();
			
/*
			$newinsert = array();
			$newdelete = array();
			$intersected = array();
			foreach($insert as $itrip){
				$newinsert[] = $itrip->toNTriples();
				}
			foreach($delete as $dtrip){
				$newdelete[] = $dtrip->toNTriples();
				}
			array_intersect($newdelete, $newinsert);
*/
/*
			foreach($insert as $itrip){
					foreach($delete as $dtrip){
							if($dtrip->equals($itrip)){
								$intersected[] = $dtrip;
							}else{
								$newinsert[] = $itrip;
								$newdelete[] = $dtrip;
								}
						}
				}
*/
/*
			$insert = $newinsert;
			$delete = $newdelete;
			count($remainderStore)."\n";
			count($remainderExtractor)."\n";
			
			Timer::printTime();
			die;
*/
		
		//Validation:
		if(	count($remainderStore) >0 || 	count($remainderExtractor) >0){
				$this->log(WARN, ' remaining triples:  '.count($remainderStore) .'|'. count($remainderExtractor));
				
				$tmp="";
				foreach($triplesFromStore as $triple){
						$tmp.=$triple->toNTriples()."";
					}
				//$this->log(WARN, " from store:  \n".$tmp);
				$tmp="";
				foreach($triplesFromExtractor as $triple){
						$tmp.=$triple->toNTriples()."";
					}
				//$this->log(WARN, " from extractor:  \n".$tmp);
				$tmp="";
				foreach($remainderStore as $triple){
						$tmp.=$triple->toNTriples()."";
					}
				$this->log(INFO, " left from store:  \n".$tmp);
				
				$tmp="";
				foreach($remainderExtractor as $triple){
						$tmp.=$triple->toNTriples()."";
					}
				$this->log(INFO, " left from extactor:  \n".$tmp);
				//die();
			}
		
			
		$result['filteredoutExtractor'] = $filteredoutExtractor;
		$result['filteredoutStore'] = $filteredoutStore;
/*
		$result['remainderExtractor'] = $remainderExtractor;
*/
		$result['remainderStore'] = $remainderStore ;
		$result['insert'] = $insert;
		$result['delete'] = $delete;
		
		//print_r($untouched_keys);die;
		
/*
		foreach(array_keys($result) as $key){
				foreach ($result[$key] as $triple){
					$this->log(DEBUG, ' '.$key.': '.$triple->toNTriples());
					}
			}
	
		die;
*/
		Timer::stop("TripleDiff");
		
		return $result;
	}
	
	
	
	public function simplerDiff( $triplesFromExtractor){
		$query = $this->createSPARQLQuery();
		//echo $query;die;
	 /**
    * sub,pred,obj, must be an array, either:
	* [action] = "fix"
	* [value]  = "http://dbpedia.org/resource/subject"
	* or a sparql variable ?o like:
	* [action] = "variable"
	* [value]  = "o"
	* * or use $this->subject:
	* [action] = "classattribute"
	* [value]  = null
	* */
/*
		$pvar = "?p";
		$ovar = "?o";
		$query = "SELECT * WHERE {<".$this->resource->getURI()."> ".$pvar." ".$ovar. " \n";
*/
		$s = array('action'=>'classattribute', 'value'=>null);
		$p = array('action'=>'variable', 'value'=>'p');
		$o = array('action'=>'variable', 'value'=>'o');
		$triplesFromStore = $this->store->getRDFTriples($query, $s, $p, $o);
		$result = array();
		$differentSubjectExtractor = array(); //should be inserted
		$subResourceAsObjectExtractor = array(); //should be inserted
		$subResourceAsObjectStore = array(); //should be deleted, special handling of object
		$remainderExtractor = array();
		$remainderStore = array();
		$insert = array();
		$delete = array();
		$untouched_keys = array();
		$intersected = array();
		
		Timer::start("TripleDiff");
		// this is a first loop that filters all subject which are not the same as 
		// this article
		// all filtered objects are remembered
		//echo count($triplesFromExtractor)."\n";
		foreach ($triplesFromExtractor as $triple) {
			
			//filter all which do not have resource as subject
			if(!$this->resource->equals( $triple->getSubject())){
				$differentSubjectExtractor[] = $triple;
			// filter out London/review/rating in Object
			}else if($triple->getObject() instanceOf URI && self::isSubstring($this->resource->getURI(),$triple->getObject()->getURI())) {
				$subResourceAsObjectExtractor[] = $triple;
			}else {
				$remainderExtractor[] = $triple;
			}
		}// end for each

				
		foreach ($triplesFromStore as $triple) {
			//filter all which do not have resource as subject
			if($triple->getObject() instanceOf URI && self::isSubstring($this->resource->getURI(),$triple->getObject()->getURI() )){
				$subResourceAsObjectStore[] = $triple;
			}else {
				$remainderStore[] = $triple;
			}
		}// end for each
		
		/*
		 * For debugging 
		 * remove later
		 * 
		 * */
		foreach ($differentSubjectExtractor as $triple) {
			if(!self::isSubstring($this->resource->getURI(),$triple->getSubject()->getURI())) {
				$this->log(WARN, "Found :\n".$triple->toNTriples());
				}
		}//end for each
			
		$result['subResourceAsObjectStore'] = $subResourceAsObjectStore;
		
		//$result['subResourceAsObjectExtractor'] = $subResourceAsObjectExtractor;
		//$result['differentSubjectExtractor'] = $differentSubjectExtractor;

		$result['triplesFromStore'] = $triplesFromStore ;
		//$result['remainderStore'] = $remainderStore ;
		//$result['remainderExtractor'] = $remainderStore ;
		
		Timer::stop("TripleDiff");
		
		return $result;
	}
	
	public static function isSubstring ($small, $big){
			return (strpos($big,$small) === 0 && strlen($big)>strlen($small));
		}
	
	private function getPropertyNames($triples){
			$result = array();
			foreach($triples as $triple){
					$result[] = $triple->getPredicate()->getURI();
				
				}
			return $result;
		}
	
	private  function log($lvl, $message){
			
				Logger::logComponent('destination', get_class($this), $lvl , $message);
		}


private function createSPARQLQuery(){
		$pvar = "?p";
		$ovar = "?o";
		$query = "SELECT * WHERE {<".$this->resource->getURI()."> ".$pvar." ".$ovar. " \n";
		$tmpFilter = self::createFilter($this->producesFilterList);
		$tmpFilter = (strlen(trim($tmpFilter)) > 0) ? "FILTER( \n".$tmpFilter. "). " : " ";
		$query  .= $tmpFilter."}";
		return $query;
	
	}


public static function createFilter($producesFilterList, $pVar = '?p', $oVar='?o') {
		$piris="";
		$oiris="";
		$terms = array();
		foreach ($producesFilterList as $rule){
			$error = false;
			if($rule['type']==STARTSWITH){
					if(!empty($rule['p']) && !empty($rule['o'])) {
						
						if(isset($rule['pexact'])&& $rule['pexact']) {
							$t1 =  $pVar . ' !=  <'. $rule['p'] .'> ';
							$t2 = self::notcurrent($oVar, $rule['o']);
							$terms[]= self::assembleTerms(array($t1,$t2), '||');
						}else{
							$t1 = self::notcurrent($pVar, $rule['p']);
							$t2 = self::notcurrent($oVar, $rule['o']);
							$terms[]= self::assembleTerms(array($t1,$t2), '||');
						}
					}else if(!empty($rule['p'])){
						$terms[] = self::notcurrent($pVar, $rule['p']);
					}else if(!empty($rule['o'])){
						$terms[] = self::notcurrent($oVar, $rule['o']);
					}else {
						$error = true;
					}
			}else if($rule['type']==EXACT){
					if(!empty($rule['p']) && !empty($rule['o'])) {
						$t1 = $pVar.' !=  <'. $rule['p'] . '>';
						$t2 = $oVar.' !=  <'. $rule['o'] . '>';
						$terms[]= self::assembleTerms(array($t1,$t2), '||');
						
					}else if(!empty($rule['p'])){
							$piris[] = "\n<{$rule['p']}>";
					}else if(!empty($rule['o'])){
							$oiris[] = "\n<{$rule['o']}>";
					}else {
						$error = true;
					}
			}
			
			if($error) {
				Logger::error("TripleDiff: Uninterpretable filter in one Extractor ");
				ob_start();
				// write content
				print_r($rule);
				$content = ob_get_contents();
				ob_end_clean();
				Logger::error("\n$content");
				die;
			}
		}
		if(!empty($piris)){
			$terms[] ='!('.$pVar.' in ( '.implode(",", $piris).'))';
		}
		if(!empty($oiris)){
			$terms[] ='!('.$oVar.' in ( '.implode(",", $oiris).'))';
		}

		//FILTER (!(p in (<http://www.w3.org/...>, <second IRI>, <third IRI>...))
		
		return self::assembleTerms($terms, '&&');

	}
		 
	public static function assembleTerms($terms, $op) {
		if(!($op == '&&' || $op == '||')){
				die('wrong operator in assembleTerms TripleDiff.php '.$op);
			}
		$retval = "";
		Timer::start('TripleDiff::assembleTerms');
		if (count($terms)==0)
			$retval = "";
		else if (count($terms) == 1)
			$retval = "(".$terms[0].")";
		else {
			//$op = "&&";
			$ret='';
			foreach ($terms as $one){
				if(strlen($ret)==0){
					$ret .= "(".$one.")";
					}
				else{
					$ret .= "\n".$op;
					$ret .= "(".$one.")";
				}	
			}
			
			$retval = "(".$ret.")";
		}
		Timer::stop('TripleDiff::assembleTerms');
		return $retval;

	}	 
	
	public static function notregex($var, $f){
			return  "!regex(str(".$var. "), '^" . $f . "')";
		}
	
	public static function notlike($var, $f){
			return  "!( ".$var. " LIKE <" . $f . "%> )";
		}
		
	public static function notcurrent($var, $f){
			return self::notlike($var, $f);
		}


}

