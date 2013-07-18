<?php

define("LUD_STORE", 'lud_store');
define("LUD_SPARQLFILTER", 'lud_sparqlfilter');
define("LUD_SPARQLFILTERWITHLANGUAGES", 'lud_sparqlfilterwithLanguages');
define("LUD_SPARULFORLANGUAGES", 'lud_sparulforlanguages');


class LiveUpdateDestination implements Destination {
	
	/*
	 * Options they should be initiialized 
	 * only once at the beginning
	 * */
	private $uri;
	private $language;
	private $oaiId;
	
	private $graphURI;
    private $annotationGraphURI;
	private $generateOWLAxiomAnnotations;
	private $languageProperties;
	private $debug_turn_off_insert;
	private $debug_run_tests;
	
	private $hash;
	
	//helpers
	private $tiplesFromExtractors = array();
	private $odbc;
	private $subjectSPARULpattern;
	
	//statistic
	private $counterInserts = 0;
	private $counterDelete = 0;
	private $counterTotalODBCOperations = 0;
	
	
	//this is set in ExtractionGroup
	private $activeExtractors = array();
	private $purgeExtractors = array();
	//this is set in ExtractionGroup
	public function addActiveExtractor($extractorID){
			$this->activeExtractors[]=$extractorID;
		}
	public function addPurgeExtractor($extractorID){
			$this->purgeExtractors[]=$extractorID;
		}

	//this is set in ExtractionGroup
	//private $predicateFilterList = array();
	//private $objectFilterList = array();
	
/*
	array(	array(
	* 			'p'=>'rdf:type'
	* 			'o'=>'yagoclass'
	* 			),
	* 		array(
	* 			'p'=>'rdf:type'
	* 			'o'=>'umbel'
	* 			) 
		)
*/	//example above
	//private $predicateObjectFilterList = array();
	private $producesFilterList = array();
	
	//collects all triples in accept
	public $tripleFromExtractor = array(); 
	
	function __construct($metainfo){
			$this->uri = RDFtriple::page($metainfo['pageTitle']);
			$this->language = $metainfo['language'];
			$this->oaiId = $metainfo['oaiId'];
			
			$this->graphURI  = Options::getOption('graphURI');
        	$this->annotationGraphURI = Options::getOption('annotationGraphURI');
			$this->generateOWLAxiomAnnotations = Options::getOption('generateOWLAxiomAnnotations');
			$this->languageProperties = Options::getOption('stringPredicateWithForeignlanguages');
			$this->debug_turn_off_insert = Options::getOption('debug_turn_off_insert');
			$this->debug_run_tests = Options::getOption('debug_run_tests');
			
			$this->hash = new Hash($this->oaiId, $this->uri->getURI());
			//$this->metainfo = $metainfo;
			
			$this->subjectSPARULpattern = $this->uri->toSPARULPattern();
			
			if(Options::isOptionSet('predicateFilter')){
				$p = Options::getOption('predicateFilter');
				$this->log(WARN, 'currently not working');
			}
			if(Options::isOptionSet('objectFilter')){
				$o = Options::getOption('objectFilter');
				$this->log(WARN, 'currently not working');
			}
			if(Options::isOptionSet('predicateObjectFilter')){
				$po = Options::getOption('predicateObjectFilter');
				$this->log(WARN, 'currently not working');
			}
			
		}
	
	
	/*
	 * 
	 * */	
	//this is set in ExtractionGroup
	//they are the produces entries from ExtractorConfigurator
	public function addFilter($filter){
			foreach ($filter as $one){
				$this->producesFilterList[] = $one;
				}
		}
	
/*
	public function addPredicateFilter($pfilter){
			$this->objectFilterList[] = $pfilter;
		}
*/
		
    public function start() { 	}
		
    public function accept($extractionResult) {
			$triples = $extractionResult->getTriples();
			foreach ($triples as $triple){
					$this->tripleFromExtractor[]=$triple;
				}
			//should always be called, as it collects the new Json Object
			$this->hash->compare($extractionResult);
    }
    
	private function _prepare($languageProperties){
		if(!TheContainer::wasSet(LUD_SPARQLFILTER)){
			$store =null;
			$tripleDiff = new TripleDiff($this->uri,$this->language ,$this->producesFilterList, $store);
			TheContainer::set(LUD_SPARQLFILTER , $tripleDiff->createFilter($this->producesFilterList));
			}
		
		if(!TheContainer::wasSet(LUD_SPARQLFILTERWITHLANGUAGES)){
			$store =null;
			$producesFiltertmp = $this->producesFilterList;
			foreach($languageProperties as $one){
				$producesFiltertmp[] = array('type'=>EXACT, 's'=>'','p'=>$one,'o'=>'');
				}
			$tripleDiff = new TripleDiff($this->uri,$this->language ,$producesFiltertmp, $store);
			TheContainer::set(LUD_SPARQLFILTERWITHLANGUAGES , $tripleDiff->createFilter($producesFiltertmp));
			}
			
	}
    
    public function finish() { 
		$strategy = Options::getOption('LiveUpdateDestination.strategy');
		$primary = true;
		$secondary = false;
		switch ($strategy){
			case 'both' : {
					$primary = true;
					$secondary = true;
					break;
				}
			case 'primary' : {
				$primary = true;
				$secondary = false;
				break;
				
				}
			case 'secondary' : {
				$primary = false;
				$secondary = true;
				break;
				
				}
			
			}
		
			
		if($this->hash->hasHash()){
			Timer::start('LiveUpdateDestination::_hashedUpdate_Strategy');
			$addTriples = $this->hash->getTriplesToAdd();
			$deleteTriples = $this->hash->getTriplesToDelete();
			//update db
			$this->hash->updateDB();
			//update triples
			$this->_hashedUpdate($addTriples, $deleteTriples);
			Timer::stop('LiveUpdateDestination::_hashedUpdate_Strategy');
			
		}else {
			
			if($primary){	
				Timer::start('LiveUpdateDestination::_primaryStrategy');
				$this->_primaryStrategy();
				Timer::stop('LiveUpdateDestination::_primaryStrategy');
			}
			if($secondary){
				Timer::start('LiveUpdateDestination::_alternativeStrategy');
				$this->_alternativeStrategy();
				Timer::stop('LiveUpdateDestination::_alternativeStrategy');
			}
			//does nothing if not active
			$this->hash->insertIntoDB();
		}
	
    }
	
	private function _hashedUpdate($addTriples, $deleteTriples){
			/*
			 * PREPARATION
			 * 
			 * */
			
			$this->odbc = ODBC::getDefaultConnection();
			if(!empty($deleteTriples)){
				$this->_alt_delete_all_triples($deleteTriples);
				$this->_alt_delete_all_annotations_for_triples($deleteTriples);
				
			}
		
		/*
		 * STRATEGIES FOR INSERTION
		 * will do nothing if Options::getOption('debug_turn_off_insert') is true
		 * */
		//$this->_odbc_sparul_insert_triples_and_annotations($graphURI,$annotationGraphURI,$generateOWLAxiomAnnotations);
		if(!empty($addTriples)) {	
			$this->_odbc_ttlp_insert_triples($addTriples);
			$this->_odbc_ttlp_insert_annotations($addTriples);
		}
		
	}
	
	
	private function _primaryStrategy(){
		/*
		 * PREPARATION
		 * 
		 * */
		
		$languageProperties = $this->languageProperties;
		$this->_prepare($languageProperties);
		$this->odbc = ODBC::getDefaultConnection();
		$graphURI  = $this->graphURI ; 
        $annotationGraphURI  = $this->annotationGraphURI ; 
		$generateOWLAxiomAnnotations = $this->generateOWLAxiomAnnotations ; 
		/*
		 * STRATEGIES FOR DELETION
		 * */
		
		Timer::start('LiveUpdateDestination::_odbc_sparul_delete_total');
		$this->_odbc_clean_sparul_delete_subresources();
		$this->_odbc_sparul_delete_subject_not_static($graphURI,$this->subjectSPARULpattern , TheContainer::get(LUD_SPARQLFILTER ) );
		
		//$this->_odbc_sparul_delete_language($graphURI, $languageProperties );
		//$this->_odbc_sparul_delete_language_oneQuery($graphURI, $languageProperties );
		//alternative: $this->_http_retrieve_odbc_sparul_delete_language($languageProperties, $graphURI);
		if($generateOWLAxiomAnnotations){
			//these 3 clearly depend on annotations
			$this->_odbc_sparul_delete_annotations($annotationGraphURI);
			//$this->_odbc_sparul_delete_subresources();
			
			$this->_odbc_sparul_delete_annotations_of_subresources();
		}		
		Timer::stop('LiveUpdateDestination::_odbc_sparul_delete_total');
		
		/*
		 * STRATEGIES FOR INSERTION
		 * will do nothing if Options::getOption('debug_turn_off_insert') is true
		 * */
		//$this->_odbc_sparul_insert_triples_and_annotations($graphURI,$annotationGraphURI,$generateOWLAxiomAnnotations);
		$this->_odbc_ttlp_insert_triples($this->tripleFromExtractor);
		$this->_odbc_ttlp_insert_annotations($this->tripleFromExtractor);
	
		$this->log(INFO, 'no of queries, insert: '.$this->counterInserts.' delete: '.$this->counterDelete.' odbc_total: '.$this->counterTotalODBCOperations);
/*
		if(Options::getOption('writeSPARULtoFiles')){
			$this->writeSPARULtoFiles($deleteSPARUL, $insertSPARUL);
		}
*/
		
		}
	
	private function _alternativeStrategy(){
		
		/*
		 * PREPARATION
		 * 
		 * */
		$languageProperties = $this->languageProperties;
		//$this->_prepare($languageProperties);
		$this->odbc = ODBC::getDefaultConnection();
		$graphURI  = $this->graphURI ; 
        $annotationGraphURI  = $this->annotationGraphURI ; 
		$generateOWLAxiomAnnotations = $this->generateOWLAxiomAnnotations ; 
		
		//echo TheContainer::get(LUD_SPARQLFILTERWITHLANGUAGES ) ;
		/*GET TRIPLES*/
		$store = new SPARQLToRDFTriple($this->uri, $this->language);
		$tripleDiff = new TripleDiff($this->uri,$this->language ,$this->producesFilterList, $store);
		
		$diff = $tripleDiff->simplerDiff($this->tripleFromExtractor);
		$this->odbc = ODBC::getDefaultConnection();
		$fromStore = $diff['triplesFromStore'];
		$subResourceAsObjectStore = $diff['subResourceAsObjectStore'];
		$this->_alt_delete_all_triples_RDFTripleArray(  $fromStore);
		$this->_alt_delete_subresources( $subResourceAsObjectStore);
		if($generateOWLAxiomAnnotations) {
			$this->_odbc_sparul_delete_annotations();
			$this->_odbc_sparul_delete_Annotations_of_subResources('alternative');
		}
		
		/*
		 * STRATEGIES FOR INSERTION
		 * */
		Timer::start('LiveUpdateDestination::_odbc_insert_total');
		//$this->_odbc_sparul_insert_triples_and_annotations($graphURI,$annotationGraphURI,$generateOWLAxiomAnnotations);
		$this->_odbc_ttlp_insert_triples($this->tripleFromExtractor);
		$this->_odbc_ttlp_insert_annotations($this->tripleFromExtractor);
		//$this->_odbc_ttlp_insert_triples_and_annotations($this->tripleFromExtractor);
		Timer::stop('LiveUpdateDestination::_odbc_insert_total');
		$this->log(INFO, 'no of queries, insert: '.$this->counterInserts.' delete: '.$this->counterDelete.' odbc_total: '.$this->counterTotalODBCOperations);
		
		}
		
	
	private function _alt_delete_all_triples_RDFTripleArray( $fromStore){
			$tmparray = array();

			foreach ($fromStore as $triple){
					$tmp = array();
					$tmp['s'] = $triple->getSubject()->toSPARULPattern();
					$tmp['p'] = $triple->getPredicate()->toSPARULPattern();
					$tmp['o'] = $triple->getObject()->toSPARULPattern();
					$tmparray[] = $tmp;
			}
			$this->_alt_delete_all_triples( $tmparray);
		}
	
	private function _alt_delete_all_triples( $fromStore){
			Timer::start('LiveUpdateDestination::_alt_delete_all_triples');
			$sparul = "";
			$pattern = "";
			foreach ($fromStore as $hash=>$triple){
					$pattern .= $triple['s'].' '.$triple['p'].' '.$triple['o']." . \n";
				}
			$sparul = "Delete From <{$this->graphURI}> { $pattern }";
			
			//TESTS>>>>>>>>>>>>
			if(Options::getOption('debug_run_tests')){
				$countbefore = $this->_testsubject($this->uri->getURI(), $this->graphURI);
			}
			//TESTS<<<<<<<<<<<
			
 			$this->counterDelete +=1;
 			$result = $this->_odbc_sparul_execute($sparul);
			if(false==$result){
				$this->log(DEBUG,'using fallback strategy (deleting single triples)' );
				foreach ($fromStore as $hash=>$triple){
					$pattern = $triple['s'].' '.$triple['p'].' '.$triple['o']." . \n";
					$sparul = "Delete From <{$this->graphURI}> { $pattern }";
					$this->_odbc_sparul_execute($sparul);
				}
			}
			$needed = Timer::stopAsString('LiveUpdateDestination::_alt_delete_all_triples');
			$this->log(DEBUG,'alt: deleted '.count($fromStore). ' triples directly'.$needed  );
			//echo $sparul;			
			//TESTS>>>>>>>>>>>>
			if(Options::getOption('debug_run_tests')){
				$countafter =  $this->_testsubject($this->uri->getURI(), $this->graphURI);
				$this->log(INFO,'TEST _alt_delete_all_triples, before: '.$countbefore. ' after: '.$countafter.' triples');
				if($countafter >= $countbefore ){
					$this->log(WARN,'TEST FAILED, AFTER SHOULD BE SMALLER');
				}  else{
					$this->log(INFO, 'SUCCESS');	
				}
			}
			
		}
		
	private function _alt_delete_all_annotations_for_triples_RDFTripleArray( $fromStore){
		$tmparray = array();

		foreach ($fromStore as $triple){
				$tmp = array();
				$tmp['s'] = $triple->getSubject()->toSPARULPattern();
				$tmp['p'] = $triple->getPredicate()->toSPARULPattern();
				$tmp['o'] = $triple->getObject()->toSPARULPattern();
				$tmparray[] = $tmp;
		}
		$this->_alt_delete_all_annotations_for_triples( $tmparray);
	}
	
	//slower than _odbc_sparul_delete_annotations, do not use
	private function _alt_delete_all_annotations_for_triples( $fromStore){
			Timer::start('LiveUpdateDestination::_alt_delete_all_annotations_for_triples');
			//$sparul = "";

	
			foreach ($fromStore as $triple){
				$axiomID = RDFtriple::recoverOWLAxiomId($this->oaiId, $triple['s'], $triple['p'], $triple['o']);
				$sparul = "Delete 
From <{$this->annotationGraphURI}>  {<$axiomID> ?p ?o  } 
From <{$this->annotationGraphURI}>  {<$axiomID> ?p ?o  } ";
				//TESTS>>>>>>>>>>>>
				if(Options::getOption('debug_run_tests')){
						$countbefore =  $this->_testsubject($axiomID, $this->annotationGraphURI);
				}
				//TESTS>>>>>>>>>>><<
				$odbc_result = $this->_odbc_sparul_execute($sparul);
				
				//TESTS>>>>>>>>>>>>
				if(Options::getOption('debug_run_tests')){
						$countafter =  $this->_testsubject($axiomID, $this->annotationGraphURI);
						
						$this->log(INFO,'TEST _alt_delete_all_annotations_for_triples, before: '.$countbefore. ' after: '.$countafter.' triples');
						
						if($countafter >= $countbefore && $countbefore >0){
							$this->log(WARN,'TEST FAILED, BEFORE SHOULD BE BIGGER THAN AFTER');
						}else{
							$this->log(INFO, 'SUCCESS');	
						}
					}
				//TESTS<<<<<<<<<<<<


		
		   }
		   $needed = Timer::stopAsString('LiveUpdateDestination::_alt_delete_all_annotations_for_triples');
		   $this->log(DEBUG,'alt: deleted annotations for '.count($fromStore). ' triple directly'.   $needed );
		 
	
	
	}
	//slower than _odbc_sparul_delete_annotations, do not use
/*
	private  _alt_delete_all_annotations_for_triples_old( $fromStore){
			Timer::start('LiveUpdateDestination::_alt_delete_all_annotations_for_triples');
			$sparul = "";
			foreach ($fromStore as $triple){
				$this->counterDelete+=1;
			$sparul = 'Delete From <'.$this->annotationGraphURI.'> 
{ ?axiom ?axp ?axo } Where {
	?axiom <'.OWL_SUBJECT.'> '.$triple['s'].' .
	?axiom <'.OWL_PREDICATE.'> '.$triple['p'].' .
	?axiom <'.OWL_OBJECT.'> '.$triple['o'].' .
	?axiom ?axp  ?axo . } ';
	 			$this->_odbc_sparul_execute($sparul);
		   }
		   $needed = Timer::stopAsString('LiveUpdateDestination::_alt_delete_all_annotations_for_triples');
		   $this->log(DEBUG,'alt: deleted annotations for '.count($fromStore). ' triple directly'.   $needed );
		 
	
	
	}
*/
	private function _alt_delete_subresources(  $subresourceFromStore){
		Timer::start('LiveUpdateDestination::_alt_delete_subresources');
			foreach ($subresourceFromStore as $triple){
				$this->counterDelete+=1;
				$sparul = "";
				$sparulpattern = $triple->getObject()->toSPARULPattern();
				$sparul = 'DELETE FROM <'.$this->graphURI.'> 
{ '.$sparulpattern.' ?p ?o } WHERE 
{ '.$sparulpattern.' ?p ?o } ' ;
	 			$this->_odbc_sparul_execute($sparul);
				
			}
		$needed = Timer::stopAsString('LiveUpdateDestination::_alt_delete_subresources');
		 $this->log(DEBUG,'alt: deleted '.count($subresourceFromStore). ' subresources directly'.$needed);
		
		
	}
		
	private function _testwherepart($testwhere , $graphURI){
			$g='';
			if($graphURI!=false){
				$g = ' FROM <'.$graphURI.'> ';
				}
			$testquery = 'SELECT count(*) as ?count '.$g.$testwhere;
			//echo $testquery;
			$se = SPARQLEndpoint::getDefaultEndpoint();
			return $se->executeCount($testquery, get_class($this), $graphURI);
	}
	
	private function _testsubject($subject , $graphURI){
			$g='';
			if($graphURI!=false){
				$g = ' FROM <'.$graphURI.'> ';
				}
			$testquery = 'SELECT count(*) as ?count '.$g.' {<'.$subject.'> ?p ?o}';
			//echo $testquery;
			$se = SPARQLEndpoint::getDefaultEndpoint();
			return $se->executeCount($testquery, get_class($this), $graphURI);
	}
	
	private function _odbc_sparul_delete_subject_not_static($graphURI, $subjectpattern, $filterWithLang ){
			
			 //***********************
			 //DELETE ALL NON STATIC TRIPLES
			 //**********************
			 //delete all triples with the current subject 
			 //according to the filters
			 //do not delete special properties see below
			 $tmpFilter = (strlen(trim($filterWithLang)) > 0) ? "FILTER( \n".$filterWithLang. "). " : " ";
 			$sparul = 
'DELETE  FROM <' . $graphURI . '> 
	{ '.$subjectpattern.' ?p ?o } 
FROM <' . $graphURI . '>  
';	
			$where = ' WHERE { 
	'.$subjectpattern.' ?p ?o .
	'.$tmpFilter.'
}'; 
		$sparul .= $where;
		//TESTS>>>>>>>>>>>>
		if(Options::getOption('debug_run_tests')){
				$countbefore = $this->_testwherepart($where, $graphURI);
			}

		Timer::start('LiveUpdateDestination::_odbc_sparul_delete_subject_not_static');
		
		if($this->_odbc_sparul_execute($sparul)){
			$this->counterDelete+=1;
			};
		$needed = Timer::stopAsString('LiveUpdateDestination::_odbc_sparul_delete_subject_not_static');
		$this->log(DEBUG,'deleted subject_not_static, needed '.$needed);
		
		//TESTS>>>>>>>>>>>>
		if(Options::getOption('debug_run_tests')){
				$countafter = $this->_testwherepart($where, $graphURI);
				$this->log(INFO,'TEST delete_subject_not_static, before: '.$countbefore. ' after: '.$countafter.' triples');
				if($countafter > 0 && $countbefore > 0){
					$this->log(WARN,'TEST FAILED, AFTER SHOULD BE 0');
				}  else{
					$this->log(INFO, 'SUCCESS');	
				}
			}
		
	}
	
	//SPECIAL function just for this extractor
	private function _hash_odbc_sparul_delete_MetaInformationExtractor( ){
		$sparul= 
'DELETE  FROM <' . $this->graphURI . '> 
	{ '.$this->subjectSPARULpattern.' ?p  ?o .  }  
 FROM <' . $this->graphURI . '> 
';
		$where = 'WHERE { 
 '.$this->subjectSPARULpattern.' ?p  ?o .  
FILTER (?p IN (<'.DBM_REVISION.'>,<'.DBM_EDITLINK.'>, <'.DBM_OAIIDENTIFIER.'>,<'.DC_MODIFIED.'>))
}';	

$sparul .= $where;
		//TESTS>>>>>>>>>>>>
		if(Options::getOption('debug_run_tests')){
				$countbefore = $this->_testwherepart($where,$this->graphURI);
			}
		//TESTS<<<<<<<<<<<
		Timer::start('LiveUpdateDestination::_hash_odbc_sparul_delete_MetaInformationExtractor');
		if($this->_odbc_sparul_execute($sparul)){
			$this->counterDelete+=1;
			};
		$needed = Timer::stopAsString('LiveUpdateDestination::_hash_odbc_sparul_delete_MetaInformationExtractor');
		$this->log(DEBUG,'deleted _hash_MetaInformationExtractor, needed '.$needed );
		
		//TESTS>>>>>>>>>>>>
		if(Options::getOption('debug_run_tests')){
				$countafter = $this->_testwherepart($where , $this->graphURI);
				
				$this->log(INFO,'TEST _hash_MetaInformationExtractor, before: '.$countbefore. ' after: '.$countafter.' triples');
				
				if($countafter >= $countbefore && $countbefore >0){
					$this->log(WARN,'TEST FAILED, BEFORE SHOULD BE BIGGER THAN AFTER');
				}else{
					$this->log(INFO, 'SUCCESS');	
				}
			}
		//TESTS<<<<<<<<<<<<
		

	}
	//SPECIAL function just for this extractor
	private function _hash_odbc_sparul_delete_MetaInformationExtractor_Annotations( ){
			$sparul= 
'DELETE  FROM <' . $this->annotationGraphURI . '> 
	{ ?axiom ?axp  ?axo .  }  
FROM <' . $this->annotationGraphURI . '> 
';
			$where = 'WHERE { 
	?axiom <'.DBM_ORIGIN.'>  <'.DB_META_NS.'MetaInformationExtractor> .  
	?axiom <'.OWL_SUBJECT.'> '.$this->subjectSPARULpattern.' .
	?axiom ?axp  ?axo .
}';	

$sparul .= $where;
		//TESTS>>>>>>>>>>>>
		if(Options::getOption('debug_run_tests')){
				$countbefore = $this->_testwherepart($where,$this->annotationGraphURI);
			}
		//TESTS<<<<<<<<<<<
		Timer::start('LiveUpdateDestination::_hash_odbc_sparul_delete_MetaInformationExtractor_Annotations');
		if($this->_odbc_sparul_execute($sparul)){
			$this->counterDelete+=1;
			};
		$needed = Timer::stopAsString('LiveUpdateDestination::_hash_odbc_sparul_delete_MetaInformationExtractor_Annotations');
		$this->log(DEBUG,'deleted _hash_MetaInformationExtractor_Annotations, needed '.$needed );
		
		//TESTS>>>>>>>>>>>>
		if(Options::getOption('debug_run_tests')){
				$countafter = $this->_testwherepart($where , $this->annotationGraphURI);
				
				$this->log(INFO,'TEST _hash_MetaInformationExtractor_Annotations, before: '.$countbefore. ' after: '.$countafter.' triples');
				
				if($countafter >= $countbefore && $countbefore >0){
					$this->log(WARN,'TEST FAILED, BEFORE SHOULD BE BIGGER THAN AFTER');
				}else{
					$this->log(INFO, 'SUCCESS');	
				}
			}
		//TESTS<<<<<<<<<<<<
		

	}

	
	private function _hash_odbc_sparul_delete_invalid_triples( $extractorsToDelete, $filtervar, $filter ){
		
			 //***********************
			 //DELETE ALL INVALID TRIPLES
			 //**********************
			 //delete all triples with the current subject 
			 //according to the filters
			 /*DELETE FROM <http://dbpedia.org> 
{<http://dbpedia.org/resource/Bughouse_chess> ?p ?o}

FROM <http://dbpedia.org/meta> 
WHERE { 

?axiom <http://dbpedia.org/meta/origin>  <http://dbpedia.org/meta/MetaInformationExtractor> .  
?axiom <http://www.w3.org/2002/07/owl#annotatedSource> <http://dbpedia.org/resource/Bughouse_chess> .
?axiom <http://www.w3.org/2002/07/owl#annotatedProperty> ?p .
?axiom <http://www.w3.org/2002/07/owl#annotatedTarget> ?o .
}
			  * */
			 
 			$sparul= 
'DELETE  FROM <' . $this->graphURI . '> 
	{ '.$this->subjectSPARULpattern.' ?p  ?o .  }  
 FROM <' . $this->annotationGraphURI . '> 
';
			$where = 'WHERE { 
	?axiom <'.DBM_ORIGIN.'>  '.$filtervar.' .  
	?axiom <'.OWL_SUBJECT.'> '.$this->subjectSPARULpattern.' .
	?axiom <'.OWL_PREDICATE.'> ?p .
	?axiom <'.OWL_OBJECT.'> ?o .
	'.$filter.' .
	
}';	

$testwhere = 'WHERE { 
	GRAPH <'.$this->graphURI.'> {
		'.$this->subjectSPARULpattern.' ?p ?o
	}
	GRAPH <'.$this->annotationGraphURI.'> {
		
	?axiom <'.DBM_ORIGIN.'>  '.$filtervar.' .  
	?axiom <'.OWL_SUBJECT.'> '.$this->subjectSPARULpattern.' .
	?axiom <'.OWL_PREDICATE.'> ?p .
	?axiom <'.OWL_OBJECT.'> ?o .
	'.$filter.' .
	}
}';	
/*
 * GRAPH <'.$this->graphURI.'> {
		'.$this->subjectSPARULpattern.' ?p ?o
		}
 * */
		$sparul .= $where;
		//TESTS>>>>>>>>>>>>
		if(Options::getOption('debug_run_tests')){
				$countbefore = $this->_testwherepart($testwhere,false);
			}
		//TESTS<<<<<<<<<<<
		Timer::start('LiveUpdateDestination::_hash_odbc_sparul_delete_invalid_triples');
		if($this->_odbc_sparul_execute($sparul)){
			$this->counterDelete+=1;
			};
		$needed = Timer::stopAsString('LiveUpdateDestination::_hash_odbc_sparul_delete_invalid_triples');
		$this->log(DEBUG,'deleted hash_invalid_triples, needed '.$needed );
		
		//TESTS>>>>>>>>>>>>
		if(Options::getOption('debug_run_tests')){
				$countafter = $this->_testwherepart($testwhere , false);
				
				$this->log(INFO,'TEST _odbc_sparul_delete_hash_invalid_triples, before: '.$countbefore. ' after: '.$countafter.' triples');
				
				if($countafter >= $countbefore && $countbefore >0){
					$this->log(WARN,'TEST FAILED, BEFORE SHOULD BE BIGGER THAN AFTER');
				}else{
					$this->log(INFO, 'SUCCESS');	
				}
			}
		//TESTS<<<<<<<<<<<<
		
	}
	private function _hash_odbc_sparul_delete_invalid_annotations( $extractorsToDelete, $filtervar, $filter ){
		
			 //***********************
			 //DELETE ALL INVALID ANNOTATIONS
			 //**********************
			 //delete all triples with the current subject 
			 //according to the filters
			$sparul= 
'DELETE  FROM <' . $this->annotationGraphURI . '> 
	{ ?axiom ?axp  ?axo .  }  
FROM <' . $this->annotationGraphURI . '> 
';
			$where = 'WHERE { 
	?axiom <'.DBM_ORIGIN.'>  '.$filtervar.' .  
	?axiom <'.OWL_SUBJECT.'> '.$this->subjectSPARULpattern.' .
	?axiom ?axp  ?axo .
	'.$filter.' .
}';	

		$sparul .= $where;
		//TESTS>>>>>>>>>>>>
		if(Options::getOption('debug_run_tests')){
				$countbefore = $this->_testwherepart($where, $this->annotationGraphURI);
			}
		//TESTS<<<<<<<<<<<<
		Timer::start('LiveUpdateDestination::_hash_odbc_sparul_delete_invalid_annotations');
		if($this->_odbc_sparul_execute($sparul)){
			$this->counterDelete+=1;
			};
		$needed = Timer::stopAsString('LiveUpdateDestination::_hash_odbc_sparul_delete_invalid_annotations');
		$this->log(DEBUG,'deleted hash_invalid_annotation triples, needed '.$needed );
		
		//TESTS>>>>>>>>>>>>
		if(Options::getOption('debug_run_tests')){
				$countafter = $this->_testwherepart($where , $this->annotationGraphURI);
				$this->log(INFO,'TEST _odbc_sparul_delete_hash_invalid_annotations, before: '.$countbefore. ' after: '.$countafter.' triples');				
				if($countafter >= $countbefore  && $countbefore >0){
					$this->log(WARN,'TEST FAILED, BEFORE SHOULD BE BIGGER THAN AFTER');
				}else{
					$this->log(INFO, 'SUCCESS');	
				}
				
			}
		//TESTS<<<<<<<<<<<<
		
	}
	
	private function _odbc_clean_sparul_delete_subresources($log=''){
		$subject = $this->uri->getURI();
		$sparul= 
'DELETE  FROM <' . $this->graphURI . '> 
	{ ?subresource ?p  ?o .  }  
FROM <' . $this->graphURI. '> 
';
$where = 'WHERE { 
'.$this->subjectSPARULpattern.'	?somep ?subresource . 
?subresource ?p  ?o .
FILTER (?subresource LIKE <'.$subject.'/%>)
}';	
		$sparul .= $where ;
		
		//TESTS>>>>>>>>>>>>
		if(Options::getOption('debug_run_tests')){
				$countbefore = $this->_testwherepart($where, false);
			}
		Timer::start('LiveUpdateDestination::_odbc_clean_sparul_delete_subresources');
		if($this->_odbc_sparul_execute($sparul)){
			$this->counterDelete+=1;
			};
		$needed = Timer::stopAsString('LiveUpdateDestination::_odbc_clean_sparul_delete_subresources');
		$this->log(DEBUG,'deleted subresources, needed '.$needed );
		//TESTS>>>>>>>>>>>>
		if(Options::getOption('debug_run_tests')){
				$countafter = $this->_testwherepart($where, false);
				$this->log(INFO,'TEST delete_subResources, before: '.$countbefore. ' after: '.$countafter.' triples');
				if($countafter > 0 && $countbefore > 0){
					$this->log(WARN,'TEST FAILED, AFTER SHOULD BE 0');
				}  else{
					$this->log(INFO, 'SUCCESS');	
				}
			}
		//TESTS<<<<<<<<<<<<
	}
	
	private function _odbc_sparul_delete_subresources($log=''){
			if(false == $this->generateOWLAxiomAnnotations){
				return;
				}
			 //***********************
			//DELETE ANOMALIES I.E. source Page
			//***********************
			//go to infobox extractor and 
			//add an annotation to all subject/rating objects to which subject they belong
			//and then delete them also	
$sparul= 
'DELETE  FROM <' . $this->graphURI . '> 
	{ ?s ?p  ?o .  }  
FROM <' . $this->annotationGraphURI . '> 
';
$where = 'WHERE { 
		?axiom <'.DBM_ONDELETECASCADE.'> '.$this->subjectSPARULpattern.' .  
		?axiom <'.OWL_SUBJECT.'> ?s .
		?axiom <'.OWL_PREDICATE.'> ?p .
		?axiom <'.OWL_OBJECT.'> ?o .
}';	
$testwhere = 'WHERE { 
	GRAPH <'.$this->graphURI.'> {
		?s ?p ?o
		}
	GRAPH <'.$this->annotationGraphURI.'> {
		?axiom <'.DBM_ONDELETECASCADE.'> '.$this->subjectSPARULpattern.' .  
		?axiom <'.OWL_SUBJECT.'> ?s .
		?axiom <'.OWL_PREDICATE.'> ?p .
		?axiom <'.OWL_OBJECT.'> ?o .
		}
}';	
		$sparul .= $where;
		//TESTS>>>>>>>>>>>>
		if(Options::getOption('debug_run_tests')){
				$countbefore = $this->_testwherepart($testwhere, false);
			}
		//TESTS<<<<<<<<<<<<
		Timer::start('LiveUpdateDestination::'.$log.'_odbc_sparul_delete_subResources');
		if($this->_odbc_sparul_execute($sparul)){
			$this->counterDelete+=1;
			};
		$needed = Timer::stopAsString('LiveUpdateDestination::'.$log.'_odbc_sparul_delete_subResources');
		$this->log(DEBUG,'deleted subresources, needed '.$needed );
		
		//TESTS>>>>>>>>>>>>
		if(Options::getOption('debug_run_tests')){
				$countafter = $this->_testwherepart($testwhere, false);
				$this->log(INFO,'TEST delete_subResources, before: '.$countbefore. ' after: '.$countafter.' triples');
				if($countafter > 0 && $countbefore > 0){
					$this->log(WARN,'TEST FAILED, AFTER SHOULD BE 0');
				}  else{
					$this->log(INFO, 'SUCCESS');	
				}
			}
		//TESTS<<<<<<<<<<<<
	}
	private function _odbc_sparul_delete_annotations_of_subresources($log='' ){
			if(false == $this->generateOWLAxiomAnnotations){
				return;
				}
			 //***********************
			//DELETE ANOMALIES I.E. source Page
			//***********************
			//go to infobox extractor and 
			//add an annotation to all subject/rating objects to which subject they belong
			//and then delete them also	

$sparul = 
'DELETE  FROM <' . $this->annotationGraphURI . '> 
	{ ?axiom ?axp  ?axo .  }  
FROM <' . $this->annotationGraphURI . '>
';
$where ='WHERE { 
	?axiom <'.DBM_ONDELETECASCADE.'> '.$this->subjectSPARULpattern.' .  
	?axiom ?axp  ?axo .
}';			

		$sparul .= $where;
		//TESTS>>>>>>>>>>>>
		if(Options::getOption('debug_run_tests')){
				$countbefore = $this->_testwherepart($where, $this->annotationGraphURI);
			}
		//TESTS<<<<<<<<<<<<
		Timer::start('LiveUpdateDestination::'.$log.'_odbc_sparul_delete_Annotations_of_subResources');
		if($this->_odbc_sparul_execute($sparul)){
			$this->counterDelete+=1;
			};
		$needed = Timer::stopAsString('LiveUpdateDestination::'.$log.'_odbc_sparul_delete_Annotations_of_subResources');
		$this->log(DEBUG,'deleted annotations of subresources, needed '. $needed );
		
		//TESTS>>>>>>>>>>>>
		if(Options::getOption('debug_run_tests')){
				$countafter = $this->_testwherepart($where, $this->annotationGraphURI);
				$this->log(INFO,'TEST delete_Annotations_of_subResources, before: '.$countbefore. ' after: '.$countafter.' triples');
				if($countafter > 0 && $countbefore > 0){
					$this->log(WARN,'TEST FAILED, AFTER SHOULD BE 0');
				}  else{
					$this->log(INFO, 'SUCCESS');	
				}
			}
		//TESTS<<<<<<<<<<<<
	}
	
	
	private function _odbc_sparul_delete_annotations( ){
			if(false == $this->generateOWLAxiomAnnotations){
				return;
				}
		
			 //****************************
			 //DELETE ANNOTATIONS
			 //****************************
			 //delete the corresponding annotations

 $sparul = 
'DELETE  FROM <' . $this->annotationGraphURI . '> 
	{ ?axiom ?axp  ?axo .  }  
FROM <' . $this->annotationGraphURI . '>
';
$where ='WHERE { 
	?axiom <'.OWL_SUBJECT.'> '.$this->subjectSPARULpattern.' .  
	?axiom ?axp  ?axo .
}';

		$sparul .= $where;
		//TESTS>>>>>>>>>>>>
		if(Options::getOption('debug_run_tests')){
				$countbefore = $this->_testwherepart($where, $this->annotationGraphURI);
			}
		Timer::start('LiveUpdateDestination::_odbc_sparul_delete_annotations');
		if($this->_odbc_sparul_execute($sparul)){
			$this->counterDelete+=1;
			};
		$needed = Timer::stopAsString('LiveUpdateDestination::_odbc_sparul_delete_annotations');
		$this->log(DEBUG,'deleted annotations, needed '.$needed );
		//TESTS>>>>>>>>>>>>
		if(Options::getOption('debug_run_tests')){
				$countafter = $this->_testwherepart($where, $this->annotationGraphURI);
				$this->log(INFO,'TEST delete_annotations, before: '.$countbefore. ' after: '.$countafter.' triples');
				if($countafter > 0 && $countbefore > 0){
					$this->log(WARN,'TEST FAILED, AFTER SHOULD BE 0');
				}  else{
				$this->log(INFO, 'SUCCESS');	
				}
		
		}
		//TESTS<<<<<<<<<<<
	}
	
	private function _odbc_sparul_delete_language($graphURI, $languageProperties ){
			 //***********************
			 //LANGUAGE
			 //***********************
			 //delete all triples with the current subject 
			 //where the lang properties with string object 
			 //from other language version are given, which should stay
		
            $x = 0;
			Timer::start('LiveUpdateDestination::_odbc_sparul_delete_english_total');
			foreach($languageProperties as $one){
				$u = new URI($one, false);
				$sparul = 
'DELETE FROM  <' . $graphURI . '> 
{ ' .$this->subjectSPARULpattern.' '.$u->toSPARULPattern() . ' ?o } 
FROM  <' . $graphURI . '>  
WHERE { 
	' .$this->subjectSPARULpattern.' '.$u->toSPARULPattern() . ' ?o .
	FILTER ( lang(?o) = \'en\').
}';
				
				Timer::start('LiveUpdateDestination::_odbc_sparul_delete_english'.$x);
				if($this->_odbc_sparul_execute($sparul)){
					$this->counterDelete+=1;
				}
				Timer::stop('LiveUpdateDestination::_odbc_sparul_delete_english'.$x);
				$x++;
			}//end foreach
		$needed = Timer::stopAsString('LiveUpdateDestination::_odbc_sparul_delete_english_total');
		$this->log(DEBUG,'deleted language'.$needed );    
		
		//TESTS>>>>>>>>>>>>
		if(Options::getOption('debug_run_tests')){
				$this->log(INFO,'no test for language currently' );    
		}  
				
		
		//TESTS<<<<<<<<<<<
	}
	
	private function _odbc_sparul_delete_language_oneQuery($graphURI, $languageProperties ){
			 //***********************
			 //LANGUAGE
			 //***********************
			 //delete all triples with the current subject 
			 //where the lang properties with string object 
			 //from other language version are given, which should stay
		
			Timer::start('LiveUpdateDestination::_odbc_sparul_delete_english_total');
			$union = array();
			foreach($languageProperties as $one){
				$u = new URI($one, false);
				$union[] = '?p = '.$u->toSPARULPattern();
			}
			$filter = TripleDiff::assembleTerms($union, '||');
			//$filter ='';
				$sparul = 
'DELETE FROM  <' . $graphURI . '> 
{ ' .$this->subjectSPARULpattern.' ?p  ?o }  
FROM  <' . $graphURI . '> 
WHERE { 
	' .$this->subjectSPARULpattern.'  ?p  ?o .
	FILTER ( '.$filter.' && (lang(?o) = \'en\')).
}';
				
			if($this->_odbc_sparul_execute($sparul)){
				$this->counterDelete+=1;
			}
		Timer::stop('LiveUpdateDestination::_odbc_sparul_delete_english_total');
		$this->log(DEBUG,'deleted language' );    
		
		//TESTS>>>>>>>>>>>>
		if(Options::getOption('debug_run_tests')){
				$this->log(INFO,'no test for language currently' );    
		}  
		//TESTS<<<<<<<<<<<
		
	}
		
/*
 
 * name: _odbc_sparul_insert_triples_and_annotations
 * @param
 * @return
 */
	public function _odbc_sparul_insert_triples_and_annotations($graphURI, $annotationGraphURI, $generateOWLAxiomAnnotations){
			if(!Options::getOption('debug_turn_off_insert')){
				return;
				}
			//**********************
			//GENERATE NEW TRIPLES
			//**********************
			Timer::start('LiveUpdateDestination::_odbc_sparul_insert_triples_and_annotations::String_Creation');
			Timer::start('LiveUpdateDestination::_odbc_sparul_insert_triples_and_annotations');
			$insertSPARUL = array();
			$insertSPARUL['insert_triples'] = array();
			$insertSPARUL['insert_annotations'] = array();
			$globalAnnotationSPARULpattern = "";
			$globalTripleSPARULpattern = "";
			$this->log(DEBUG, 'number of triple inserts: '.count($this->tripleFromExtractor) );
			foreach ($this->tripleFromExtractor as $triple){
					$tripleSPARULpattern = $triple->toSPARULPattern();
					$insertSPARUL['insert_triples'][] = 'INSERT INTO GRAPH <' . $graphURI . '> { ' . $tripleSPARULpattern . ' }';
					$globalTripleSPARULpattern .= $tripleSPARULpattern."\n";
					/*
					 * Annotations
					 * */
					if($generateOWLAxiomAnnotations){
						$annotations = $triple->getOWLAxiomAnnotations($this->oaiId);
						Statistics::increaseCount( 'Total','createdAnnotations', count($annotations));
						if(count($annotations)>0){
							$annotationSPARULpattern = "";
							foreach($annotations as $ann){
								$currentSPARULpattern = $ann->toSPARULPattern();
								$annotationSPARULpattern .= $currentSPARULpattern;
								$globalAnnotationSPARULpattern .= $currentSPARULpattern."\n";
							}
							//annotations for one triple are aggregated to one query
							$insertSPARUL['insert_annotations'][] = 'INSERT INTO GRAPH <' . $annotationGraphURI . '> { ' . $annotationSPARULpattern . ' }';
						}
					}
			}
			$this->log(DEBUG, 'number of annotation inserts: '.count($insertSPARUL['insert_annotations']) );
			
			$insertSPARUL['globalTripleSPARULpattern'] = 'INSERT INTO GRAPH <' . $graphURI . '> { ' . $globalTripleSPARULpattern . ' }';
			$insertSPARUL['globalAnnotationSPARULpattern'] = 'INSERT INTO GRAPH <' . $annotationGraphURI . '> { ' . $globalAnnotationSPARULpattern . ' }';
			
			
			$this->log(DEBUG, 'length globalTriplePattern: '. strlen($insertSPARUL['globalTripleSPARULpattern']) );
			$this->log(DEBUG, 'length globalAnnotationPattern: '. strlen($insertSPARUL['globalAnnotationSPARULpattern']) );
			
			Timer::stop('LiveUpdateDestination::_odbc_sparul_insert_triples_and_annotations::String_Creation');
			
			Timer::start('LiveUpdateDestination::_odbc_sparul_insert_triples_and_annotations::InsertOperations');
			//if global batch is successfull
			Timer::start('LiveUpdateDestination::_odbc_sparul_insert_triples_and_annotations::insertGlobalTriplePattern');
			$globalSuccess = $this->_odbc_sparul_execute( $insertSPARUL['globalTripleSPARULpattern']) ;
			Timer::stop('LiveUpdateDestination::_odbc_sparul_insert_triples_and_annotations::insertGlobalTriplePattern');

			//if global tripel batch is unsuccessfull
			if(false === $globalSuccess ) {
				$this->log(INFO, 'global insert of triples failed, inserting each triple ('.count($insertSPARUL['insert_triples']).')');
				Timer::start('LiveUpdateDestination::_odbc_sparul_insert_triples_and_annotations::insertSingleTriples');
				foreach($insertSPARUL['insert_triples'] as $query){
					if($this->_odbc_sparul_execute( $query )){
						$this->counterInserts+=1;
						}
				}
				Timer::stop('LiveUpdateDestination::_odbc_sparul_insert_triples_and_annotations::insertSingleTriples');
				
			}else if( $generateOWLAxiomAnnotations){
				$this->counterInserts+=1;
				Timer::start('LiveUpdateDestination::_odbc_sparul_insert_triples_and_annotations::insertGlobalAnnotationPattern');
				$globalSuccessAnnotations = $this->_odbc_sparul_execute($insertSPARUL['globalAnnotationSPARULpattern']) ;
				Timer::stop('LiveUpdateDestination::_odbc_sparul_insert_triples_and_annotations::insertGlobalAnnotationPattern');
				
				}
				
			if($generateOWLAxiomAnnotations && false===$globalSuccessAnnotations ){
				$this->log(INFO, 'global insert of annotations failed, inserting each triple ('.count($insertSPARUL['insert_annotations']).')');
				Timer::start('LiveUpdateDestination::_odbc_sparul_insert_triples_and_annotations::insertSingleAnnotations');
				foreach ( $insertSPARUL['insert_annotations'] as $query){
					if($this->_odbc_sparul_execute( $query )){
						$this->counterInserts+=1;
						}
				}
				Timer::stop('LiveUpdateDestination::_odbc_sparul_insert_triples_and_annotations::insertSingleAnnotations');
			}else if($generateOWLAxiomAnnotations){
				$this->counterInserts+=1;
				}
			Timer::stop('LiveUpdateDestination::_odbc_sparul_insert_triples_and_annotations::InsertOperations');
			Timer::stop('LiveUpdateDestination::_odbc_sparul_insert_triples_and_annotations');
		}
	
/*
 * * will do nothing if Options::getOption('debug_turn_off_insert') is true
 * will not upload Annotations if generateOWLAxioms is set to false
 * name: _odbc_ttlp_insert_triples_and_annotations
 * @param array of triples
 * @return
 */
	public function _odbc_ttlp_insert_triples($triplesToAdd){
			if($this->debug_turn_off_insert){
				return;
				}
			//**********************
			//GENERATE NEW TRIPLES
			//**********************
			Timer::start('LiveUpdateDestination::_odbc_ttlp_insert_triples');
			Timer::start('LiveUpdateDestination::_odbc_ttlp_insert_triples::string_creation');
			$globalTripleNTriplePattern = "";
			$tripleCounter = count($triplesToAdd);
			$this->log(DEBUG, 'number of triple inserts: '.$tripleCounter );
			foreach ($triplesToAdd as $triple){
					$globalTripleNTriplePattern .= $triple->toNTriples();
			}
			$this->log(DEBUG, 'length globalTriplePattern: '. strlen($globalTripleNTriplePattern) );
			
			Timer::stop('LiveUpdateDestination::_odbc_ttlp_insert_triples::string_creation');
			//TESTS>>>>>>>>>>>>
			$where = 'WHERE { '.$this->subjectSPARULpattern.' ?p ?o } ';
			if(Options::getOption('debug_run_tests')){
				$countbefore = $this->_testwherepart($where , $this->graphURI);
			}
			//TESTS<<<<<<<<<<<<
			Timer::start('LiveUpdateDestination::_odbc_ttlp_insert_triples::insert_operation');
			$globalSuccess = $this->_odbc_ttlp_execute( $globalTripleNTriplePattern, $this->graphURI) ;
			if($globalSuccess) {
				$this->counterInserts+=1;	
			}
			
			Timer::stop('LiveUpdateDestination::_odbc_ttlp_insert_triples::insert_operation');
			Timer::stop('LiveUpdateDestination::_odbc_ttlp_insert_triples');
			
			//TESTS>>>>>>>>>>>>
			if(Options::getOption('debug_run_tests')){
					$countafter = $this->_testwherepart($where , $this->graphURI);
					
					$this->log(INFO,'TEST _odbc_ttlp_insert_triples, before: '.$countbefore. ' after: '.$countafter.' triples');
					if($countafter - $countbefore < 0 && $tripleCounter >0){
						$this->log(WARN,'TEST FAILED, INSERT TRIPLES AFTER SHOULD BE BIGGER THAN BEFORE');
					}else{
						$this->log(INFO, 'SUCCESS');	
					}
				}
			//TESTS<<<<<<<<<<<<
			
		}
		
	public function _odbc_ttlp_insert_annotations($triplesToAdd){
			if($this->debug_turn_off_insert){
				return;
				}
			if(false == $this->generateOWLAxiomAnnotations ){
				return;
				}
			//**********************
			//GENERATE NEW TRIPLES
			//**********************
			Timer::start('LiveUpdateDestination::_odbc_ttlp_insert_annotations');
			Timer::start('LiveUpdateDestination::_odbc_ttlp_insert_annotations::string_creation');
			
			$globalAnnotationNTriplePattern = "";
			$annotationCounter = 0;
			foreach ($triplesToAdd as $triple){
				$annotations = $triple->getOWLAxiomAnnotationsAsNTriple($this->oaiId);
				$globalAnnotationNTriplePattern .= implode('',$annotations );
				Statistics::increaseCount( 'Total','createdAnnotations', count($annotations));
				$annotationCounter +=count($annotations);
			}
			$this->log(DEBUG, 'number of annotation inserts: '.$annotationCounter);
			$this->log(DEBUG, 'length globalAnnotationPattern: '. strlen($globalAnnotationNTriplePattern ) );
			
			Timer::stop('LiveUpdateDestination::_odbc_ttlp_insert_annotations::string_creation');
			//TESTS>>>>>>>>>>>>
			$where = 'WHERE { ?s <'.OWL_SUBJECT.'> '.$this->subjectSPARULpattern.' . ?s ?p ?o} ';
			if(Options::getOption('debug_run_tests')){
				$countbefore = $this->_testwherepart($where , $this->annotationGraphURI);
			}
			//TESTS<<<<<<<<<<<<
			Timer::start('LiveUpdateDestination::_odbc_ttlp_insert_annotations::insert_operation');
			$globalSuccess = $this->_odbc_ttlp_execute( $globalAnnotationNTriplePattern, $this->annotationGraphURI) ;
			Timer::stop('LiveUpdateDestination::_odbc_ttlp_insert_annotations::insert_operation');
			if($globalSuccess) {
				$this->counterInserts+=1;	
			}
/*
			else{
				foreach ($triplesToAdd as $triple){
						$annotations = $triple->getOWLAxiomAnnotationsAsNTriple($this->oaiId);
						$globalAnnotationNTriplePattern .= implode('',$annotations );
						Statistics::increaseCount( 'Total','createdAnnotations', count($annotations));
						$annotationCounter +=count($annotations);
					}
			}
*/
			
			Timer::stop('LiveUpdateDestination::_odbc_ttlp_insert_annotations');
			
			//TESTS>>>>>>>>>>>>
			if(Options::getOption('debug_run_tests')){
					$countafter = $this->_testwherepart($where , $this->annotationGraphURI);
					$this->log(INFO,'TEST _odbc_ttlp_insert_annotations, before: '.$countbefore. ' after: '.$countafter.' triples');
					if($countafter - $countbefore < 0 && $annotationCounter >0){
						$this->log(WARN,'TEST FAILED, INSERT ANNOTATIONS AFTER SHOULD BE BIGGER THAN BEFORE');
					}else{
						$this->log(INFO, 'SUCCESS');	
					}
				}
			//TESTS<<<<<<<<<<<<
			
		}
		
			
/*
 * 
 * name: unbekannt
 * @param
 * @return
 */
	private function _http_retrieve_odbc_sparul_delete_language($languageProperties, $graphURI){
			Timer::start('LiveUpdateDestination::_http_retrieve_odbc_sparul_delete_language');
			$store = new SPARQLToRDFTriple($this->uri, $this->language);
			
			
			$languageTriples = $store->getRDFTripleForLangProperties($languageProperties);
			$this->log(DEBUG, 'retrieved triples to be deleted, language : '.count($languageTriples));
			
			$sparul = "";
			foreach ( $languageTriples as $triple){
				$this->counterDelete+=1;
				$sparul .= ' '.$this->subjectSPARULpattern;
				$sparul .= ' '.$triple->getPredicate()->toSPARULPattern();
				$sparul .= ' '.$triple->getObject()->toSPARULPattern();
				$sparul .= " .\n";
				}
			$sparul = 'DELETE FROM <'.$graphURI.'> WHERE { '.$sparul.' }';
			$this->_odbc_sparul_execute($sparul);
			Timer::stop('LiveUpdateDestination::_http_retrieve_odbc_sparul_delete_language');
		}
	
    
	private function writeSPARULtoFiles($deleteSPARUL, $insertSPARUL){
    		$out = "";
			$out.=implode(";\n", $deleteSPARUL );
			$out.="\n";
			$out.=implode(";\n", $insertSPARUL );
			
			$dirs = Options::getOption('outputdirs');
			foreach($dirs as $dir){
				@mkdir($dir);
				$uri = $this->uri->getURI();
				$uri = substr($uri,strlen(DB_RESOURCE_NS));
				$uri = str_replace("/","%2F", $uri);
				$uri = urlencode(DB_RESOURCE_NS).$uri;
				$uri = substr($uri,0, 233);
				$file = $dir."/".$uri;
				Logger::toFile($file ,$out,true);
				//Logger::toFile($file ,"\n**DEBUG***********\n".$logString,false);
			}
		}
	
    private function _odbc_sparul_execute($query)   {
		$odbc_result = false;
		if(Options::getOption('dryRun')){
			$this->log(INFO,$query);
			$odbc_result = true;
		}else{
			 // escape characters that delimit the query within the query
        	//obsolete $query = addcslashes($query, '\'\\');
        	// build Virtuoso/PL query
        	//$virtuosoPl = 'CALL DB.DBA.SPARQL_EVAL(\'' . $query . '\', \'' . $graphURI . '\', 0)';
        	//$virtuosoPl = 'CALL DB.DBA.SPARQL_EVAL(\'' . $query . '\', NULL,  0)';
        	$virtuosoPl = 'sparql ' . $query . '';
			$odbc_result = $this->odbc->exec( $virtuosoPl,'LiveUpdateDestination');
			if($odbc_result != false){
				$tmparray= odbc_fetch_array ($odbc_result);
				if(count($tmparray)==0){
					$this->log(INFO, "odbc_exec returned empty array");
				}else{
					foreach ($tmparray as $key => $value){
						$this->log(INFO, "odbc_exec returned: ".$tmparray[$key]);
						}
				}
				
				
			}
			$this->counterTotalODBCOperations+=1;
			$this->log(TRACE, $virtuosoPl);
		}
		return $odbc_result;
    }
	
    private function _odbc_ttlp_execute($ntriples, $graphURI)   {
		$odbc_result = false;
		if(Options::getOption('dryRun')){
			$virtuosoPl = "DB.DBA.TTLP_MT (\n'$ntriples', '$graphURI', '$graphURI', 255)";
			$this->log(INFO,$virtuosoPl);
			$odbc_result = true;
		}else{
			$virtuosoPl = "DB.DBA.TTLP_MT (?, '$graphURI', '$graphURI', 255)";
			$stmt = $this->odbc->prepare($virtuosoPl , 'LiveUpdateDestination');
			$odbc_result = odbc_execute  ( $stmt , array($ntriples) );
			if($odbc_result == false){
				$this->log(ERROR, 'ttlp insert failes');
				$this->log(ERROR, $virtuosoPl);
				$this->log(ERROR, substr(odbc_errormsg(),0,100));
				$this->log(ERROR, substr($ntriples,0,100));

			}else{
				$this->log(INFO, 'insert returned a true via odbc_execute');
			}
			
			//old line, now we use odbc_prepare
			//$result = $this->odbc->exec( $virtuosoPl,'LiveUpdateDestination');
			$this->counterTotalODBCOperations+=1;
			$this->log(TRACE, $virtuosoPl);
		}
		return $odbc_result;
		
    }
    private function _odbc_sparul_insert_one_triple($sparulpattern, $graphURI)   {
		$odbc_result = false;
		$sparul = "Insert Into <$graphURI> { $sparulpattern }";
		if(Options::getOption('dryRun')){
			$this->log(INFO,$sparul);
			$odbc_result = true;
		}else{
			$odbc_result = $this->odbc->exec( $sparul, 'LiveUpdateDestination');
			if($odbc_result == false){
				$this->log(ERROR, 'insert failed patter:');
				$this->log(ERROR, $sparulpattern);
			}else{
				$this->log(INFO, 'insert returned a true via odbc_execute');
			}
			$this->counterTotalODBCOperations+=1;
			$this->log(TRACE, $sparul);
		}
		return $odbc_result;
		
    }
   
   	private  function log($lvl, $message){
			
				Logger::logComponent('destination', get_class($this), $lvl , $message);
		}
    
}

