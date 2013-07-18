<?php 
include('dbpedia.php');
$storespecific = VIRTUOSO;
$graphURI = 'http://dbpedia.org';
$annotationGraphURI = 'http://dbpedia.org/meta';
$odbc = ODBC::getDefaultConnection();
$odbc->connect();

$total = 0 ;
while (count($triples = getAnnotations($graphURI)) > 0 ){
	
	$total += deleteSubjects($odbc, $graphURI, $triples, $storespecific);
	echo 'executed '.$total. ' delete queries'."\n";
	
	}


//deleteTriples($odbc,$graphURI, $triples, $storespecific);


function getAnnotations($graphURI){
		$lang = 'en';
		$store = new SPARQLToRDFTriple(null, $lang);
		$query = 'SELECT * FROM <'.$graphURI.'> WHERE {
			?s <'.RDF_TYPE.'> <'.OWL_AXIOM.'> .
			FILTER (isURI(?s))
		} Limit 100';
		//$subject =array('action'=>'classattribute','value'=>null);
		$subject =array('action'=>'variable','value'=>'s');
		$predicate =array('action'=>'fix','value'=>OWL_SUBJECT);
		$object =array('action'=>'fix','value'=>OWL_AXIOM);
		Logger::debug( $query);
		$triples = $store->getRDFTriples($query, $subject, $predicate, $object, false);
		Logger::debug('retrieved: '.count($triples).' triples');
		return $triples;
	
	}



	

function deleteSubjects($odbc, $graphURI, $triples, $storespecific){
		$sparul = "";
		$a = 'sparql DELETE FROM  <'.$graphURI.'>'; 
		$first = true;
		foreach ( $triples as $one){
			if($first){
				echo $one->getSubject()->getURI()."\n";
				$first = false;
				}
			//echo $one;continue;
			$sparul.= $a; 
			$sparul.=' { '. $one->getSubject()->toSPARULPattern($storespecific).' ?p ?o }';
			$sparul.=' WHERE { '. $one->getSubject()->toSPARULPattern($storespecific).' ?p ?o }';
			echo $sparul; 
			$res = $odbc->exec($sparul, 'maintenance');
			if(false != $res) {
				odbc_fetch_array($res);
			}
			print_r($res);
			$sparul="";
			}
		
		return count($triples);
	}	


function test(){
	
		$s = new URI('http://dbpedia.org/resource/London');
		$lang = 'en';
		$store = new SPARQLToRDFTriple($s, $lang);
		$query = 'SELECT * WHERE {?s ?p ?o} Limit 10';
		$subject =array('action'=>'classattribute','value'=>null);
		$subject =array('action'=>'variable','value'=>'s');
		$predicate =array('action'=>'variable','value'=>'p');
		$object =array('action'=>'variable','value'=>'o');
		$r = $store->getRDFTriples($query, $subject, $predicate, $object);
/*
		foreach ($r as $one){
			echo $one->toNTriples();
			
			}
*/
		return $r;
	
	}

function deleteTriples($odbc, $graphURI, $triples, $storespecific){
		$pattern = "";
		foreach ( $triples as $one){
			$pattern.= $one->toSPARULPattern($storespecific)."\n";
			}
		$sparul = 'sparql DELETE FROM <'.$graphURI.'>  { '.$pattern.'}';
		echo $sparul;die;
		$odbc->exec($sparul, 'maintenance');
	}
