<?php



class SPARQLToRDFTriple  {
	
	private $subject;
	private $language;
	private $sparqlEndpoint;
	private $odbc;
	private $use = null;
	
	public function __construct(URI $subject, $language){
			$this->subject = $subject;
			$this->language = $language;
			$this->use = Options::getOption('Sparql.use');
			if($this->use=='odbc'){
				$this->odbc=ODBC::getDefaultConnection();
			}else{
				$this->sparqlEndpoint = SPARQLEndpoint::getDefaultEndpoint();
			}
		}
    
	
	   public function getRDFTripleForLangProperties($languageProperties){
		
			$result = array();
			foreach($langProp as $oneproperty){
				$query = 'SELECT ?o WHERE { <'.$this->subject->getURI().'> <'.$oneproperty.'>  ?o  }';
				//$query = 'SELECT * WHERE {?s ?p ?o} Limit 10';
				$subject =array('action'=>'classattribute','value'=>null);
				$predicate =array('action'=>'fix','value'=>$oneproperty);
				$object =array('action'=>'variable','value'=>'o');
				$triples = $this->getRDFTriples($query, $subject, $predicate, $object);
				foreach ($triples as $one){
					$result[]=$one;
					}
				
			}
			return $result;
		
		}

   
   
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
   public function getRDFTriples($query, $subject, $predicate, $object, $filterlanguage = true){
	   		
			if($subject['action'] == 'fix'){
				$s = new URI($subject['value'],false);
				
			}else if($subject['action'] == 'classattribute'){
				$s = $this->subject;
			}
			if($predicate['action'] == 'fix'){
				$p = new URI($predicate['value'],false);
			}
			if($object['action'] == 'fix'){
				try {
				$o = new URI($object['value'],false);	
				}catch(Exception $e) {
					$this->log(WARN, 'object = fix only for uris currently');
				return array();
				}
			}
			
			$result = array();
			
			$pass = false;
			if($this->use=='odbc'){
				Timer::start(get_class($this).':odbc_request');
				$jarr = $this->odbc->execAsJson($query, 'SPARQLToRDFTriple');
				Timer::stop(get_class($this).':odbc_request');
			}else{
				Timer::start(get_class($this).':http_request');
				$json = $this->sparqlEndpoint->executeQuery($query, 'SPARQLToRDFTriple');
				$jarr = json_decode($json, true);
				if(isset($jarr['results'])){
					$jarr = $jarr['results'];
					}
				Timer::stop(get_class($this).':http_request');
			}
			
			
			//print_r($result);
			if(isset($jarr['bindings']) ){
				$bindings = $jarr['bindings'];
				foreach($bindings as $one){
						try{
							switch($subject['action']){
								case 'fix':{
									//$s is already set								
									break;}
								case 'variable':{
									$s = new URI($one[$subject['value']]['value']);
									break;}
								case 'classattribute':{
									//$s is already set
									break;}
							}
							switch($predicate['action']){
								case 'fix':{
									//$p is already set	
									break;}
								case 'variable':{
									$p = new URI($one[$predicate['value']]['value']);
									break;}
							}
							switch($object['action']){
								case 'fix':{
									//$o is already set	
									break;}
								case 'variable':{
									$unknown = $one[$object['value']];
									$o = $this->toObject($unknown, $filterlanguage);
									if($o === false) {continue;	}
									break;}
							}
											
						$this->log(DEBUG, "*******************");
						$this->log(DEBUG, $s->toNTriples());
						$this->log(DEBUG, $p->toNTriples());
						$this->log(DEBUG, $o->toNTriples());
						$result[]  = new RDFtriple($s, $p, $o);
						//$this->log(DEBUG, $t->getObject()->toNTriples());
						}catch(Exception $ex){
							$this->log(WARN, 'found invalid URI: '.$ex->getMessage());
							}
					}
			 }else{
				$this->log(WARN, $json); 
				 }
			return $result;
	   }
   
   
   /*
    * makes RDFtriples with $this->subject ?p ?o
	* similar to construct, but uses internal format right away
	* */
/*
   	public  getRDFTriples($query){
		
			$s = $this->subject;
			$result = array();
			Timer::start(get_class($this).':http_request');
			$json = $this->sparqlEndpoint->executeQuery($query, 'SPARQLToRDFTriple');
			Timer::stop(get_class($this).':http_request');
			$jarr = json_decode($json, true);
			if(isset($jarr['results']) && isset($jarr['results']['bindings']) ){
				$bindings = $jarr['results']['bindings'];
				foreach($bindings as $one){
						try{
						$p = new URI($one['p']['value']);
						$unknown = $one['o'];
						$o = $this->toObject($unknown);
						if($o === false) {
							continue;
						}
							
						$this->log(DEBUG, $s->toNTriples());
						$this->log(DEBUG, $p->toNTriples());
						$this->log(DEBUG, $o->toNTriples());
						$result[] = new RDFtriple($s, $p, $o);
						}catch(Exception $ex){
							$this->log(WARN, 'found invalid URI: '.$ex->getMessage());
							}
					}
			 }
			return $result;
		}
*/
		
	public function toObject($unknown, $filterlanguage = true){
			if($unknown['type']=='uri'){
				return new URI($unknown['value']);
			}else if ($unknown['type']=='literal'){
				if(isset($unknown['xml:lang'])){
					if($unknown['xml:lang']==$this->language){
						return new RDFliteral($unknown['value'], null,$unknown['xml:lang'] );	
					}else if(!$filterlanguage) {
						return new RDFliteral($unknown['value'], null,$unknown['xml:lang'] );
					}	
				}else {
					return new RDFliteral(	$unknown['value'],  null,  null);
				}
			}else if ($unknown['type']=='typed-literal'){
				return new RDFliteral($unknown['value'], $unknown['datatype'], null );
			}else{
				die("tail in SPARQLToRDFTriple::toObject ");
				}
		
		}
    
   
		
	
		
	public  function log($lvl, $message){
			
				Logger::logComponent('destination', get_class($this), $lvl , $message);
		}
    
/*	LEGACY CODE, might be usefull later
 * 
     private function getDataAsArray($uri){
			Timer::start('sparqlretrieval::httpsend');
			$xmlString = $this->getDataViaSPARQLHTTP($uri);
			Timer::stop('sparqlretrieval::httpsend');
			Timer::start('sparqlretrieval::parsexml');
			$ret = array();
			
			//dirty hack
			//$xmlString = str_replace("xml:lang", "lang", $xmlString);

		//$query  = urlencode( $query );
			//echo $xmlString;
			//echo "<xmp>";
			$xml = simplexml_load_string($xmlString);
			foreach ( $xml->results->result as $result){
					//print_r($result);
					
					$lang="en";
					$oneResult = array();
				foreach ( $result->binding as $binding){
						
						if($binding->attributes()== 'p'){
							//$p = $binding->uri;
							$oneResult['p'] = (string)$binding->uri;
							
							//echo $p."\n"; 
							}
						else if($binding->attributes()== 'o'){
							if(isset($binding->uri)){
								//$o = $binding->uri;
								$oneResult['o'] = (string)$binding->uri;
								$oneResult['otype'] = "resource";
								//echo $o."\n";
								}
							else if(isset($binding->literal)){
								$lit = $binding->literal;
								$datatype = "";
								$language = "";
								if(isset($lit['datatype']))	{
										$datatype = "^^<".$lit['datatype'].">";
										//echo $datatype."\n";
									}						
								else if(isset($lit['lang'])) {
									 $lang = $lit['lang'];
									// echo $lang."\n";
									 $language="@".$lit['lang'];
									}
								$lit = "\"".$lit."\"".$language.$datatype;
								$oneResult['o'] = $lit;
								$oneResult['otype'] = "literal";
								//print_r($binding);
								}
							else {
								echo "tail in getDataAsArray\n";
								}
							}	
						
						
					}//inner foreach
				if(!($lang == "en" || $lang =="de") ){
					continue;
				}
				$ret[]=$oneResult;
				
    	}//outerforeach
    	Timer::stop('sparqlretrieval::parsexml');
    	//print_r($ret);
    	return $ret;
    }
*/
}

