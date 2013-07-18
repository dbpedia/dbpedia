<?php
class SPARQLEndpoint {
	var $sparqlendpointURL;
	var $defaultGraphURI;
	var $format = "JSON";
	const wait =5;
	const cutstring = 1000;
	
public function __construct($sparqlendpointURL, $defaultGraphURI = "", $format = "JSON"){
		$this->sparqlendpointURL = $sparqlendpointURL;
		$this->defaultGraphURI = $defaultGraphURI;
		$this->format = $format;
	}
	
public static function getDefaultEndpoint(){
		 $sparqlendpointURL = Options::getOption('sparqlendpoint');
    	 $defaultGraphURI       = Options::getOption('graphURI');
    	return new SPARQLEndpoint($sparqlendpointURL, $defaultGraphURI);
	}
	
private function _getDefaultGraphURI($defaultGraphURI=null){
		if(!empty($this->defaultGraphURI)&& $defaultGraphURI===null){
			return "&default-graph-uri=".urlencode($this->defaultGraphURI);
		} else if(!($defaultGraphURI === false)){
			return "&default-graph-uri=".urlencode($defaultGraphURI);
		}else {
			return '';
			}
	}
private function _getFormat($format=null){
		if(!empty($this->format) && $format===null){
			return "&format=".$this->format;
		} else if(!($format === false)){
			return "&format=".$format;
		}else {
			return '';
			}
	}

 public function executeQuery($query, $logComponent, $defaultGraphURI=null, $format=null){
			$sparqlendpoint  = $this->sparqlendpointURL;
    		$url = $sparqlendpoint."?query=";
			$url .= urlencode($query);
			$url .= $this->_getDefaultGraphURI($defaultGraphURI);
			$url .=$this->_getFormat($format);
			
		   // echo $url;
		    Timer::start($logComponent.'::http_sparqlquery');
			Logger::debug($logComponent."::url = ".$url);
			$c = curl_init();
			curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($c, CURLOPT_URL, $url);
			//curl_setopt($c, CURLOPT_HTTPHEADER, $headers);
			$contents = curl_exec($c);
			if($contents === false){
					$error = "\nSparqlRetrieval: endpoint failure $sparqlendpoint\n";
					$error .="Error: ".curl_error($c)."\n";
					$error .="URL: ".$url."\n";
					$error .="Query: ".$query."\n";
					Logger::error($logComponent.$error);
					
				}
			curl_close($c);
			 Timer::stop($logComponent.'::http_sparqlquery');
			//echo $contents;die;
			Logger::debug($logComponent. "returned: ".strlen($contents)." of json code");
			return $contents;
    	}
	
	
	
	/*
	 * returns just a number
	 * $query must be like SELECT count(*) as ?count WHERE...
	 * */
public function executeCount($query, $logComponent, $defaultGraphURI=null, $format=null){
				Timer::start($logComponent.'::http::count');
				$json = $this->executeQuery($query, $logComponent, $defaultGraphURI, $format);
				$jarr = json_decode($json, true);
				Timer::stop($logComponent.'::http::count');
/*
				"results": { "distinct": false, "ordered": true, "bindings": [
    { "count": { "type": "typed-literal", "datatype": "http://www.w3.org/2001/XMLSchema#integer", "value": "30" }} ] } }
*/
				if(isset($jarr['results']) && isset($jarr['results']['bindings']) ){
					$bindings = $jarr['results']['bindings'];
					foreach($bindings as $one){
						$count = $one['count'];
						return $count['value'];
					}	
				}else{
                    return 0;    
                }
	}
	
public function test(){
		$query = 'SELECT count(*) as ?count WHERE {<http://dbpedia.org/resource/London> ?p ?o .}';
		$c =  $this->executeCount($query, 'testing');
		echo $c ."\n";
		if($c>5){
			echo 'bigger than 5'."\n";
			}
		die;
	}


}
