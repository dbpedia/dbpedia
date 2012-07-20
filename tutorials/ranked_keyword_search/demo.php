<?php 
	define('RDFS_LABEL','<http://www.w3.org/2000/01/rdf-schema#label>');
	define('DBPEDIA','http://dbpedia.org/sparql?query=');
	define('LMDB','http://www.linkedmdb.org/sparql?query=');
	$limit = (isset($_REQUEST['limit']))?$_REQUEST['limit']:20;
	$s = trim($_REQUEST['field']);
	$ep = $_REQUEST['endpoint'];
	$globalfilter =
	"FILTER (!regex(str(?s), '^http://dbpedia.org/resource/Category:')). 
	FILTER (!regex(str(?s), '^http://dbpedia.org/resource/List')).
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
		$endpoints[]=LMDB;
	?>
	<form action="">
		<p>
		
		<br>	
		<h2>Description:</h2>  
		<p>
			In this page you can create SPARQL queries either by typing the key words or by using pre-made examples. The generated SPARQL query will
			be displayed according to different perspectives and the results will be shown in a tabular form where some results are ranked too.
		</p>
		<hr size="2">
			Enter keyword(s): <input type='text' name='field' value='<?=$s?>'> <input type='submit' name='search' value="Submit"><br>
			Limit: 	<input type='text' name='limit' value='<?=$limit?>' size = '4'><br>
			Select different SPARQL endpoint:<br>
			<?php
			$first = true; 
			foreach($endpoints as $one ){
				echo "<input type=\"radio\" name=\"endpoint\" value=\"$one\" ".(($first)?'checked="checked"':'')."  > $one <br>";
				$first = false;
				}
				
		?>
		

		</p>
		//TODO make this automatically generated, use variable $ep
		<?php 
		$keywordexamples = array("Germa", "Germany", "German Bee");
		
		?>
		You can use these examples:<br>
		<a href='demo.php?endpoint=http://dbpedia.org/sparql?query=&field=Germa'>Germa</a><br>
		<a href='demo.php?endpoint=http://dbpedia.org/sparql?query=&field=Germany'>Germany</a><br>
		<a href='demo.php?endpoint=http://dbpedia.org/sparql?query=&field=German Bee'>German Bee</a><br>
		<a href='demo.php?endpoint=http://dbpedia.org/sparql?query=&field=German beer'>German beer</a><br>
		<a href='demo.php?endpoint=http://dbpedia.org/sparql?query=&field=German Beer'>German Beer</a><br>
		<a href='demo.php?endpoint=http://dbpedia.org/sparql?query=&field=Einstein Alber'>Einstein Alber</a><br>
		<a href='demo.php?endpoint=http://dbpedia.org/sparql?query=&field=Albert Einstein Insti'>Albert Einstein Insti</a><br>
		<a href='demo.php?endpoint=http://dbpedia.org/sparql?query=&field=Albert Einstein Institution'>Albert Einstein Institution</a><br>
		<a href='demo.php?endpoint=http://dbpedia.org/sparql?query=&field=Einstein Albert'>Einstein Albert</a><br>
		<a href='demo.php?endpoint=http://dbpedia.org/sparql?query=&field=goethe'>goethe</a><br>
		<a href='demo.php?endpoint=http://dbpedia.org/sparql?query=&field=Johann Goethe'>Johann Goethe</a><br>

	
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

		

			//QueryVertuosoCount
			$queries['single_word_complete_startswith'] = 
			$tmpstr=
"SELECT DISTINCT ?s ?o	WHERE { 
	?s  ".RDFS_LABEL." ?o . 
	?o bif:contains \"$current\".	
	FILTER (regex(str(?o), '^$current')) . 
	$globalfilter 	
}	
Limit 10";
			$queries['single_word_incomplete_startswith'] =
			"SELECT DISTINCT ?s ?o 
			WHERE { ?s  ".RDFS_LABEL." ?o . ?o bif:contains '\"$current*\"'.	FILTER (regex(str(?o), '^$current')). \n".		"$globalfilter }
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

			//QueryVertuoso.php
			$queries['multiple_words_incompletephrase_startswith'] = 
			"SELECT DISTINCT ?s ?o 
			WHERE { 
				?s  ".RDFS_LABEL." ?o . 
				?o bif:contains '$contains1'.
				$or
				$globalfilter 
				}
			Limit 10";


			$queries['multiple_words_complete_startswith'] = 
			"SELECT DISTINCT ?s ?o 
			WHERE { 
				?s  ".RDFS_LABEL." ?o . 
				?o bif:contains '$contains2'.
				$or
				$globalfilter 
				}
			Limit 10";


			$queries['multiple_words_nthwordregex_startswith'] = 
			"SELECT DISTINCT ?s ?o \nWHERE { \n?s  ".RDFS_LABEL."?o .\n?o bif:contains '$contains3'.\n$or	$and $globalfilter \n}\n Limit 10\n";

			//QueryVertuosoCount
			$queries['multiple_words_complete_count_outdeg'] = 
			"SELECT DISTINCT ?s ?o count(?s) as ?count
			WHERE { 
				?s  ?p ?someobj . 
				?s  ".RDFS_LABEL." ?o . 
				?o bif:contains '$contains2'.
				$globalfilter 
				FILTER (!isLiteral(?someobj)).
			}
			ORDER BY DESC(?count)
			Limit $limit";

			$queries['multiple words, exact match, count indegree'] = 
			"SELECT DISTINCT ?s ?o count(?s) as ?count 
			WHERE { 
				?someobj  ?p ?s . 
				?s  ".RDFS_LABEL." ?o . 
				?o bif:contains '$contains2'.
				$globalfilter 
				FILTER (!isLiteral(?someobj)).
			}
			ORDER BY DESC(?count)
			Limit $limit";

		}

		///////////////////////////////////////////////
		$queries['exact']=
		"SELECT DISTINCT ?s 
		WHERE { 
			?s ".RDFS_LABEL." '$full'@en .
			FILTER (!regex(str(?s), '^http://dbpedia.org/resource/Category:')).
			FILTER (!regex(str(?s), '^http://sw.opencyc.org/')). 
		}";
		//$strtmp=str_replace(" W","\nW",$strtmp);
		//$strtmp=str_replace(" F","\nF",$strtmp);
		/*$sttrtmp=preg_replace('!\s+!', ' ', $sttrtmp);
		$sttrtmp=preg_replace("/[[:blank:]]+/"," ", $sttrtmp);
		$queries['exact']=$strtmp+".......";*/
		$queries['test']="SELECT * WHERE {?s ?p ?o} Limit 1";		

		$description_of_keys=array();
		$description_of_keys['multiple words, exact match, count indegree']="Query has multiple keywords and is sorted by in degree of subject. Keywords must be exact match. " ;

		foreach ($queries as $key=>$sparqlQueryString){
			echo "<h2>$key</h2>";
			echo $description_of_keys[$key]."<br>";
			//echo "<xmp>***************************\n".$sparqlQueryString."</xmp>";
			echo "<textarea cols=\"80\" rows=\"15\" readonly=\"yes\" wrap=\"on\"><$sparqlQueryString></textarea> <br>";
		//	echo "<div style=\"border: blue 4px solid; border-bottom: blue 4px solid; border-top-style: ridge;\">$sparqlQueryString</div>";
			
			if($ep==DBPEDIA)$defaultgraphURI='http://dbpedia.org';
			else {$defaultgraphURI='';}
				echo executeSparqlQuery($ep, $defaultgraphURI, $sparqlQueryString); // here the function implements the query then dsiplay the result
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
		//QueryVertuoso.php
		echo "<a href='$url' target='blank' >SPARQL link</a> <br>";

		//QueryVertuosoCount.php
		$printurl = str_replace('sparql','snorql',$url);
			echo "<a href='$printurl' target='blank' >SNORQLlink</a> <br>";

		///////////////////////

		$c = curl_init();
		curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($c, CURLOPT_URL, $url);
		$now = microtime(true);
			$contents = curl_exec($c);
			$now = microtime(true)-$now;
			echo "<xmp>needed $now milliseconds</xmp>";
			curl_close($c);
			
			if($contents === false){
				echo "<xmp>".trim(curl_error($c))."</xmp>";
				
				//$contents = "";
				}
			$contents = str_replace('<br/>',"",$contents);
			return trim($contents);
    	}
		
?>

