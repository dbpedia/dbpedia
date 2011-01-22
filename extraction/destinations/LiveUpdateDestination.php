<?php

define("LUD_STORE", 'lud_store');
define("LUD_SPARQLFILTER", 'lud_sparqlfilter');
define("LUD_SPARULFORLANGUAGES", 'lud_sparulforlanguages');
define("TEST_DELAY", 0);


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
		
	}
    
    
    public function countLiveAbstracts(){
			$testquery = "SELECT count(*) as ?count FROM <".$this->graphURI."> 
            {".$this->subjectSPARULpattern." <".DBCOMM_ABSTRACT."> ?o }";
			//echo $testquery;
			$se = SPARQLEndpoint::getDefaultEndpoint();
			return $se->executeCount($testquery, get_class($this), $this->graphURI);
        }
        
    
    public function finish() { 
        
        $abstractCount = $this->countLiveAbstracts();
        
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
            Timer::start('LiveUpdateDestination::_primaryStrategy');
            $this->_primaryStrategy();
            Timer::stop('LiveUpdateDestination::_primaryStrategy');
			//does nothing if not active
			$this->hash->insertIntoDB();
		}
        
        $abstractCountAfter = $this->countLiveAbstracts();
        $level = "info";
        $success = "";
        
        if(($abstractCountAfter-$abstractCount)>0 && $abstractCountAfter!=1){
            $level = "error";
            $success = "FAILURE";
        }
        $this->log($level, "$success abstracts before/after: $abstractCount / $abstractCountAfter");
        
        
    }
	
	private function _hashedUpdate($addTriples, $deleteTriples){
        /*
         * DELETION
         * */
        
        $this->odbc = ODBC::getDefaultConnection();
        if(!empty($deleteTriples)){
            $this->_alt_delete_all_triples($deleteTriples);
        }
		
		/*
		 * STRATEGIES FOR INSERTION
		 * will do nothing if Options::getOption('debug_turn_off_insert') is true
		 * */
		if(!empty($addTriples)) {	
			$this->_odbc_ttlp_insert_triples($addTriples);
		}
		
	}
	
	
	private function _primaryStrategy(){
		/*
		 * PREPARATION
		 * 
		 * */
		
        if(!TheContainer::wasSet(LUD_SPARQLFILTER)){
			$store = null;
			$tripleDiff = new TripleDiff($this->uri,$this->language ,$this->producesFilterList, $store);
			TheContainer::set(LUD_SPARQLFILTER , $tripleDiff->createFilter($this->producesFilterList));
        }
        
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
		Timer::stop('LiveUpdateDestination::_odbc_sparul_delete_total');
		
		/*
		 * STRATEGIES FOR INSERTION
		 * will do nothing if Options::getOption('debug_turn_off_insert') is true
		 * */
		$this->_odbc_ttlp_insert_triples($this->tripleFromExtractor);
		$this->log(INFO, 'no of queries, insert: '.$this->counterInserts.' delete: '.$this->counterDelete.' odbc_total: '.$this->counterTotalODBCOperations);
/*
		if(Options::getOption('writeSPARULtoFiles')){
			$this->writeSPARULtoFiles($deleteSPARUL, $insertSPARUL);
		}
*/
		
		}
	
    
    
	private function _alt_delete_all_triples( $fromStore){
			Timer::start('LiveUpdateDestination::_alt_delete_all_triples');
			$sparul = "";
			$pattern = "";
            $directCount = 0;
			foreach ($fromStore as $hash=>$triple){
					$pattern .= $triple['s'].' '.$triple['p'].' '.$triple['o']." . \n";
                    if($triple['s'] == $this->subjectSPARULpattern){
				       $directCount++;
			        }
                    
				}
			$sparul = "Delete From <{$this->graphURI}> { \n  $pattern }";
			
			//TESTS>>>>>>>>>>>>
			if(Options::getOption('debug_run_tests')){
				$countbefore = $this->_testsubject($this->uri->getURI());
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
			$this->log(DEBUG,'alt: deleted '.count($fromStore). ' triples directly ('.$directCount.')'.$needed  );
			//echo $sparul;			
			//TESTS>>>>>>>>>>>>
			if(Options::getOption('debug_run_tests')){
                sleep(TEST_DELAY);
                $this->log(INFO,"delaying: ".TEST_DELAY);
				$countafter =  $this->_testsubject($this->uri->getURI());
				$this->log(INFO,'TEST _alt_delete_all_triples, before: '.$countbefore. ' after: '.$countafter.' triples');
                $diff = $countbefore-$countafter;
				if( $diff !=  $directCount ){
					
                     $eachtriplelog ="";
                     foreach ($fromStore as $hash=>$triple){
                            $testpattern = 'where { '.$triple['s'].' '.$triple['p'].' '.$triple['o']." . } \n";
                            $testOnePattern = $this->_testwherepart($testpattern);
                            $eachtriplelog .= $testOnePattern." ".( ($testOnePattern > 0)?"NOT deleted: ":"SUCCESS deleted: ");
                            $eachtriplelog .=  $this->_testwhereQuery($testpattern);
                        }
                    $this->log(WARN,"TEST FAILED, AFTER SHOULD BE SMALLER, testing each triple:\n$eachtriplelog");
                    $this->log(WARN,"Count executed again, yields :". $this->_testsubject($this->uri->getURI()));

                    if(false==$result){
                        $this->log(WARN,"Used Fallback last query no advanced testing implemented yet ");
                    }else{
                        $this->log(WARN,"Delete query: \n$sparul");
                        }
                    $this->log(WARN,"Test query: \n".$this->_testsubjectQuery($this->uri->getURI()));                    
					
				}  else{
					$this->log(INFO, 'SUCCESS');	
				}
			}
			
		}
		
	private function _testwhereQuery($testwhere){
            $g = ' FROM <'.$this->graphURI.'> ';
			$testquery = "SELECT count(*) as ?count ".$g."".$testwhere;
            return $testquery;
        }
		
	private function _testwherepart($testwhere){
            $testquery = $this->_testwhereQuery($testwhere);

			$se = SPARQLEndpoint::getDefaultEndpoint();
			return $se->executeCount($testquery, get_class($this), $this->graphURI);
	}
    
	private function _testprintSPARQLResult($testwhere){
            $g = ' FROM <'.$this->graphURI.'> ';
			$testquery = 'SELECT * '.$g.$testwhere;

			$se = SPARQLEndpoint::getDefaultEndpoint();
			$json =  $se-> executeQuery($testquery, get_class($this), $this->graphURI);
            $arr = json_decode($json, true);
            $vars = @$arr['head']['vars'];
            $bindings = @$arr['results']['bindings'];
            $logstr = "";
            
            if(!is_array($bindings)){
                return $logstr;
                }
            
            foreach($bindings as $b){
                $firstElement = true;
                foreach($vars as $var){
                    if(false){
                        $logstr .=  @$b[$var]['value']."  ";
                    }else{
                       
                        if($firstElement){
                            $logstr .= $this->subjectSPARULpattern." ";
                            $firstElement=false;
                        }
                        if(@$b[$var]['type']=='uri'){
                            $logstr .= "<".@$b[$var]['value']."> ";
                        }else if(@$b[$var]['type']=='literal'){
                            $logstr .= '"'.@$b[$var]['value'].'"';
                            $logstr .= (isset($b[$var]['xml:lang']))?"@".$b[$var]['xml:lang']:" ";
                        }else if(@$b[$var]['type']=='typed-literal'){
                            $logstr .= '"'.@$b[$var]['value'].'"^^<'.@$b[$var]['datatype'].'> ';
                        }
                    }
                }
                
                $logstr .="  .\n";
            }
            return $logstr;
	}
	
    private function _testsubjectQuery($subject){
            $g = ' FROM <'.$this->graphURI.'> ';
            $testquery = 'SELECT count(*) as ?count '.$g.' { <'.$subject.'> ?p ?o }';
            return $testquery;
        }
    
	private function _testsubject($subject ){
			$testquery = $this->_testsubjectQuery($subject);
			$se = SPARQLEndpoint::getDefaultEndpoint();
			return $se->executeCount($testquery, get_class($this), $this->graphURI);
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
'DELETE  FROM <' . $this->graphURI . '> 
	{ '.$this->subjectSPARULpattern.' ?p ?o } 
FROM <' . $this->graphURI . '>  
';	
      $where = ' WHERE { 
	'.$subjectpattern.' ?p ?o .
	'.$tmpFilter.'
}'; 
		$sparul .= $where;
		//TESTS>>>>>>>>>>>>
		if(Options::getOption('debug_run_tests')){
				$countbefore = $this->_testwherepart($where);
               $triplesBefore = $this->_testprintSPARQLResult($where);
			}

		Timer::start('LiveUpdateDestination::_odbc_sparul_delete_subject_not_static');
		
		if($this->_odbc_sparul_execute($sparul)){
			$this->counterDelete+=1;
			};
		$needed = Timer::stopAsString('LiveUpdateDestination::_odbc_sparul_delete_subject_not_static');
		$this->log(DEBUG,'deleted subject_not_static, needed '.$needed);
		
		//TESTS>>>>>>>>>>>>
		if(Options::getOption('debug_run_tests')){
                 sleep(TEST_DELAY);
                 $this->log(INFO,"delaying: ".TEST_DELAY);
				$countafter = $this->_testwherepart($where);
				$this->log(INFO,'TEST delete_subject_not_static, before: '.$countbefore. ' after: '.$countafter.' triples');
				if($countafter > 0 && $countbefore > 0){
                    $this->log('warn',"<TRIPLES_BEFORE>\n". $triplesBefore."</TRIPLES_BEFORE>");
                    $this->log('warn',"<TRIPLES_AFTER>\n". $this->_testprintSPARQLResult($where)."</TRIPLES_AFTER>");
					$this->log(WARN,'TEST FAILED, AFTER SHOULD BE 0');
                    $this->log(WARN, "Testquery: ".$this->_testwhereQuery($where));
                    $this->log(WARN, "Deletequery: ". $sparul);
                    $this->log('warn',"Remaining triples, diplayed below: \n". $this->_testprintSPARQLResult($where));
                    $this->log('warn',"Count executed again, yields: ". $this->_testwherepart($where));
                    
				}  else{
					$this->log(INFO, 'SUCCESS');	
				}
			}
		
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
				$countbefore = $this->_testwherepart($where);
			}
		Timer::start('LiveUpdateDestination::_odbc_clean_sparul_delete_subresources');
		if($this->_odbc_sparul_execute($sparul)){
			$this->counterDelete+=1;
			};
		$needed = Timer::stopAsString('LiveUpdateDestination::_odbc_clean_sparul_delete_subresources');
		$this->log(DEBUG,'deleted subresources, needed '.$needed );
		//TESTS>>>>>>>>>>>>
		if(Options::getOption('debug_run_tests')){
            sleep(TEST_DELAY);
            $this->log(INFO,"delaying: ".TEST_DELAY);
				$countafter = $this->_testwherepart($where);
				$this->log(INFO,'TEST delete_subResources, before: '.$countbefore. ' after: '.$countafter.' triples');
				if($countafter > 0 && $countbefore > 0){
					$this->log(WARN,'TEST FAILED, AFTER SHOULD BE 0');
                    $this->log(WARN, "Test: ".$this->_testwhereQuery($where));
                    $this->log(WARN, "Delete: ". $sparul);
				}  else{
					$this->log(INFO, 'SUCCESS');	
				}
			}
		//TESTS<<<<<<<<<<<<
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
				$countbefore = $this->_testwherepart($where );
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
					$countafter = $this->_testwherepart($where );
					
					$this->log(INFO,'TEST _odbc_ttlp_insert_triples, before: '.$countbefore. ' after: '.$countafter.' triples');
					if($countafter - $countbefore < 0 && $tripleCounter >0){
						$this->log(WARN,'TEST FAILED, INSERT TRIPLES AFTER SHOULD BE BIGGER THAN BEFORE');
					}else{
						$this->log(INFO, 'SUCCESS');	
					}
				}
			//TESTS<<<<<<<<<<<<
			
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
			//$this->log(INFO, "QUERY IS:\n$query\n\n\n");

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
   
   
   	private  function log($lvl, $message){
			
				Logger::logComponent('destination', get_class($this), $lvl , $message);
		}
    
}

