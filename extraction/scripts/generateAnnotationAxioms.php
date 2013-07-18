#! /usr/bin/php
<?php 

if(empty($argv[1]) || empty($argv[2])){
	die('$1 = nt file, $2 =  origin URI'."\n");
	}
$ntfile =	$argv[1];
$extractorURI =	'<'.$argv[2].'>';

define("RDF_TYPE", '<http://www.w3.org/1999/02/22-rdf-syntax-ns#type>');
define("OWL_AXIOM", '<http://www.w3.org/2002/07/owl#Axiom>');
define("OWL_SUBJECT", '<http://www.w3.org/2002/07/owl#subject>');
define("OWL_OBJECT", '<http://www.w3.org/2002/07/owl#object>');
define("OWL_PREDICATE", '<http://www.w3.org/2002/07/owl#predicate>');

define("DC_MODIFIED", '<http://purl.org/dc/terms/modified>');
define("ORIGIN", '<http://dbpedia.org/property/origin>');

$modified = '"2008-10-08T00:00:00+00:00"^^<http://www.w3.org/2001/XMLSchema#dateTime>';

$file = fopen($ntfile, "r");

$i=0;
while(!feof($file))
 {
  $i++;
  $line =  fgets($file);
  $s = trim(substr($line, 0, strpos($line, '>')+1));
  $rest = substr($line, strpos($line, '>')+1);
  $p = trim(substr($rest, 0, strpos($rest, '>')+1));
  $rest = trim(substr($rest, strpos($rest, '>')+1));
  $o = trim(substr($rest, 0, strrpos($rest, '.')));
/*
  if(strpos($object,'"')===false){
	  $object = remove($object);
	  }
*/
  
  $bnode = '_:a'.$i;
  echo trip($bnode, RDF_TYPE , OWL_AXIOM);
  echo trip($bnode, OWL_SUBJECT , $s );
  echo trip($bnode, OWL_PREDICATE, $p);
  echo trip($bnode, OWL_OBJECT, $o );
  echo trip($bnode, DC_MODIFIED,$modified );
  echo trip($bnode, ORIGIN, $extractorURI );
  
  //echo $subject."|".$predicate."|".$object."\n";
  
}


fclose($file);

function trip($s, $p, $o){
		return $s." ".$p." ".$o." . \n";
	}


  function remove($str){
	  	return   	str_replace("<","",
  		  			str_replace(">","", $str));
	  }
 
