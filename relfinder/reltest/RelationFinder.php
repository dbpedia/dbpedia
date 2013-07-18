<?php

//require_once('Filter.php');

class RelationFinder {

	// $endpointURI = "http://db0.aksw.org:8890";
	private $endpointURI = "http://dbpedia.org/sparql";
	// default graphy URI can be empty, but it is usually fast to specify it
	private $defaultGraphURI = "http://dbpedia.org";
	private $contentType = "application/sparql-results+json";
	// prefix for all resources (not really needed, but makes queries more readable)
	private $prefixes = array(
		"db" => "http://dbpedia.org/resource/",
		"rdf" => "http://www.w3.org/1999/02/22-rdf-syntax-ns#",
		"skos" => "http://www.w3.org/2004/02/skos/core#"
		);
		
	 /**
	  * Send SPARQL query to endpoint and return result.
	  */
	function executeSparqlQuery($sparqlQueryString, $format = "JSON"){
				
		// echo '<pre>'.htmlentities($sparqlQueryString).'</pre>';
		$url = $this->endpointURI."/sparql?query=";
				
		$defaultGraphString = (strlen($this->defaultGraphURI)==0)?"":"&default-graph-uri=".$this->defaultGraphURI;
		$format="&format=".$format;
		$url .= urlencode($sparqlQueryString).$defaultGraphString.$format;
		
		// Accept: application/xml, text/html, application/sparql-results+json,
		// application/javascript, application/sparql-results+xml, text/rdf+n3
		//$headers = array("Accept: application/sparql-results+xml");
		$headers = array("Content-Type: ".$this->contentType);

		$c = curl_init();
		curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($c, CURLOPT_URL, $url);
		curl_setopt($c, CURLOPT_HTTPHEADER, $headers);
		$contents = curl_exec($c);
		//file_put_contents("curl.log",$url."\n".$contents);
		curl_close($c);
		return $contents;
	}
		
	/**
	 * Takes the core of a SPARQL query and completes it (e.g. adds prefixes).
	 * 
	 */
	private function completeQuery($coreQuery, $options, $vars) {
		$completeQuery = '';
		foreach($this->prefixes as $key=>$value) {
			$completeQuery .= 'PREFIX '.$key.': <'.$value.">\n";
		}
		$completeQuery .= 'SELECT * WHERE {'."\n";
		$completeQuery .= $coreQuery."\n";
		$completeQuery .= $this->generateFilter($options, $vars)."\n";
		$completeQuery .= '} '. ((isset($options['limit']))?'LIMIT '.$options['limit']:"");
		return $completeQuery;
	}
	/**
	 * simple startsWith function 
	 */
	function startsWith($Haystack, $Needle){
  	  // Recommended version, using strpos
 	   return strpos($Haystack, $Needle) === 0;
	}
		
	/**
	 * Takes a URI and formats it according to the prefix map.
	 * This basically is a fire and forget function, punch in 
	 * full uris, prefixed uris or anything and it will be fine
	 * 
	 * 1. if uri can be prefixed, prefixes it and returns
	 * 2. checks whether uri is already prefixed and returns
	 * 3. else it puts brackets around the <uri>
	 */
	private function uri($uri) {
		
		foreach($this->prefixes as $key=>$value) {
			if($this->startsWith($uri, $value )){
				$uri = str_replace($value, $key.':', $uri);
				return $uri;
			}
		}
		
		$prefixes = array_keys($this->prefixes);
		$check = substr($uri,0,strpos($uri,":"));
		if (in_array($check,$prefixes)){
			return $uri;
			}
				
		return "<".$uri.">";
			
	}
	
		
	/**
	 * Return a set of queries to find relations between two objects.
	 * 
	 * @param object1 First object.
	 * @param object2 Second object.
	 * @param maxDistance The maximum distance up to which we want to search.
	 * @param limit The maximum number of results per SPARQL query (=LIMIT).
	 * @param ignoredObjects Objects which should not be part of the returned connections between the first and second object.
	 * @param ignoredProperties Properties which should not be part of the returned connections between the first and second object.
	 * @param avoidCycles Integer value which indicates whether we want to suppress cycles, 
	 * 			0 = no cycle avoidance
	 * 			1 = no intermediate object can be object1 or object2
	 *  		2 = like 1 + an object can not occur more than once in a connection.
	 * @return A two dimensional array of the form $array[$distance][$queries].
	 */
	function getQueries($object1, $object2, $maxDistance, $limit, $ignoredObjects, $ignoredProperties, $avoidCycles) {
		$queries = array();
		$options = array();
		$options['object1'] = $object1;
		$options['object2'] = $object2;
		$options['limit'] = $limit;
		$options['ignoredObjects'] = $ignoredObjects;
		$options['ignoredProperties'] = $ignoredProperties;
		$options['avoidCycles'] = $avoidCycles;
		
		for($distance=1; $distance<=$maxDistance; $distance++) {
			// get direct connection in both directions
			$queries[$distance][] = $this->direct($object1, $object2, $distance, $options);
			$queries[$distance][] = $this->direct($object2, $object1, $distance, $options);
			
			/*
			 * generates all possibilities for the distances
			 * 
			 * current
			 * distance 	a 	b
			 * 2			1	1
			 * 3			2	1
			 * 				1	2
			 * 4			3	1
			 * 				1	3
			 * 				2	2
			 * */
			
			for($a=1; $a<=$distance; $a++) {
				for($b=1; $b<=$distance; $b++) {
					if($a+$b==$distance){
						$queries[$distance][] = $this->connectedViaAMiddleObject($object1, $object2,$a, $b, true,  $options);
						$queries[$distance][] = $this->connectedViaAMiddleObject($object1, $object2,$a, $b, false,  $options);
						//echo $a.$b."\n";
					}
				}
			}
		}
		return $queries;
	}
	
	
	/**
	 * Return a set of queries to find relations between two objects, 
	 * which are connected via a middle objects.
	 * $dist1 and $dist2 give the distance between the first and second object to the middle
	 * they have ti be greater that 1
	 * 
	 * Patterns:
	 * if $toObject is true then:
	 * PATTERN												DIST1	DIST2
	 * first-->?middle<--second 						  	1		1
	 * first-->?of1-->?middle<--second						2		1
	 * first-->?middle<--?os1<--second 						1		2
	 * first-->?of1-->middle<--?os1<--second				2		2
	 * first-->?of1-->?of2-->middle<--second				3		1
	 * 
	 * if $toObject is false then (reverse arrows)
	 * first<--?middle-->second 
	 * 
	 * the naming of the variables is "pf" and "of" because predicate from "f"irst object
	 * and "ps" and "os" from "s"econd object
	 * 
	 * @param first First object.
	 * @param second Second object.
	 * @param dist1 Distance of first object from middle
	 * @param dist2 Distance of second object from middle
	 * @param toObject Boolean reverses the direction of arrows.
	 * @param options All options like ignoredProperties, etc. are passed via this array (needed for filters)
	 * @return the SPARQL Query as a String
	 */
	function connectedViaAMiddleObject($first, $second, $dist1, $dist2, $toObject,  $options){
			$properties =array();
			$vars = array();
			$vars['pred'] = array();
			$vars['obj'] = array();
			$vars['obj'][] =  '?middle';
			
			$fs = 'f';
			$tmpdist = $dist1;
			$twice = 0;
			$coreQuery = "";
			$object = $first;
			
			// to keep the code compact I used a loop
			// subfunctions were not appropiate since information for filters is collected
			// basically the first loop generates $first-pf1->of1-pf2->middle
			// while the second generates $second -ps1->os1-pf2->middle
			while($twice < 2){
				
				if($tmpdist == 1) {
					$coreQuery .= $this->toPattern($this->uri($object), '?p'.$fs.'1', '?middle', $toObject);
					$vars['pred'][] =  '?p'.$fs.'1';
				}else {
					$coreQuery .= $this->toPattern($this->uri($object), '?p'.$fs.'1', '?o'.$fs.'1', $toObject);
					$vars['pred'][] =  '?p'.$fs.'1';
					
					for($x=1;$x<$tmpdist;$x++ ){
						$s = '?o'.$fs.''.$x;
						$p = '?p'.$fs.''.($x+1) ; 
						$vars['obj'][] =  $s;
						$vars['pred'][] =  $p;
						if(($x+1)==$tmpdist){
							$coreQuery .= $this->toPattern($s , $p , '?middle', $toObject);
						}else{
							$coreQuery .=$this->toPattern($s , $p , '?o'.$fs.''.($x+1), $toObject);
						}
					}
				}
				$twice++;
				$fs = 's';
				$tmpdist = $dist2;
				$object = $second;
				
			}//end while
			
			return  $this->completeQuery($coreQuery, $options, $vars);
		}  

		/*
		 * Helper function to reverse the order 
		 * */
	function toPattern($s, $p, $o, $toObject){
		if($toObject){
			return $s.' '.$p.' '.$o." . \n";
		}else {
			return $o.' '.$p.' '.$s." . \n";
		}
		
	}
		
	
  
  	/**
  	 * Returns a query for getting a direct connection from $object1 to $object2.
  	 */
	function direct($object1, $object2, $distance, $options) {
		$vars = array();
		$vars['obj'] = array();
		$vars['pred'] = array();
		if($distance == 1) {
			$retval =  $this->uri($object1) .' ?pf1 '. $this->uri($object2);
			$vars['pred'][] = '?pf1';
			return $this->completeQuery($retval,  $options, $vars);
			
		} else {
			$query = $this->uri($object1) .' ?pf1 ?of1 '.".\n";
			$vars['pred'][] = '?pf1';
			$vars['obj'][] = '?of1';
			for($i = 1; $i < $distance-1; $i++) {
				$query .= '?of'.$i.' ?pf'.($i+1).' ?of'.($i+1).".\n";
				$vars['pred'][] = '?pf'.($i+1);
				$vars['obj'][] = '?of'.($i+1);
			}
			$query .= '?of'.($distance-1).' ?pf'.$distance.' '.$this->uri($object2);
			$vars['pred'][] = '?pf'.$distance;
			//$vars['obj'][] = '?of'.($distance-1);
			return $this->completeQuery($query, $options, $vars);
		}
		
	}	
		
	/*
	 * assembles the filter according to the options given and the variables used
	 * @param vars 
	 * array(1) {
  			["pred"]=>
  				array(1) {
    					[0]=>string(4) "?pf1"
  						}
  			["obj"]=>
  				array(1) {
    					[0]=>string(4) "?of1"
  						}
			}
	 *
	 * */
	function generateFilter($options, $vars){
		//var_dump($vars);
		//die;
		$filterterms = array();
		foreach($vars['pred'] as $pred) {
			// ignore properties
			if(isset($options['ignoredProperties']) && count($options['ignoredProperties'])>0){
				foreach ($options['ignoredProperties'] as $ignored){
						$filterterms[] =  $pred.' != '.$this->uri($ignored).' ';
				}
			}
			
		}
		foreach($vars['obj'] as $obj) {
			// ignore literals
			$filterterms[] ='!isLiteral('.$obj.')';
			// ignore objects
			if(isset($options['ignoredObjects']) && count($options['ignoredObjects'])>0){
				foreach ($options['ignoredObjects'] as $ignored){
						$filterterms[] =  $obj.' != '.$this->uri($ignored).' ';
				}
			}
			
			if(isset($options['avoidCycles']) ){
				// object variables should not be the same as object1 or object2
				if( $options['avoidCycles'] > 0){
					$filterterms[] =  $obj.' != '.$this->uri($options['object1']).' ';
					$filterterms[] =  $obj.' != '.$this->uri($options['object2']).' ';
				}
				// object variables should not be the same as any other objectvariables
				if( $options['avoidCycles'] > 1){
					foreach($vars['obj'] as $otherObj) {
						if($obj != $otherObj){
							$filterterms[] =  $obj.' != '.$otherObj.' ';
						}
					}
				}
			}
			
			
			
		}
		
		return 'FILTER '.$this->expandTerms($filterterms, '&&').'. ';
	}
		
	/*
	 * puts bracket around the (filterterms) and concatenates them with &&
	 * 
	 * */
	function expandTerms ($terms, $operator = "&&"){
		$result="";
		for ($x=0;$x<count($terms);$x++){
			$result.= "(".$terms[$x].")";
			$result.= ($x+1==count($terms)) ? "" : " ".$operator." ";
			$result.= "\n";
		}
		return "(".$result.")";
	}


	
		
	}
    
