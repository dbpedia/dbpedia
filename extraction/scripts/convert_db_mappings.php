<?php
//define('POSTURL', 'http://meta.wikimedia.org/w/api.php');
define('POSTURL', 'http://localhost/wiki/api.php');
define('PREFIX', 'DBpedia/ontology/');
//define('PREFIX', '');
// =============== WARNING ===============
// This file uses the global mysql connection.
// Do not use this code with other code that uses mysql.
// =============== WARNING ===============

ini_set('memory_limit', '1512M');
/**
 * Script for converting the mapping database to wiki templates.
 */

include('../databaseconfig.php');
require_once('wikibot.classes.php');
// information about classes and properties
$classFile = "classes/classes";
$end = ".html";
$propertyFile = "properties.txt";
$token = "";
$w = new wikipediaapi();
echo $w->login($luser, $lpass);
$page = PREFIX.'Actor';
$data = "test";
echo $w->edit ($page,$data,$summary = 'ddd');
die;

//$token = login($mwuser, $mwpass);
/*
print_r($token);
die;
*/
// actual mappings
$mappingsFile = "mappings.txt";


//$baseURL = 'http://meta.wikimedia.org/wiki/DBpedia/ontology/';
$dbname = 'dbpedia_extraction_en';
//$baseURL = 'http://meta.wikimedia.org/w/index.php?title=DBpedia/ontology/';
$link = mysql_connect($host , $user, $password) or die("couldnt connect");
mysql_select_db($dbname) or die(" no db:  ".$dbname);

// query information about classes (name, label, super class)
$query = 'SELECT t1.name, t1.label, t2.name AS superClass FROM class t1 LEFT JOIN class t2 ON t1.parent_id=t2.id ORDER BY t1.name';
$result = mysql_query($query) or die("query failed: " . mysql_error());

$classStr = '';
$x = 0;
while($var = mysql_fetch_array($result)) {
	if($x%10==0){
		// write information to file
		$handle = fopen($classFile.$x.$end, "w");
		fwrite($handle, $classStr);
		fclose($handle);
		$classStr ="";
		}
	
	if(!empty($var['superClass'])) {
		$ins = " | rdfs:subClassOf = {$var['superClass']}";
	}else{
		$ins = " | rdfs:subClassOf = ";
		}
		$title = $var['name'];
	//$url = PREFIX.$title."&action=edit";
/*
	$classStr .= "Please store the following in <a href='$url' target='_blank'>$url</a><br>
<textarea name=\"aaa\" cols=\"50\" rows=\"14\">";
*/
$label = $var['label'];
$label = strtolower($label[0]).substr($label,1);
$text="{{DBpedia Class 
 | rdfs:label = $label
 | rdfs:label@en = $label
 | rdfs:label@de = 
 | rdfs:label@fr = 
 | rdfs:comment = 
 | rdfs:comment@en =
 | rdfs:comment@de = 
 | rdfs:comment@fr =
 | owl:equivalentClass = 
 | owl:disjointWith = 
 | rdfs:seeAlso = 
$ins
}}";

$rand = rand(1,7);
$rand = 2;
/*
$classStr .= $text."</textarea><br><br>
";
*/	$current = 110;
	echo "Mammal";
	if($x <$current){
		$x++;
		continue;
	}
		
		
	if($x >= ($current+21)){
		echo "sleeping 600";
		sleep(600);
		echo "finished";
		die;
		
		}
	echo "********************************************\n";
	echo "sleeping $rand, currently at $title with nr $x\n";
	sleep($rand);
	
	$ret = edit($token, $title, $text);
	print_r($ret);
	echo "\n";
	$x++;
	//die;
	
	
}
$handle = fopen($classFile."last".$end, "w");
fwrite($handle, $classStr);
fclose($handle);

//echo $classStr;

mysql_close();

echo "Finished succesfully.\n";

function edit($token="", $title, $text){
		$add = "";
       	$enc = urlencode('+\\');
		if(!empty($token)){
		 foreach ( $token as $key=>$value){
			 if($key=="lgtoken"){
				 $value.=$enc;
				 }
			 $add .= "&".$key."=$value";
			 }
		}
		 $add .="&token=".$enc;
		 //define('POSTVARS', 'listID=29&request=suba&SubscribeSubmit=Subscribe&EmailAddress=');  // POST VARIABLES TO BE SENT
		 $postvars = "title=".PREFIX."$title&text=$text".$add;
		 $url = POSTURL."?action=edit&format=json";
		 echo  $url."\n";
		// echo $postvars."\n";
	// echo $postvars."\n";
		return post( $url, $postvars);
	
	}
function login($user, $pass){
	
		 //define('POSTVARS', 'listID=29&request=suba&SubscribeSubmit=Subscribe&EmailAddress=');  // POST VARIABLES TO BE SENT
		 $postvars = "lgname=$user&lgpassword=$pass";
		 echo $postvars."\n";
		$ret = post(POSTURL."?action=login&format=json", $postvars);
		$arr = json_decode($ret,true);
/*
		print_r($arr);
		die;
*/
		$token = $arr['login'];
		//$token = "&token=".urlencode('+\\');
		//$token = "&token=".$arr['login']['lgtoken'].'+\\';
		return $token;
	
	}
	

	
function post($url, $postvars){
		 $ch = curl_init($url);
		 curl_setopt($ch, CURLOPT_POST      ,1);
		 curl_setopt($ch, CURLOPT_POSTFIELDS    ,$postvars);
		 curl_setopt($ch, CURLOPT_FOLLOWLOCATION  ,1);
		 curl_setopt($ch, CURLOPT_HEADER      ,0);  // DO NOT RETURN HTTP HEADERS
		 curl_setopt($ch, CURLOPT_RETURNTRANSFER  ,1);  // RETURN THE CONTENTS OF THE CALL
		 return curl_exec($ch);
		
	
	}
