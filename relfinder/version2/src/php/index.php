<html>
<body style="font-size:10pt;" >
<a href = "index.php?first=Angela_Merkel&second=Hillary_Rodham_Clinton"> Angie und Hillary</a><br>
<a href = "index.php?first=Angela_Merkel&second=Joachim_Sauer"> Angie und ihr Mann</a><br>
<a href = "index.php?first=Angela_Merkel&second=Dagmar_Krause"> Angie und Dagmar_Krause</a><br>
<form action = "index.php">
first: <input type="text" width ="30" name = "first" value = "Leipzig"><br>
second: <input type="text" width ="30" name = "second" value = "Dresden"><br>
(prefix http://dbpedia.org/resource/ will be added automatically)<br>
<input type="submit">
</form>

<?

if(isset($_REQUEST['first']) && isset($_REQUEST['second'])) {
	
	include('RelationFinder.php');
	$first = 'http://dbpedia.org/resource/'.$_REQUEST['first'];
	$second = 'http://dbpedia.org/resource/'.$_REQUEST['second'];

	$rf = new RelationFinder();

	$maxDistance = 4;
	// get all queries we are interested in
	$queries = $rf->getQueries($first, $second, $maxDistance, 10, array(), array('http://www.w3.org/1999/02/22-rdf-syntax-ns#type','http://www.w3.org/2004/02/skos/core#subject'), true);
	// execute queries one by one
	for($distance = 1; $distance <= $maxDistance; $distance++) {
		echo '<b>Executing queries for distance '.$distance.'</b><br />';
		foreach($queries[$distance] as $query) {
			echo 'Running following query:<br /><pre>'.htmlentities($query).'</pre><br/>';
			$startTime = microtime(true);
			$table = $rf->executeSparqlQuery($query, "HTML");
			$runTime = microtime(true) - $startTime;
			echo $table.'<br />';
			echo 'runtime: '.$runTime.' seconds<br /><br />';
		}
	}

}

?>

</body>
</html>
