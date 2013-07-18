<?

require_once('RelationFinder.php');


$r = new RelationFinder();
$object1 = "db:Angela_Merkel";
$object2 = "db:Joachim_Sauer";
$object2 = "db:Hillary_Rodham_Clinton";
//$object1 = "a";
//$object2 = "b";
$maxDistance = 3;
$limit = 10;
$ignoredObjects;
$ignoredProperties = array(
	'http://www.w3.org/1999/02/22-rdf-syntax-ns#type', 
	'http://www.w3.org/2004/02/skos/core#subject',
	'http://dbpedia.org/property/wikiPageUsesTemplate',
	'http://dbpedia.org/property/wordnet_type'
	
	);
$avoidCycles = 2;

$arr = $r->getQueries($object1, $object2, $maxDistance, $limit, $ignoredObjects, $ignoredProperties, $avoidCycles);
//print_r($arr);

foreach ($arr as $distance){
	foreach ($distance as $query){
		$now = microtime(true);
		echo "<xmp>".$query."</xmp>";
		echo $r->executeSparqlQuery($query, "HTML");
		echo "<br>needed ".(microtime(true)-$now)." seconds<br>";
	}
	}

