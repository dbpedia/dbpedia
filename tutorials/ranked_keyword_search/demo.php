<!--
Copyright (C) 2012 Mofeed Hassan (mounir@informatik.uni-leipzig.de)
 Everyone is permitted to copy and distribute verbatim copies
 of this license document, but changing it is not allowed.
-->

<?php 
    //DEfining some fixed URIs
	define('RDFS_LABEL','<http://www.w3.org/2000/01/rdf-schema#label>');
	define('DBPEDIA','http://dbpedia.org/sparql?query=');
	define('LMDB','http://www.linkedmdb.org/sparql?query=');
	//Assigning limit value or default
	$limit = (isset($_REQUEST['limit']))?$_REQUEST['limit']:20;
	//Retrieve the keywords
	$s = trim($_REQUEST['field']);
	//Retrieve the selected endpoint
	$ep = $_REQUEST['lstendpoint'];
	//Specifying some filtering criteria
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
	// Creating array of endpoints and assigning values
		$endpoints = array();
		$endpoints[]=DBPEDIA;
		$endpoints[]=LMDB;
	?>
	<form action="" >
		<!-- Writing description of page functionality-->
		<p>
		<h2><u>Description:</u></h2>  
		<p>
			
			In this page you can create SPARQL queries either by typing the key words or by using pre-made examples. The generated SPARQL query will
			be displayed according to different perspectives and the results will be shown in a tabular form where some results are ranked too.
			
		</p>
		
		   <hr size="2">
			Enter keyword(s): <input type='text' name='field' value='<?=$s?>'> <input type='submit' name='search' value="Submit"><br>
			Limit: 	<input type='text' name='limit' value='<?=$limit?>' size = '4'><br>
			
				<fieldset>
				<legend>Select different SPARQL endpoint:<br></legend>
				<?php
				//$first is a flag which is used to set the first Option in the Radiobutton list selected in case that no previous selection was made
				// which is the case for the first time loading the page
				$first = false;  
				$selectedendpoint =$_REQUEST['lstendpoint'];// get the vaue of the selected radiobutton into variable "selectedendpoint"
				$selected_button = $_REQUEST['lstendpoint'];// the same as previous statement but for variable "selected_button"
				if($selected_button ==null) // there is no selected endpoint so set the first one to be default selection
				   $first=true;
				//According to number of defined endpoints in the array create radio buttons
				foreach($endpoints as $one ){
					echo "<input type=\"radio\" name=\"lstendpoint\" value=\"$one\" ".(($first||$selected_button==$one)?'checked="checked"':'')."  > $one <br>";
				$first=false;//return the flag false again closing the way for setting the next radiobutons
				
					}
			   ?>
				</fieldset>
				<?php
				//This flag aywa to check every time the page is refreshed if the Display Query only checkbox was 
				//set or not to keep its status
				$aywa=$_GET['dspQuery'];
				
				echo "<input type=\"checkbox\" name=\"dspQuery\" value=\"Display\" ".(($aywa!=null)?'checked="checked"':'')." /> Display queries only(not valid for examples) <br />"
				?>
		
		</p>
		<!--Here list of examples to be used-->
		You can use these examples:<br>
		
		<?php 
		//Display only checbox: setting flag to false and checking the value of the checkbox, so if it is checked 
		//the flag will be set to true; that will lead to just displaying the query only not to be exectuted
		    $onlydisplayflag=false;
			if(isset($_REQUEST["dspQuery"]) &&   $_REQUEST['dspQuery'] == "Display")
				$onlydisplayflag=true;
		//Defining a list of search keywords examples
			$keywordexamples = array("Germa", "Germany", "German Bee", "German beer", "German Beer", 
									  "Einstein Alber", "Albert Einstein Insti", "Albert Einstein Institution", "Einstein Albert",
									  "goethe", "Johann Goethe" );
		//Loop for displaying the list of examples							  
			foreach($keywordexamples as $keyword)
			 {
				 echo "<a href='demo.php?lstendpoint=http://dbpedia.org/sparql?query=&field=".$keyword."'>".$keyword."</a><br>";
			 }		
		?>
	</form>
	<?php 
	    //Defining array of different queries with multiple types of keywords and filtering
	    //These queries will be formed based on the entered or selected(examples) keywords
		$queries = array();
		$s = trim($s);
		$full = $s;
		//code for parsing the entered text with keywords separation
		$swords=array();
        if(empty($s)){//empty textbox
			die('Enter search word');
		} else if(strpos($s,' ')!==false){
			while (($pos = strpos($s,' '))!==false){
				$swords[] = trim(substr($s,0,$pos));
				$s = substr($s,$pos+1);
			}
			$swords[] = trim($s);
			}else{
				$swords[] = $s;
			}

		if(count($swords)==0){//no keyword entered
			 die('enter search word');
		}else if(count($swords)==1){//single keyword entered
			$current = $swords[0]; //current is the first keyword (only one in this case)
			//Define two types of queries for such single word
			$queries['single word, complete, in the start  '] = 
			$tmpstr=
"SELECT DISTINCT ?s ?o	WHERE { 
	?s  ".RDFS_LABEL." ?o . 
	?o bif:contains \"$current\".	
	FILTER (regex(str(?o), '^$current')) . 
	$globalfilter 	
}	
Limit 10";
			$queries['single keyword, incomplete word, in the start  '] =
"SELECT DISTINCT ?s ?o	WHERE { 
	?s  ".RDFS_LABEL." ?o . 
	?o bif:contains '\"$current*\"'.
	FILTER (regex(str(?o), '^$current')). \n".
	"$globalfilter 
}
Limit 10";
			

		}else{ //more than keyword
		//combining the filters
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

			//Forming different types of queries for multiple keywords
			$queries['multiple words, incomplete phrase,in start of phrase'] = 
"SELECT DISTINCT ?s ?o WHERE { 
	?s  ".RDFS_LABEL." ?o . 
	?o bif:contains '$contains1'.
	$or
	$globalfilter 
 }
Limit 10";


			$queries['multiple words, complete phrase,in start of phrase'] = 
"SELECT DISTINCT ?s ?o WHERE { 
	?s  ".RDFS_LABEL." ?o . 
	?o bif:contains '$contains2'.
	$or
	$globalfilter 
	}
Limit 10";

			$queries['multiple words,in start of phrase,with n word regex'] = 
"SELECT DISTINCT ?s ?o \nWHERE {
	?s  ".RDFS_LABEL."?o .
	?o bif:contains '$contains3'.
	$or	
	$and 
	$globalfilter 
	}
Limit 10";
			$queries['multiple complete keywords,outdeg counted'] = 
"SELECT DISTINCT ?s ?o count(?s) as ?count WHERE { 
	?s  ?p ?someobj . 
	?s  ".RDFS_LABEL." ?o . 
	?o bif:contains '$contains2'.
	$globalfilter 
	FILTER (!isLiteral(?someobj)).
	}
ORDER BY DESC(?count)
Limit $limit";

			$queries['multiple words, exact match, count indegree'] = 
"SELECT DISTINCT ?s ?o count(?s) as ?count WHERE { 
	?someobj  ?p ?s . 
	?s  ".RDFS_LABEL." ?o . 
	?o bif:contains '$contains2'.
	$globalfilter 
	FILTER (!isLiteral(?someobj)).
	}
ORDER BY DESC(?count)
Limit $limit";

		}
		$queries['exact']=
"SELECT DISTINCT ?s WHERE { 
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
		//Array of texts that will desccribe each type of the queries
		$description_of_keys=array();
		$description_of_keys['multiple words, exact match, count indegree']="Query has multiple keywords and is sorted by in degree of subject. Keywords must be exact match. " ;
        $description_of_keys['single word, complete, in the start  '] = "Query has single keyword. The returned phrases start with it.";
		$description_of_keys['single keyword, incomplete word, in the start  ']= "Query has single keyword. that represents the head of a word which The returned phrases start with it.";
		$description_of_keys['multiple words, incomplete phrase,in start of phrase'] ="Select distinct values of subjects and objects, where subjects have labels thta start with the searched keywords. The subject is not from:
http://dbpedia.org/resource/Category
http://dbpedia.org/resource/List
http://sw.opencyc.org/ 
which are in Englich lanuage."; "Query has multiple keywords. The returned phrases are not complete and start with the specified keywords. ";
		$description_of_keys['multiple words, complete phrase,in start of phrase'] ="Select distinct values of subjects and objects, where subjects have labels that start with the exact searched keywords. The subject is not from:
http://dbpedia.org/resource/Category
http://dbpedia.org/resource/List
http://sw.opencyc.org/ 
which are in Englich lanuage.";// "Query has multiple keywords. The returned phrases are complete and start with the specified keywords. ";
		$description_of_keys['multiple words,in start of phrase,with n word regex'] = "Select distinct values of subjects and objects, where subjects have labels that start with or contain the searched keywords. The subject is not from:
http://dbpedia.org/resource/Category
http://dbpedia.org/resource/List
http://sw.opencyc.org/ 
which are in Englich lanuage.
";//"Query has multiple keywords."; 
		$description_of_keys['multiple complete keywords,outdeg counted'] ="Select distinct values of subjects and objects where objects are URIs and msatche the searched keywords and in addition counting the number of retrieved triples for each subject. The subject is not from:
http://dbpedia.org/resource/Category
http://dbpedia.org/resource/List
http://sw.opencyc.org/ 
which are in Englich. Sorted decendingly by outdgree"; "Query  has multiple keywords and is sorted by out degree of subject. keywords must be exact match. ";
		//$description_of_keys['multiple words, exact match, count indegree'] = 
		$description_of_keys['exact']="Query matches the exact keyword";
		$description_of_keys['test']= "This query for testing that the ENDPOINT works well. ";
		$separator="-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------";
//Loop for displaying each query's short title, query description, the quesry itself inside a text area
// and based on the flag value the function for exectuting the query and displaying the restult will be called		
		foreach ($queries as $key=>$sparqlQueryString){
			echo "<h2>".$key."</h2>";
			echo $description_of_keys[$key]."<br>";
			echo "<textarea cols=\"90\" rows=\"15\" readonly=\"yes\" wrap=\"on\">$sparqlQueryString</textarea> <br>";
			if($ep==DBPEDIA)$defaultgraphURI='http://dbpedia.org';
			else {$defaultgraphURI='';}
			if(!$onlydisplayflag)
				{

					echo executeSparqlQuery($ep, $defaultgraphURI, $sparqlQueryString); // here the function implements the query then dsiplay the result
				}
			}
	?>	
</body>
</html>
<!------------------------------------------------------------------------------------------------------------------------------------------------------------------------->
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

