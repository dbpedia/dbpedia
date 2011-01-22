<?php

// =============== WARNING ===============
// This file uses the global mysql connection.
// Do not use this code with other code that uses mysql.
// =============== WARNING ===============


/**
* Export Wikipedia template mappings from database to GraphViz file.
*
* @author	Anja Jentzsch <mail@anjajentzsch.de>
*/

require_once("../extraction/databaseconfig.php");
$db = "dbpedia_extraction";
$link = mysql_connect($host, $user, $password) or die ("No connection to database possible.");
mysql_select_db($db, $link);

// Get and print classes

$query1 = "SELECT name, parent_id, label FROM class ";
$result1 = mysql_query($query1) or die("Anfrage fehlgeschlagen: " . mysql_error());

$output= "digraph G { \n\t rankdir=RL; \n\t remincross=true;\n";

while ($line=mysql_fetch_array($result1, MYSQL_ASSOC) ) {
      $class=$line["label"];
      $subclassnr=$line["parent_id"];



      if ($subclassnr!=""){
      $ssubname="SELECT name, label from class WHERE id=$subclassnr";


      $subresult=mysql_query($ssubname);

      $sub= mysql_fetch_array($subresult, MYSQL_ASSOC);
      $subclass=$sub["label"];

      $output .= ' "'.$class.'" -> "'.$subclass.'";';
     }
}

$output .= "\n}";

//echo $output;

$fileName = 'dbpedia.owl.viz';
$uploadfile = $fileName;
$handle = fopen($uploadfile, 'w+');
fwrite($handle, $output);
fclose($handle);

