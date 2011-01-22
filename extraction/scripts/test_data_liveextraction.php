<?php 
define('RDFS_LABEL','<http://www.w3.org/2000/01/rdf-schema#label>');
//define('ENDPOINTURI','http://dbpedia.org/sparql?query=');
define('ENDPOINTURI','http://dbpedia2.openlinksw.com:8895/sparql?query=');
//define('ENDPOINTURI','http://localhost:8890/sparql?query=');
//define('ENDPOINTCURLOPT_PORT','8895');
define('DB_GRAPH','http://dbpedia.org');
define('DBM_GRAPH','http://dbpedia.org/meta');
define('LN',"<br>\n");
$limit = 20;
global $prefix ;
$prefix = "
PREFIX owl: <http://www.w3.org/2002/07/owl#>
PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX foaf: <http://xmlns.com/foaf/0.1/>
PREFIX dc: <http://purl.org/dc/elements/1.1/>
PREFIX : <http://dbpedia.org/resource/>
PREFIX dbpedia2: <http://dbpedia.org/property/>
PREFIX dbpedia: <http://dbpedia.org/>
PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
";
?>
<html>
<head>
 <style> 
    <!--
	table {font-size:9px;}
	
    -->
   </style>

</head>
<body>

<?php 
$queries = array();

/*
$e = 'any result, to see if server is working';
$q = 'SELECT * FROM <'.DB_GRAPH.'> WHERE {?s rdf:type ?o} LIMIT 10';
$queries [] = new Query($e, $q);
*/

$e = 'EMPTY, checks if wrong annotations are in MAIN graph owl:Axiom ';
$q = 'SELECT * FROM <'.DB_GRAPH.'> WHERE {
	?a owl:annotatedSource ?s } LIMIT 5';
$queries [] = new Query($e, $q);

$e = 'EMPTY, checks if wrong annotations are in MAIN graph http://www.w3.org/2006/12/owl2#Axiom';
$q = 'SELECT * FROM <'.DB_GRAPH.'> WHERE {
	?a  <http://www.w3.org/2006/12/owl2#annotatedSource> ?s } LIMIT 5';
$queries [] = new Query($e, $q);

$e = 'EMPTY, checks if wrong annotations are in MAIN graph  owl:subject';
$q = 'SELECT * FROM <'.DB_GRAPH.'> WHERE {
	?a  owl:subject ?s } LIMIT 5';
$queries [] = new Query($e, $q);



$e = '5 results, checks if annotations are in META graph owl:Axiom ';
$q = 'SELECT * FROM <'.DBM_GRAPH.'> WHERE {
	?a owl:annotatedSource ?s } LIMIT 5';
$queries [] = new Query($e, $q);

$e = 'EMPTY, wrong vocab, checks if annotations are in META graph http://www.w3.org/2006/12/owl2#Axiom';
$q = 'SELECT * FROM <'.DBM_GRAPH.'> WHERE {
	?a <http://www.w3.org/2006/12/owl2#annotatedSource> ?s } LIMIT 5';
$queries [] = new Query($e, $q);

$e = 'EMPTY, wrong vocab, checks if annotations are in META graph  owl:subject';
$q = 'SELECT * FROM <'.DBM_GRAPH.'> WHERE {
	?a  owl:subject ?s} LIMIT 5';
$queries [] = new Query($e, $q);

/*
$e = 'empty, checks if annotations are in main graph';
$q = 'SELECT * FROM <'.DBM_GRAPH.'> WHERE {
	?a rdf:type owl:Axiom } LIMIT 5';
$queries [] = new Query($e, $q);
*/



$e = '6 or more, checks what propeties for annotations exists in META';
$q = 'SELECT ?a ?p ?o FROM <'.DBM_GRAPH.'> WHERE {
	?a owl:annotatedSource ?s .?a ?p ?o} LIMIT 30';
$queries [] = new Query($e, $q);

$e = 'gives a sample of meta:extractedFromTemplate in META';
$q = 'SELECT ?a  ?o FROM <'.DBM_GRAPH.'> WHERE {
	?a owl:annotatedSource ?s .
	?a <http://dbpedia.org/meta/extractedFromTemplate> ?o} LIMIT 100';
$queries [] = new Query($e, $q);

$e = 'EMPTY, wrong vocabulary, gives a sample of meta:pageSource in META';
$q = 'SELECT ?a  ?o FROM <'.DBM_GRAPH.'> WHERE {
	?a owl:annotatedSource ?s .
	?a <http://dbpedia.org/meta/pageSource> ?o} LIMIT 30';
$queries [] = new Query($e, $q);

$e = 'gives a sample of meta:sourcePage in META';
$q = 'SELECT * FROM <'.DBM_GRAPH.'> WHERE {
	?a owl:annotatedSource ?source .
	?a <http://dbpedia.org/meta/sourcePage> ?o.
	
	} LIMIT 30';
$queries [] = new Query($e, $q);

$e = 'EMPTY, checks whether there is a class , that has type owl:class and rdfs:Class';
$q = 'SELECT * FROM <'.DB_GRAPH.'> WHERE {
	?c rdf:type owl:Class .
	?c rdf:type rdfs:Class .
	} LIMIT 10';
$queries [] = new Query($e, $q);

$e = 'EMPTY, checks whether there is a class , that has type owl:class and owl:Thing';
$q = 'SELECT *  FROM <'.DB_GRAPH.'> WHERE {
	?c rdf:type owl:Class .
	?c rdf:type owl:Thing .
	} LIMIT 10';
$queries [] = new Query($e, $q);

$e = 'inspects: http://dbpedia.org/ontology/MusicalArtist';
$q = 'SELECT *  FROM <'.DB_GRAPH.'> WHERE {
	<http://dbpedia.org/ontology/MusicalArtist> ?p ?o .
	
	} LIMIT 100';
$queries [] = new Query($e, $q);

$e = 'inspects annotation properties pointing to: http://dbpedia.org/ontology/MusicalArtist
		should be annotetedSource and Target only
';
$q = 'SELECT DISTINCT ?test  FROM <'.DBM_GRAPH.'> WHERE {
	?a ?test <http://dbpedia.org/ontology/MusicalArtist> .
	} LIMIT 10';
$queries [] = new Query($e, $q);

$e = 'inspects if annotatedProperty points to: http://dbpedia.org/ontology/MusicalArtist';
$q = 'SELECT  ?a ?p ?o   FROM <'.DBM_GRAPH.'> WHERE {
	?a <http://www.w3.org/2002/07/owl#annotatedProperty> <http://dbpedia.org/ontology/MusicalArtist> .
	OPTIONAL {?a ?p ?o}
	} LIMIT 10';
$queries [] = new Query($e, $q);

$e = 'inspects all META axioms for: http://dbpedia.org/ontology/MusicalArtist';
$q = 'SELECT ?a ?p ?o  FROM <'.DBM_GRAPH.'> WHERE {
	?a ?test <http://dbpedia.org/ontology/MusicalArtist> .
	OPTIONAL {?a ?p ?o}
	} LIMIT 1000';
$queries [] = new Query($e, $q);


foreach ($queries as $q){
/*
	echo "<h2>$key</h2>";
*/
	$q->echoExp();
	$q->echoQuery();
	$q->executeSparqlQuery();
/*
	echo "<xmp>***************************\n".$sparqlQueryString."</xmp>";
	if($ep==DBPEDIA)$defaultgraphURI='http://dbpedia.org';
	else {$defaultgraphURI='';}
	echo executeSparqlQuery($ep, $defaultgraphURI, $sparqlQueryString);
*/
	}

?>
</body>
</html>

<?php


	

class Query{
	
/*
var $endpointURI;
*/
/*
var $defaultgraphURI;
*/
var $expected;
var $sparqlQueryString;
var $format = 'HTML';

function __construct($exp, $q){
	$this->sparqlQueryString = $q;
	$this->expected = $exp;
	}

public function executeSparqlQuery( ){
    		global $prefix;
    		$url = ENDPOINTURI."";
			$defaultgraphURI="";
			//$defaultgraphURI = (strlen($defaultgraphURI)==0)?"":"&default-graph-uri=".$defaultgraphURI;
			$format="&format=$this->format";
			$url .= urlencode($prefix.$this->sparqlQueryString).$defaultgraphURI.$format;
			
/*
			$printurl = str_replace('sparql','snorql',$url);
*/
			echo "<xmp>".$this->sparqlQueryString."</xmp>";
			echo "<a href='$url' target='blank' >link</a>".LN;
			$c = curl_init();
			
			curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($c, CURLOPT_URL, $url);
			
			//curl_setopt($c, CURLOPT_HTTPHEADER, $headers);
			$now = microtime(true);
			$contents = curl_exec($c);
			//print_r($c);die;
			$now = microtime(true)-$now;
			echo "<xmp>needed $now</xmp>".LN;
			
			
			if($contents === false){
				echo "<xmp>".trim(curl_error($c))."</xmp>".LN;
				
				}
			$contents = str_replace('<br/>',"",$contents);
			echo "".trim($contents)."".LN;
			curl_close($c);
    	}
		
public	function echoQuery(){
			
		}
public	function echoExp(){
			echo "<b>".$this->expected."</b>";
		}
	
	
	}	
	
		
?>
