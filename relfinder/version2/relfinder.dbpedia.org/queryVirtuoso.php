<?php 
define('RDFS_LABEL','<http://www.w3.org/2000/01/rdf-schema#label>');
define('DBPEDIA','http://dbpedia.org/sparql?query=');
define('LMDB','http://www.linkedmdb.org/sparql?query=');
$s = trim($_REQUEST['field']);
$ep = $_REQUEST['endpoint'];
$globalfilter =
"FILTER (!regex(str(?s), '^http://dbpedia.org/resource/Category:')). 
FILTER (!regex(str(?s), '^http://sw.opencyc.org/')). 
FILTER (lang(?o) = 'en'). ";
?>
<html>
<head>
</head>
<body>
<?php
$endpoints = array();
$endpoints[]=DBPEDIA;
//$endpoints[]=LMDB;
//echo "<xmp><a href='queryVirtuoso.php?endpoint=$ep&field=$s'>$s</a><br></xmp>";

?>
<form action="">
  <p>
	<?php
		$first = true; 
		foreach($endpoints as $one ){
		echo "<input type=\"radio\" name=\"endpoint\" value=\"$one\" ".(($first)?'checked="checked"':'')."  > $one <br>";
		$first = false;

		}
     ?>
   
  </p>

<a href='queryVirtuoso.php?endpoint=http://dbpedia.org/sparql?query=&field=Germa'>Germa</a><br>
<a href='queryVirtuoso.php?endpoint=http://dbpedia.org/sparql?query=&field=Germany'>Germany</a><br>
<a href='queryVirtuoso.php?endpoint=http://dbpedia.org/sparql?query=&field=German Bee'>German Bee</a><br>
<a href='queryVirtuoso.php?endpoint=http://dbpedia.org/sparql?query=&field=German beer'>German beer</a><br>
<a href='queryVirtuoso.php?endpoint=http://dbpedia.org/sparql?query=&field=German Beer'>German Beer</a><br>
<a href='queryVirtuoso.php?endpoint=http://dbpedia.org/sparql?query=&field=Einstein Alber'>Einstein Alber</a><br>
<a href='queryVirtuoso.php?endpoint=http://dbpedia.org/sparql?query=&field=Albert Einstein Insti'>Albert Einstein Insti</a><br>
<a href='queryVirtuoso.php?endpoint=http://dbpedia.org/sparql?query=&field=Albert Einstein Institution'>Albert Einstein Institution</a><br>

<input type='text' name='field' value='<?=$s?>'><br>
<input type='submit' name='search'>

</form>

<?php 
$queries = array();

$s = trim($s);
$full = $s;
$swords=array();

if(empty($s)){
	die('enter search word');
} else if(strpos($s,' ')!==false){
	
	while (($pos = strpos($s,' '))!==false){
		$swords[] = trim(substr($s,0,$pos));
		$s = substr($s,$pos+1);
		}
	$swords[] = trim($s);
}else{
	$swords[] = $s;
	}



if(count($swords)==0){
	die('enter search word');
}else if(count($swords)==1){
$current = $swords[0];
$queries['single_word_complete_startswith'] = 
"SELECT DISTINCT ?s ?o WHERE { 
?s  ".RDFS_LABEL." ?o .
?o bif:contains \"$current\".
FILTER (regex(str(?o), '^$current')). \n".
"$globalfilter }
Limit 10";

$queries['single_word_incomplete_startswith'] = 
"SELECT DISTINCT ?s ?o WHERE { 
?s  ".RDFS_LABEL." ?o . 
?o bif:contains '\"$current*\"'.
FILTER (regex(str(?o), '^$current')). \n".
"$globalfilter }
Limit 10";
}else{
	//echo "<xmp>";
	$contains1 ="";
	$contains2 ="";
	$contains3 ="";
	$or ='FILTER ( ';
	$and ='FILTER ( ';
	for($x=0;$x<count($swords);$x++){
		$current = $swords[$x];
		$toAdd3 = "";

		if($x<count($swords)-2){
			$toAdd3 = $current.' and ';
			
		}else if($x == count($swords)-2){
			$toAdd3 = $current;
		}

		
		if($x<count($swords)-1){
			$toAdd1 = $current.' and ';
			$toAdd2 = $current.' and ';
			
		}else {
			$toAdd1 = '"'.$current.'*"';
			$toAdd2 = $current;
		}
		$contains1 .= $toAdd1;
		$contains2 .= $toAdd2;
		$contains3 .= $toAdd3;
		echo $contains."\n";
	}
	
	for($x=0;$x<count($swords);$x++){
		$current = $swords[$x];
		
		$or .= "(regex(str(?o), '^$current', 'i')) ";
		$and .= "(regex(str(?o), '$current', 'i')) ";
		//$toAdd = $current;
		if($x<count($swords)-1){
			$or .= ' || ';
			$and .= ' && ';
			
		}else {
			$or .=' ). ';
			$and .=' ). ';
			
		}
		
	}
	//$contains.="\n";
	
/*
		$or .= "(regex(str(?o), '^$swords[$x]', 'i')) ";
		$and .= "(regex(str(?o), '$swords[$x]', 'i')) ";
		
			$or .= '||';
			$and .= ' && ';
			
*/
$queries['multiple_words_incompletephrase_startswith'] = 
"SELECT DISTINCT ?s ?o WHERE { 
?s  ".RDFS_LABEL." ?o . 
?o bif:contains '$contains1'.
$or
$globalfilter }
Limit 10";
	
	
$queries['multiple_words_complete_startswith'] = 
"SELECT DISTINCT ?s ?o WHERE { 
?s  ".RDFS_LABEL." ?o . 
?o bif:contains '$contains2'.
$or
$globalfilter }
Limit 10";
	
	
$queries['multiple_words_nthwordregex_startswith'] = 
"SELECT DISTINCT ?s ?o WHERE { 
?s  ".RDFS_LABEL." ?o . 
?o bif:contains '$contains3'.
$or
$and
$globalfilter }
Limit 10";
	}

/*
	$or ='FILTER ( ';
	$and ='FILTER ( ';
	for($x=0;$x<count($swords);$x++){
		$or .= "(regex(str(?o), '^$swords[$x]', 'i')) ";
		$and .= "(regex(str(?o), '$swords[$x]', 'i')) ";
		if($x<count($swords)-1){
			$or .= '||';
			$and .= ' && ';
			}
	if($x == 0)$s1 = $swords[$x];
	if($x == 1)$s2 = $swords[$x];
	if($x == 2)$s3 = $swords[$x];
		
	}
	$or .=' ). ';
	$and .=' ). ';
*/
	
	
/*
$queries['mixVirt'] = "SELECT DISTINCT(?s) ?o WHERE { 
	?s  ".RDFS_LABEL." ?o .
	?o bif:contains \"$swords[0]\".
	$or 
	$and 
	$globalfilter
} 
Limit 10";

$queries['mixOther'] = "SELECT DISTINCT(?s) ?o WHERE { 
	?s  ".RDFS_LABEL." ?o .
	$or 
	$and 
	$globalfilter
} 
Limit 10";
	
	//split
}else{
	$s1 = $full;		
		}

$queries['exact'] = 


$queries['regex'] = "SELECT ?s ?o
WHERE { 
	?s ".RDFS_LABEL." ?o.
	Filter (regex(str(?o), '$full', 'i')).
	Filter (!regex(str(?s), '^http://dbpedia.org/resource/Category:')).
	FILTER (!regex(str(?s), '^http://sw.opencyc.org/')). 
} LIMIT 10";


$queries ['LabelStartsWithWord']= "SELECT DISTINCT ?s ?o WHERE { 
?s  ".RDFS_LABEL." ?o .
?o bif:contains \"$s1\".
Filter (regex(str(?o), '^$s1')). \n".
((!empty($s2))?"Filter (regex(str(?o), '$s2')).\n":"").
((!empty($s3))?"Filter (regex(str(?o), '$s3')).\n":"").
"$globalfilter
}
Limit 10";

$queries['LabelStartswithCharacters']= "SELECT DISTINCT ?s ?o WHERE { 
?s  ".RDFS_LABEL." ?o .
?o bif:contains '\"$s1*\"'.
Filter (regex(str(?o), '^$s1')). \n".
((!empty($s2))?"Filter (regex(str(?o), '$s2')).\n":"").
((!empty($s3))?"Filter (regex(str(?o), '$s3')).\n":"").
"$globalfilter
} 
Limit 10";

*/




/*
$queries []= "SELECT DISTINCT ?s WHERE  {
?s  ".RDFS_LABEL." ?o .
Filter (regex(str(?o), '^$s1')).
} Limit 10";
*/
//echo "<xmp>";

$queries['exact']="SELECT DISTINCT ?s 
WHERE { 
	?s ".RDFS_LABEL." '$full'@en .
	FILTER (!regex(str(?s), '^http://dbpedia.org/resource/Category:')).
	FILTER (!regex(str(?s), '^http://sw.opencyc.org/')). 
}";

$queries['test']="SELECT * WHERE {?s ?p ?o} Limit 1";
/*
if(trim($s)==='')die ('enter a search word');
*/

foreach ($queries as $key=>$sparqlQueryString){
	echo "<h2>$key</h2>";
	echo "<xmp>***************************\n".$sparqlQueryString."</xmp>";
	if($ep==DBPEDIA)$defaultgraphURI='http://dbpedia.org';
	else {$defaultgraphURI='';}
	echo executeSparqlQuery($ep, $defaultgraphURI, $sparqlQueryString);
	}

?>
</body>
</html>

<?php

function executeSparqlQuery($endpointURI, $defaultgraphURI, $sparqlQueryString){
    		
    		$url = $endpointURI."";
			
			//echo $query."\n";
			$defaultgraphURI = (strlen($defaultgraphURI)==0)?"":"&default-graph-uri=".$defaultgraphURI;
			$format="&format=HTML";
			$url .= urlencode($sparqlQueryString).$defaultgraphURI.$format;
			//return $url;
			echo "<a href='$url' target='blank' >link</a>\n";
/*
			echo "<xmp>".$url; die;
*/
			$c = curl_init();
			//$headers = array("Accept: application/sparql-results+xml");
			
			//$headers = array("Content-Type: application/sparql-results+json");
			//$headers = array("Content-Type: rdf");
			
			/*Accept: application/xml
			text/html
				application/sparql-results+json
application/javascript
XML 		application/sparql-results+xml
TURTLE 		text/rdf+n3
			*/
			curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($c, CURLOPT_URL, $url);
			//curl_setopt($c, CURLOPT_HTTPHEADER, $headers);
			$now = microtime(true);
			$contents = curl_exec($c);
			$now = microtime(true)-$now;
			echo "<xmp>needed $now</xmp>";
			curl_close($c);
			
			if($contents === false){
				echo "<xmp>".trim(curl_error($c))."</xmp>";
				
				//$contents = "";
				}
			$contents = str_replace('<br/>',"",$contents);
			return trim($contents);
    	}
		
?>
