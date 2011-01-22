<?php

class LiveUpdateDestination implements Destination {
	
	private $uri;
	private $tiplesFromExtractors = array();
	private $language;
	
	private $storespecific = VIRTUOSO;
	
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
	private $predicateFilterList = array();
	private $objectFilterList = array();
	
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
	private $predicateObjectFilterList = array();
	
	//collects all triples in accept
	public $tripleFromExtractor = array(); 
	
	function __construct($pageID, $language){
			$this->uri = RDFTriple::page($pageID);
			$this->language = $language;
			if(Options::isOptionSet('predicateFilter')){
				$p = Options::getOption('predicateFilter');
				foreach($p as $one){
					//echo $one."\n";
					$this->predicateFilterList[] = trim($one);
					}
			}
			if(Options::isOptionSet('objectFilter')){
				$o = Options::getOption('objectFilter');
				foreach($o as $one){
					//echo $one."\n";
					$this->objectFilterList[] = trim($one);
					}
			}
			if(Options::isOptionSet('predicateObjectFilter')){
				$po = Options::getOption('predicateObjectFilter');
				foreach($po as $one){
						//echo $one."\n";
					$pos = strpos($one," ");
					$currentp = trim(substr($one,0,$pos));
					$currento = trim(substr($one,$pos+1));
						//echo $currentp."\n";
						//echo $currento."\n";
					$this->predicateFilterList[] = $one;
				}
			}
		}
	
	
	/*
	 * 
	 * */	
	//this is set in ExtractionGroup
	public function addFilter($filter){
			if(!empty($filter['pfilter'])){
				foreach($filter['pfilter'] as $one){
					$this->predicateFilterList[] = $one;
					}
				}
			if(!empty($filter['ofilter'])){
				foreach($filter['ofilter'] as $one){
					$this->objectFilterList[] = $one;
					}
				
				}
			if(!empty($filter['pofilter'])){
				foreach($filter['pofilter'] as $one){
					$this->predicateObjectFilterList[] = $one;
					}
				
				}
		}
	
	public function addPredicateFilter($pfilter){
			$this->objectFilterList[] = $pfilter;
		}
		
    public function start() { 
    	
    	}
		
    public function accept($extractionResult) {
			
			$triples = $extractionResult->getTriples();
			foreach ($triples as $triple){
					$this->tripleFromExtractor[]=$triple;
				}
    }
    
    
    public function finish() { 
		$generateOWLAxiomAnnotations = Options::getOption('generateOWLAxiomAnnotations');
		
		if($generateOWLAxiomAnnotations){
			Timer::start('LiveUpdateDestination::diffItOWLAxioms');
			//$result = $this->diffItOWLAxioms();
			$result = $this->smarterDiffItOWLAxioms();
			Timer::stop('LiveUpdateDestination::diffItOWLAxioms');
			
		}else{
			Timer::start('LiveUpdateDestination::diffItSimple');
			$result = $this->diffItSimple();
			Timer::stop('LiveUpdateDestination::diffItSimple');
			}
		
		$graphURI  = Options::getOption('graphURI');
		$deleteSPARUL = $result['del'] ;	
		$insertSPARUL = $result['ins'] ;

		if(Options::getOption('writeSPARULtoFiles')){
			$this->writeSPARULtoFiles($deleteSPARUL, $insertSPARUL);
		}
		
		$odbc = ODBC::getDefaultConnection();
		
/*
		$iconfirm = Options::getOption('iconfirmthaticheckedthestringsbelow');
		$dryRun = false;
		if(($generateOWLAxiomAnnotations==true) && ($iconfirm==false )){
			
			$dryRun = true;
			$this->log(WARN, 'This will be a dry run, see dbpedia.ini iconfirmthaticheckedthestringsbelow');
			sleep (4);
			}
		
		$dryRun = ($dryRun || Options::getOption('dryRun'));
*/
		
		// LOADING into STORE
		
			Timer::start('LiveUpdateDestination::loadIntoVirtuoso');
			if($generateOWLAxiomAnnotations) {
				$this->loadIntoVirtuoso($odbc, $deleteSPARUL, $insertSPARUL, $graphURI);
			}else{
				$this->loadIntoVirtuosoSimple($odbc, $deleteSPARUL, $insertSPARUL, $graphURI);
				}
			Timer::stop('LiveUpdateDestination::loadIntoVirtuoso');
		//Logger::debug($this->tripleDiff->createSPARQLQuery());
    	//odbc_close_all();
    }
	public function diffItOWLAxioms(){
			//a store is not needed here 
			$store = null;
			$propLangFilter = Options::getOption('stringPredicateWithForeignlanguages');
			$graphURI  = Options::getOption('graphURI');
            $annotationGraphURI = Options::getOption('annotationGraphURI');
			
			foreach($propLangFilter as $one){
				$this->predicateFilterList[] = $one;
				}

			
			Timer::start('LiveUpdateDestination::diffItOWLAxioms::TripleDiff');
			$tripleDiff = new TripleDiff($this->uri,$this->language ,$this->predicateFilterList, $this->objectFilterList, $this->predicateObjectFilterList, $store);
    		$filterForNotAnnotatedTriples =  $tripleDiff->createFilter($this->predicateFilterList, $this->objectFilterList, $this->predicateObjectFilterList);
			Timer::stop('LiveUpdateDestination::diffItOWLAxioms::TripleDiff');
			
			Timer::start('LiveUpdateDestination::diffItOWLAxioms::generationOfSPARULqueries');
			//get a list of all active extractors
			Timer::start('LiveUpdateDestination::diffItOWLAxioms::extractorFilter');
			

			$extractedByPattern = RDFtriple::URI(DBM_ORIGIN)->toSPARULPattern($this->storespecific);
			$subjectpattern = $this->uri->toSPARULPattern($this->storespecific);
			$extractorFilter = "";
			$extTerms = array();
			foreach ($this->activeExtractors as $one){
			 	 $u = new URI($one);
				 $extPattern = $u->toSPARULPattern($this->storespecific);
				 $extTerms[] = ' ?extractor = '.$extPattern.' '; 
			}
			foreach ($this->purgeExtractors as $one){
			 	 $u = new URI($one);
				 $extPattern = $u->toSPARULPattern($this->storespecific);
				 $extTerms[] = ' ?extractor = '.$extPattern.' '; 
			}
			$extractorFilter = 'FILTER ( '.TripleDiff::assembleTerms($extTerms,'||').') . ';
			
			//delete triples from all active extractor	
			$deleteSPARUL = array();
			$deleteSPARUL['delete_from_all_active_extractors'] = 'DELETE  FROM <' . $graphURI . '> 
				 { '.$subjectpattern.' ?p ?o }  
			 WHERE { GRAPH <'.$annotationGraphURI.'> {
				 ?axiom <'.OWL_SUBJECT.'> '.$subjectpattern.' .
				 ?axiom <'.OWL_PREDICATE.'> ?p .  
				 ?axiom <'.OWL_OBJECT.'>  ?o . 
				 ?axiom '.$extractedByPattern.' ?extractor . 
				 '.$extractorFilter.' }
				
			 }';
			 //removed : 
			 // '.$subjectpattern.' ?p ?o 
			 //****************************
			 //delete the corresponding annotations
			 $deleteSPARUL['delete_corresponding_annotations'] = 'DELETE  FROM <' . $annotationGraphURI . '> 
				 { ?axiom ?axp  ?axo .  }  
			 WHERE { 
				 ?axiom <'.OWL_SUBJECT.'> '.$subjectpattern.' .  
				 ?axiom '.$extractedByPattern.' ?extractor . 
				  '.$extractorFilter.' 
				 ?axiom ?axp  ?axo . 
			 }';
			 //echo  $deleteSPARUL['delete_corresponding_annotations'];
/*
 * 			 taking out:
			 	 ?axiom <'.OWL_PREDICATE.'> ?p .  
				 ?axiom <'.OWL_OBJECT.'>  ?o . 
*/
			 Timer::stop('LiveUpdateDestination::diffItOWLAxioms::extractorFilter');
			 /*OPTIMIZATIONS
			 * ?axiom ?axp  ?axo . maybe could be combined with q1 above
			 * best to ask Joerg Unbehauen who knows SPARUL or OpenLink
			 * */
			 		 
			//***********************
			//TODO go to infobox extractor and 
			//add an annotation to all subject/rating objects to which subject they belong
			//and then delete them also	
			
/*			***********************
 * 			This became obsolete
			//delete all triples that do not have an annotation
			//this is consistent as they will be recreated with annotations
			//all triples to be kept should be annotated beforehand
			$deleteSPARUL[] = 'DELETE  FROM <' . $graphURI . '> 
				 { '.$subjectpattern.' ?p ?o }  
			 WHERE { 
				 '.$subjectpattern.' ?p ?o .
				 OPTIONAL { ?axiom <'.OWL_SUBJECT.'> '.$subjectpattern.' . }
				 FILTER (!bound($axiom)).
			 }';
			 //			OPTIONAL { '$subjectpattern.' ?p2 ?o2 . FILTER (isLiter) }
*/
			 
			 
			 //***********************
			 //delete all triples with the current subject 
			 //according to the filters
			 //do not delete special properties see below
			 //Timer::start('LiveUpdateDestination::diffItOWLAxioms::notAnnotated');
			$notAnnotated = 'DELETE  FROM <' . $graphURI . '> 
				 { '.$subjectpattern.' ?p ?o }  
			 WHERE { 
				 GRAPH <'.$graphURI.'> {
				 '.$subjectpattern.' ?p ?o .
				 FILTER ('.$filterForNotAnnotatedTriples.').
			 	}
				 GRAPH <'.$annotationGraphURI.'> {
					  OPTIONAL {
						  ?a <'.OWL_SUBJECT.'> '.$subjectpattern.'.
						  }
					 FILTER (!bound(?a)).	 
					 } 
			 }';
			 //echo $notAnnotated;die;
			 $deleteSPARUL['delete_with_subject_not_static'] = $notAnnotated;
			//Timer::stop('LiveUpdateDestination::diffItOWLAxioms::notAnnotated');
			 //***********************
			 //delete all triples with the current subject 
			 //where the special properties with string object 
			 //from other language version are given, which should stay
			 //Timer::start('LiveUpdateDestination::diffItOWLAxioms::propLangSPARULfilter');
			 $propTerms = array();
			 foreach ($propLangFilter as $one){
			 	 $u = new URI($one);
				 $predicatePattern = $u->toSPARULPattern($this->storespecific);
				 $propTerms[] = ' ?p = '.$predicatePattern.' '; 
			 }
			$propLangSPARULfilter = TripleDiff::assembleTerms($propTerms,'||');
			 
			$deleteEnglishLanguage = 
			'DELETE  FROM <' . $graphURI . '> 
				 { '.$subjectpattern.' ?p ?o }  
			 WHERE { 
				 '.$subjectpattern.' ?p ?o .
				 FILTER ('.$propLangSPARULfilter.' 
				 		&& (isLiteral(?o))
						&& (lang (?o) = \''.$this->language.'\' )
						).
			 }';
/*
			 echo $deleteEnglishLanguage;die;
*/
			 $deleteSPARUL['delete_english'] = $deleteEnglishLanguage;
			//Timer::stop('LiveUpdateDestination::diffItOWLAxioms::propLangSPARULfilter');
			
			//write the new triples
			Timer::start('LiveUpdateDestination::diffItOWLAxioms::insertSPARULCreation');
			
			$insertSPARUL = array();
			$insertSPARUL['insert_triples'] = array();
			$insertSPARUL['insert_annotations'] = array();
			$globalannotationpattern = "";
			$globaltriplepattern = "";
			foreach ($this->tripleFromExtractor as $triple){
					$pattern = $triple->toSPARULPattern($this->storespecific);
					$insertSPARUL['insert_triples'][] = 'INSERT INTO GRAPH <' . $graphURI . '> { ' . $pattern . ' }';
					$globaltriplepattern .= $pattern;
					$annotations = $triple->getOWLAxiomAnnotations();
					Statistics::increaseCount( 'Total','createdAnnotations', count($annotations));
					if(count($annotations)>0){
						$pattern = "";
						foreach($annotations as $ann){
							$current = $ann->toSPARULPattern($this->storespecific);
							$pattern .= $current;
							$globalannotationpattern .= $current;
						}
						//annotations for one triple are aggregated to one query
						$insertSPARUL['insert_annotations'][] = 'INSERT INTO GRAPH <' . $annotationGraphURI . '> { ' . $pattern . ' }';
					}
			}
			$insertSPARUL['globalAnnotationPattern'] = 'INSERT INTO GRAPH <' . $annotationGraphURI . '> { ' . $globalannotationpattern . ' }';
			$insertSPARUL['globalTriplePattern'] = 'INSERT INTO GRAPH <' . $graphURI . '> { ' . $globaltriplepattern . ' }';
			
			Timer::stop('LiveUpdateDestination::diffItOWLAxioms::insertSPARULCreation');
			$result=array();
			$result['del'] = $deleteSPARUL;	
			$result['ins'] = $insertSPARUL;	
			//print_r($result);die;
			
			Timer::stop('LiveUpdateDestination::diffItOWLAxioms::generationOfSPARULqueries');
			return $result;
		
		}
		
	public function smarterDiffItOWLAxioms(){
			Timer::start('LiveUpdateDestination::diffItOWLAxioms::total');
			Timer::start('LiveUpdateDestination::diffItOWLAxioms::preparation');
			//a store is needed for language here
            $store = null;
			//$store = new SPARQLToRDFTriple($this->uri, $this->language);
			$propLangFilter = Options::getOption('stringPredicateWithForeignlanguages');
			$graphURI  = Options::getOption('graphURI');
            $annotationGraphURI = Options::getOption('annotationGraphURI');
			
			//generate the regex filter according to namespaces
			//includes language properties
			foreach($propLangFilter as $one){
				$this->predicateFilterList[] = $one;
				}
			$tripleDiff = new TripleDiff($this->uri,$this->language ,$this->predicateFilterList, $this->objectFilterList, $this->predicateObjectFilterList, $store);
    		$filterForNotAnnotatedTriples =  $tripleDiff->createFilter($this->predicateFilterList, $this->objectFilterList, $this->predicateObjectFilterList);

		
			//$langTriples = $store->getRDFTripleForLangProperties($propLangFilter);
			
			//create a filter for extractors
			$subjectpattern = $this->uri->toSPARULPattern($this->storespecific);
/*
			$extractedByPattern = RDFtriple::URI(DBM_ORIGIN)->toSPARULPattern($this->storespecific);
			
			$extractorFilter = "";
			$extTerms = array();
			foreach ($this->activeExtractors as $one){
			 	 $u = new URI($one);
				 $extPattern = $u->toSPARULPattern($this->storespecific);
				 $extTerms[] = ' ?extractor = '.$extPattern.' '; 
			}
			foreach ($this->purgeExtractors as $one){
			 	 $u = new URI($one);
				 $extPattern = $u->toSPARULPattern($this->storespecific);
				 $extTerms[] = ' ?extractor = '.$extPattern.' '; 
			}
			$extractorFilter = 'FILTER ( '.TripleDiff::assembleTerms($extTerms,'||').') . ';
*/
			$preparation  = Timer::stop('LiveUpdateDestination::diffItOWLAxioms::preparation');
			$this->log(TRACE, 'prep needed: '.$preparation);
			 //***********************
			 //DELETE ALL NON STATIC TRIPLES
			 //**********************
			 //delete all triples with the current subject 
			 //according to the filters
			 //do not delete special properties see below
			 //Timer::start('LiveUpdateDestination::diffItOWLAxioms::notAnnotated');
 			$deleteSPARUL['delete_with_subject_not_static'] = 
'DELETE  FROM <' . $graphURI . '> 
	{ '.$subjectpattern.' ?p ?o }  
WHERE { 
	'.$subjectpattern.' ?p ?o .
	FILTER ('.$filterForNotAnnotatedTriples.').
}';
			
			 //***********************
			 //LANGUAGE
			 //***********************
			 //delete all triples with the current subject 
			 //where the lang properties with string object 
			 //from other language version are given, which should stay
		
			$x = 0;
			//var_dump($langTriples);
                       
			foreach($propLangFilter as $one){
				$u = new URI($one, false);
				$deleteSPARUL['delete_english'.$x++] = 
'DELETE FROM GRAPH <' . $graphURI . '> 
{ ' .$subjectpattern.' '.$u->toSPARULPattern($this->storespecific) . ' ?o }  
WHERE { 
	' .$subjectpattern.' '.$u->toSPARULPattern($this->storespecific) . ' ?o .
	FILTER ( lang(?o) = \'en\').
}';
			}
			
			 //****************************
			 //DELETE ANNOTATIONS
			 //****************************
			 //delete the corresponding annotations

 $deleteSPARUL['delete_corresponding_annotations'] = 
'DELETE  FROM <' . $annotationGraphURI . '> 
	{ ?axiom ?axp  ?axo .  }  
WHERE { 
	?axiom <'.OWL_SUBJECT.'> '.$subjectpattern.' .  
	?axiom ?axp  ?axo .
}';
 //echo $deleteSPARUL['delete_corresponding_annotations'] ;die;
			//***********************
			//MISSING: DELETE ANOMALIES I.E. source Page
			//***********************
			//TODO go to infobox extractor and 
			//add an annotation to all subject/rating objects to which subject they belong
			//and then delete them also	
/*
$deleteSPARUL['delete_anomalies'] = 
'DELETE  FROM <' . $annotationGraphURI . '> 
	{ ?axiom ?axp  ?axo .  }  
WHERE { 
	?axiom <'.DBM_ONDELETECASCADE.'> '.$subjectpattern.' .  
	?axiom ?axp  ?axo .
}';			
*/

			//**********************
			//GENERATE NEW TRIPLES
			//**********************
			Timer::start('LiveUpdateDestination::diffItOWLAxioms::insertSPARULCreation');
			
			$insertSPARUL = array();
			$insertSPARUL['insert_triples'] = array();
			$insertSPARUL['insert_annotations'] = array();
			$globalannotationpattern = "";
			$globaltriplepattern = "";
			$this->log(DEBUG, 'number of triples: '.count($this->tripleFromExtractor) );
			foreach ($this->tripleFromExtractor as $triple){
					$pattern = $triple->toSPARULPattern($this->storespecific);
					$insertSPARUL['insert_triples'][] = 'INSERT INTO GRAPH <' . $graphURI . '> { ' . $pattern . ' }';
					$globaltriplepattern .= $pattern."\n";
					$annotations = $triple->getOWLAxiomAnnotations();
					Statistics::increaseCount( 'Total','createdAnnotations', count($annotations));
					if(count($annotations)>0){
						$pattern = "";
						foreach($annotations as $ann){
							$current = $ann->toSPARULPattern($this->storespecific);
							$pattern .= $current;
							$globalannotationpattern .= $current."\n";
						}
						//annotations for one triple are aggregated to one query
						$insertSPARUL['insert_annotations'][] = 'INSERT INTO GRAPH <' . $annotationGraphURI . '> { ' . $pattern . ' }';
					}
			}
			$this->log(DEBUG, 'number of annotation inserts: '.count($insertSPARUL['insert_annotations']) );
			
			$insertSPARUL['globalAnnotationPattern'] = 'INSERT INTO GRAPH <' . $annotationGraphURI . '> { ' . $globalannotationpattern . ' }';
			$insertSPARUL['globalTriplePattern'] = 'INSERT INTO GRAPH <' . $graphURI . '> { ' . $globaltriplepattern . ' }';
			
			$this->log(DEBUG, 'length globalTriplePattern: '. strlen($insertSPARUL['globalTriplePattern']) );
			$this->log(DEBUG, 'length globalAnnotationPattern: '. strlen($insertSPARUL['globalAnnotationPattern']) );
			
			Timer::stop('LiveUpdateDestination::diffItOWLAxioms::insertSPARULCreation');
			$result=array();
			$result['del'] = $deleteSPARUL;	
			$result['ins'] = $insertSPARUL;	
			
			Timer::stop('LiveUpdateDestination::diffItOWLAxioms::total');
			return $result;
		
		}
	
	public function diffItSimple(){
		Timer::start('TripleDiff::total');
		//language is filtered right here
		$store = new SPARQLToRDFTriple($this->uri, $this->language);
		$tripleDiff = new TripleDiff($this->uri,$this->language ,$this->predicateFilterList, $this->objectFilterList, $this->predicateObjectFilterList, $store);
    	$diff = $tripleDiff->diff($this->tripleFromExtractor);
		Timer::stop('TripleDiff::total');
		
/*
 * 		basically this: is the structured data available
		$diff['filteredoutExtractor'] = $filteredoutExtractor;
		$diff['filteredoutStore'] = $filteredoutStore;
		$diff['insert'] = $insert;
		$diff['delete'] = $delete;
		$diff['remainderStore'] = $remainderStore ;
*/
		
		//CONVERT to triples SPARUL patterns e.g. <s> <p> """o"""@en.
		
		$insertpatterns = array();
		$deletepatterns = array();
		$intersected = array();
		$specialobjects = array();
		foreach($diff['insert'] as $itrip){
			$insertpatterns[] = $itrip->toSPARULPattern($this->storespecific);
			}
		foreach($diff['filteredoutExtractor'] as $itrip){
			$insertpatterns[] = $itrip->toSPARULPattern($this->storespecific);
			}
		foreach($diff['delete'] as $dtrip){
			$deletepatterns[] = $dtrip->toSPARULPattern($this->storespecific);
			}
		foreach($diff['filteredoutStore'] as $dtrip){
			$deletepatterns[] = $dtrip->toSPARULPattern($this->storespecific);
			$specialobjects[] = $dtrip->getObject()->toSPARULPattern($this->storespecific);
			}
		if(!Options::getOption('debug_keep_remaining_triples_from_store')){
			foreach($diff['remainderStore'] as $dtrip){
			$deletepatterns[] = $dtrip->toSPARULPattern($this->storespecific);
			}
		}
		
			
		$intersected = array_intersect($deletepatterns, $insertpatterns);
		$insertpatternstmp=array_diff($insertpatterns, $deletepatterns);
    	$deletepatternstmp=array_diff($deletepatterns, $insertpatterns);
    	
		$insertpatterns = $insertpatternstmp;
		$deletepatterns = $deletepatternstmp;
		
/*
		print_r($intersected);
		print_r($deletepatterns);
		print_r($insertpatterns);
*/

		$graphURI  = Options::getOption('graphURI');
		$deleteSPARUL = $this->deleteToSparul($graphURI, $deletepatterns, $specialobjects);
		$insertSPARUL = $this->insertToSparul($graphURI, $insertpatterns);
		
		$result=array();
		$result['del'] = $deleteSPARUL;	
		$result['ins'] = $insertSPARUL;	
		
		return $result;
	}
	
	public function deleteToSparul($graphURI, $deletepatterns, $specialobjects){
			$ret = array();
			foreach($deletepatterns as $pattern){
				$ret[] = 'DELETE FROM GRAPH <' . $graphURI . '> {' . $pattern . ' }';
				}
			
			foreach($specialobjects as $pattern){
				//$this->log(WARN, $pattern . " not deleted see line 161");
				  $ret[] = 'DELETE  FROM <' . $graphURI . '> { '.$pattern.' ?p ?o }  WHERE { '.$pattern.' ?p ?o  }';
				//$ret[] = 'DELETE * FROM GRAPH <' . $graphURI . '> WHERE {' .$pattern . ' ?p ?o }';
				}
			return $ret;
		}
		
	public function insertToSparul($graphURI, $insertpatterns){
			
			$ret = array();
			foreach($insertpatterns as $pattern){
				$ret[] = 'INSERT INTO GRAPH <' . $graphURI . '> {' . $pattern . '}';
				}
			return $ret;
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
	
	private function loadIntoVirtuoso($odbc, $deleteSPARUL, $insertSPARUL, $graphURI){
			$countdel = 0;
			$countins =0;
			
			foreach($deleteSPARUL as $key => $query){
				Timer::start('LiveUpdateDestination::del::'.$key);
				//Logger::info('SPARUL'.$key);
				$this->executeUpdate($odbc, $query, $graphURI) ;
				//Logger::info('SPARUL'.$key);
				Timer::stop('LiveUpdateDestination::del::'.$key);
				$countdel++;
			}
			if(Options::getOption('debug_turn_off_insert')){
				$this->log(INFO, 'no of queries, insert: '.$countins.' delete: '.$countdel);
				odbc_close_all();
				return ;
				}
			
			//if global batch is successfull
			Timer::start('LiveUpdateDestination::insertGlobalTriplePattern');
			//$timeWasted = microtime(true);
			$globalSuccess = $this->executeUpdate($odbc, $insertSPARUL['globalTriplePattern'], $graphURI) ;
			
			//$timeWasted = (microtime(true) - $timeWasted );
			//if(false === $globalSuccess){Timer::staticTimer('LiveUpdateDestination::insertFailedBecauseOfLongQuery', $timeWasted);}
			Timer::stop('LiveUpdateDestination::insertGlobalTriplePattern');
			
			$allGood = true;
			$stillAllGood = true;
			if(false === $globalSuccess) {
				//Logger::info('global insert of Triples failed, inserting each triple');
				Timer::start('LiveUpdateDestination::insertSingleTriples');
				foreach($insertSPARUL['insert_triples'] as $query){
					$countins++;
					$allGood = ($allGood && $this->executeUpdate($odbc, $query, $graphURI) );
				}
				Timer::stop('LiveUpdateDestination::insertSingleTriples');
			}else{
				$countins = 1;
				}
			
			if($allGood){
				$countins += 1;
				Timer::start('LiveUpdateDestination::insertGlobalAnnotationPattern');
				$stillAllGood = $this->executeUpdate($odbc, $insertSPARUL['globalAnnotationPattern'], $graphURI) ;
				Timer::stop('LiveUpdateDestination::insertGlobalAnnotationPattern');
			}
			
			
			if(!$stillAllGood){
				//Logger::info('global insert of Annotations failed, inserting each triple');
				Timer::start('LiveUpdateDestination::insertSingleAnnotations');
				foreach ( $insertSPARUL['insert_annotations'] as $query){
					$countins++;
					$this->executeUpdate($odbc, $query, $graphURI) ;
				}
				Timer::stop('LiveUpdateDestination::insertSingleAnnotations');
			}
			
			$this->log(INFO, 'no of queries, insert: '.$countins.' delete: '.$countdel);

			
		}
		
	private function loadIntoVirtuosoSimple($odbc, $deleteSPARUL, $insertSPARUL, $graphURI){
		
			$this->log(INFO, 'no of queries, insert: '.count($insertSPARUL).' delete: '.count($deleteSPARUL));
			foreach($deleteSPARUL as $query){
				Timer::start('LiveUpdateDestination::deleteSPARUL');
				$this->executeUpdate($odbc, $query, $graphURI) ;
				Timer::stop('LiveUpdateDestination::deleteSPARUL');
			}
			
			foreach($insertSPARUL as $query){
				Timer::start('LiveUpdateDestination::insertSPARUL');
				$this->executeUpdate($odbc, $query, $graphURI) ;
				Timer::stop('LiveUpdateDestination::insertSPARUL');
			}
		
			//odbc_close_all();
		}
	
    private function executeUpdate($odbc, $query, $graphURI)   {
		

		$result = false;
		if(Options::getOption('dryRun')){
			$this->log(INFO,$query);
			$result = true;
		}else{
			 // escape characters that delimit the query within the query
        	$query = addcslashes($query, '\'\\');
        	// build Virtuoso/PL query
        	//$virtuosoPl = 'CALL DB.DBA.SPARQL_EVAL(\'' . $query . '\', \'' . $graphURI . '\', 0)';
        	$virtuosoPl = 'CALL DB.DBA.SPARQL_EVAL(\'' . $query . '\', NULL,  0)';
			$result = $odbc->exec( $virtuosoPl,'LiveUpdateDestination');
			$this->log(TRACE, $virtuosoPl);
		}
		return $result;
		
    }
   
   	private  function log($lvl, $message){
			
				Logger::logComponent('destination', get_class($this), $lvl , $message);
		}
    
}

