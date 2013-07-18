<?php

// =============== WARNING ===============
// This file uses the global mysql connection.
// Do not use this code with other code that uses mysql.
// =============== WARNING ===============

/****
 * Diese Funktionen werden sowohl von den AJAX Funktionen (ajax.php), als auch von den Hauptfunktionen (index.php) benutzt
 */

/***
 * Sucht alle verbundenen Ressourcen bis zur Ursprungsressource in der Cluster Tabelle
 */
function searchClusterStart($resource) {
	mysql_connect($GLOBALS['host'],$GLOBALS['user'],$GLOBALS['password']);
	mysql_select_db($GLOBALS['db']);
	$foundstart=false;
	$connected=array();
	array_push($connected,$resource);
	do {
		$res=mysql_query("SELECT referenced_by_object FROM ".$GLOBALS['clusterTableName']." WHERE object='".$resource."'");
		$row=mysql_fetch_array($res);
		if ($row[0]=='start') {
			$foundstart=true;
			array_push($connected,$row[0]);
		}
		else {
			$resource=$row[0];
			array_push($connected,$row[0]);
		}
	}while($foundstart==false);
	return $connected;
}
/***
 * Sucht zu den uebergebenen Werten (Ressourcen, Ergebnisanzahl, max. Tiefe, Ignorierte Objekte/Praedikate), ob diese Anfrage bereits abgespeichert ist
 */
function isSaved($first,$second,$limit,$maxdepth,$depth,$ignoredObjects,$ignoredPredicates) {
	include("queries.inc.php");
	$alreadysaved=false;
	for ($i=0;$i<count($queries);$i++) {
		if (($queries[$i]['firstObject']==$first||$queries[$i]['secondObject']==$first)&&($queries[$i]['firstObject']==$second||$queries[$i]['secondObject']==$second)&&($queries[$i]['limit']==$limit)&&($queries[$i]['maxdepth']==$maxdepth)&&count(array_diff($ignoredObjects,$queries[$i]['ignoredObjects']))==0&&count(array_diff($ignoredPredicates,$queries[$i]['ignoredPredicates']))==0) {
			
				$alreadysaved=$i;
				return $alreadysaved;	
								
		}
	}
	return $alreadysaved;
}
/***
 * Sucht das Label aus der  Statementstabelle fuer uebergebene Ressource, falls nicht vorhanden => BaseURI abschneiden und URL Escapes entfernen
 */
function getLabel($x) {
	mysql_connect($GLOBALS['host'],$GLOBALS['user'],$GLOBALS['password']);
	mysql_select_db($GLOBALS['db']);
	$labres=mysql_query("SELECT object FROM ".$GLOBALS['statementsTableName']." WHERE predicate='rdfs:label' AND subject='".$x."'");
	if (mysql_num_rows($labres)>0) {
		$labrow=mysql_fetch_array($labres);
		return $labrow[0];
	} 
	else
		return str_replace("_"," ",urldecode(cutBaseUri($x,'wikiBase')));
}
/***
 * Schneidet die konfigurierte BaseURI (Predicate oder Ressource) vom �bergebenen String ab
 */
function cutBaseUri($x,$uri) {
	if ($uri=='wikiBase')
		return substr($x,-(strlen($x)-strlen($GLOBALS['wikipediaBase'])));
	else
		return substr($x,-(strlen($x)-strlen($GLOBALS['propertyBase'])));
}


