<?php

// =============== WARNING ===============
// This file uses the global mysql connection.
// Do not use this code with other code that uses mysql.
// =============== WARNING ===============


/***
 * Cluster Algorithmus:
 * Es wird eine Resource $r aus der Statementscopy Tabelle ausgew�hlt.
 * $r wird in die Clustertabelle geschrieben
 * Danach werden alle mit $r verbundenen Resourcen gesucht
 * Diese werden in der Clustertabelle abgelegt und gleichzeitig aus der Statementstabelle gel�scht
 * Au�erdem werden sie zur weiteren Abarbeitung in einer Warteliste abgespeichert.
 * 
 * Danach wird die n�chste Resource vom Anfang der Warteliste ausgew�hlt und wieder nach verbundenen Resourcen gesucht, 
 * bis die Warteliste leer ist.
 * Dann wird (falls vorhanden) die n�chste Resource $r aus der (kopierten)Statementstabelle ausgew�hlt.
 */
error_reporting(E_ALL & ~E_NOTICE);

include('cluster_config.inc.php');
include('cluster_prepareTableFunctions.php');
$mysql2=mysql_connect($host,$user,$password);
mysql_select_db($db,$mysql2);

if ($GLOBALS['createNecessaryTables']==true) {
	createCopyTable();
	createClusterTable();
	createCountTable();
	createDirectionConnectionTable();
}
if ($GLOBALS['clearCopyTable']==true) clearCopyTable();
if ($GLOBALS['clearClusterTables']==true) clearClusterTables();
if ($GLOBALS['clearDirectionConnectionTable']==true) clearDirectionConnectionTable();
if ($GLOBALS['copyLinkTriples']==true) {
	copyLinkTriples();
	$mysql2=mysql_connect($host,$user,$password);
	mysql_select_db($db,$mysql2);
}
if ($GLOBALS['copyToConnectionTable']==true) fillConnectionTable(); 

//fuer die Prozentangabe der vorhandenen Objekte
$stcount=mysql_query("(SELECT DISTINCT subject FROM ".$GLOBALS['copyTableName'].") UNION (SELECT DISTINCT object FROM ".$GLOBALS['copyTableName'].")");
$statementscount=mysql_num_rows($stcount);
mysql_free_result($stcount);
$strows=mysql_query("SELECT count(*) FROM ".$GLOBALS['copyTableName']);
$statementsrow=mysql_fetch_array($strows);
$statementsrowsstart=$statementsrow[0];
$statementsrows=$statementsrowsstart;
mysql_free_result($strows);

$clusterid=0;
$count=array();
$start=microtime(true);
$last=microtime(true);
$queue=array('cluster'=>array(),'depth'=>array());
$delcount=array();
$time=0;
if ($GLOBALS['startClusterAlg']==true) {
	echo "Start clustering... Writing progress Output to delete.out, queue.out and progress.out\n";
	do {
		$result=mysql_query("SELECT count(*) FROM ".$GLOBALS['copyTableName']);
		$rows=mysql_fetch_array($result);
		if ($rows[0]>0) {
			$nextresource=getNextObjectWikilink();
			$depth=0;
			//start Objekt in Clustertabelle einfuegen
			mysql_query("INSERT INTO ".$GLOBALS['clusterTableName']." (cluster_id,object,depth,referenced_by_object,referenced_by_property) VALUES ('".$clusterid."','".$nextresource."','".$depth."','start','start')");			
			while ($nextresource!=NULL) {
				findConnectedResources($nextresource,$depth,$clusterid);
				$nextresource=array_shift($queue['cluster']);
				$depth=array_shift($queue['depth']);
				//aller 30 Minuten Fortschritt ausgeben
				if ((microtime(true)-$last)>1800) {
					$statementsrows=promptProgressInformation($clusterid,$statementscount,$statementsrows,$statementsrowsstart);
					$last=microtime(true);
				}
			}
			$clusterid++;
		}
	} while ($rows[0]>0);
	echo "Start filling CountTable...\n";
	for ($i=0;$i<$clusterid;$i++) {
		$countres=mysql_query("SELECT count(*) FROM ".$GLOBALS['clusterTableName']." WHERE cluster_id=".$i);
		$countrow=mysql_fetch_array($countres);
		mysql_query("INSERT INTO ".$GLOBALS['countTableName']." (id,object_count,triple_count) VALUES ('".$i."','".$countrow[0]."','".$delcount[$i]."')");
	}
	
	echo("\nAll Objects sorted to $clusterid Clusters in ".(microtime(true)-$start)."s\n");
}

/***
 * Zu gegebener Resource werden verbundene Resourcen gesucht (Objekte, Subjekte)
 * Diese werden, falls noch nicht vorhanden, in die Clustertabelle abgelegt und in $queue abgespeichert, damit
 * sp�ter auch f�r diese Resourcen die Verbindungen gesucht werden k�nnen
 * Ausserdem werden die gefundenen verbundenen Resourcen aus der kopierten Statementstabelle geloescht
 */
function findConnectedResources($resource,$depth,$clusterid) {
	global $queue,$delcount;
	$depth++;
	$query="SELECT id,object,predicate FROM ".$GLOBALS['copyTableName']." WHERE subject='".$resource."' UNION SELECT id,subject AS object,predicate FROM ".$GLOBALS['copyTableName']." WHERE object='".$resource."'";
	$newres=mysql_query($query);
	while ($newrow=mysql_fetch_array($newres)) {
		if (!isset($queue['cluster'][$newrow[1]])) {	//Suche im Array mit isset(), da das schneller ist als in_array, oder eine Datenbankabfrage, ob das Objekt schon vorhanden ist: http://ilia.ws/archives/12-PHP-Optimization-Tricks.html 
			mysql_query("INSERT INTO ".$GLOBALS['clusterTableName']." (cluster_id,object,depth,referenced_by_object,referenced_by_property) VALUES ('".$clusterid."','".$newrow[1]."','".$depth."','".$resource."','".$newrow[2]."')");
			$queue['cluster'][$newrow[1]]=$newrow[1];
			$queue['depth'][$newrow[1]]=$depth;		
		}
		deleteTriple($newrow[0]);
		$delcount[$clusterid]++;
	}
	mysql_free_result($newres);
}


function getNextObjectWikilink() {		
	$res=mysql_query("SELECT object FROM ".$GLOBALS['copyTableName']." LIMIT 1");	// Auswahl aus der Datenbank
	$row=mysql_fetch_array($res); 
	#return array(subject=> $row[0],predicate=>$row[1],object=>$row[2],id=>$row[3]);	
	return $row['object'];	
}
function deleteTriple($id) {
	mysql_query("DELETE FROM ".$GLOBALS['copyTableName']." WHERE id=".$id);
}
function objectExistsInClusterTable($object) {
	$res=mysql_query("SELECT object FROM ".$GLOBALS['clusterTableName']." WHERE object='".$object."' LIMIT 1");
	if (mysql_num_rows($res)>0)
		return true;
	else
		return false;
} 
function promptProgressInformation($clusterid,$statementscount,$statementsrows,$statementsrowsstart) {
	global $queue;
	static $first,$timecount,$statementsrowscount;
	//Zeitfortschritt (fuer Aufruf alle 1800 sekunden(30 min))
	$timecount++;
	$timecontent=floor($timecount/2)."h";
	$timecontent.=($timecount%2==1)?":30min":":00min";	
	//Bei jedem 2. Aufruf Optimize Table (aller 1h) fuer die copyTable
	if ($timecount%2==0)
		mysql_query("OPTIMIZE TABLE ".$GLOBALS['copyTableName']);
	
		
	$queuecontent="[$timecontent] Working on Cluster $clusterid, queue size: ".count($queue['cluster'])."\n";
	
	$clust=mysql_query("SELECT count(*) FROM ".$GLOBALS['clusterTableName']);
	$clustrow=mysql_fetch_array($clust);
	$progresscontent="[$timecontent] The ClusterTable contains ".round((($clustrow[0]/$statementscount)*100),2)."% (=".$clustrow[0].") of all Objects.\n";
	mysql_free_result($clust);
	
	$strows=mysql_query("SELECT count(*) FROM ".$GLOBALS['copyTableName']);
	$statementsrowsnew=mysql_fetch_array($strows);
	mysql_free_result($strows);
	$statementsrowscount+=($statementsrows-$statementsrowsnew[0]);
	$delcontent="[$timecontent] $statementsrowscount Triples (".round((($statementsrowscount/$statementsrowsstart)*100),2)."%) deleted, ".($statementsrows-$statementsrowsnew[0])." Triples (".(round((($statementsrows-$statementsrowsnew[0])/($statementsrowsstart)*100),2))."%) within last 30 minutes\n";
	
	//Dateien oeffnen
	if (!$first) {
		$fqueue=fopen('queue.out','w');
		$progress=fopen('progress.out','w');
		$del=fopen('delete.out','w');
		$first=true;
	} 
	else {
		$fqueue=fopen('queue.out','a');
		$progress=fopen('progress.out','a');
		$del=fopen('delete.out','a');
	}
	//Dateien schreiben
	if (fwrite($fqueue, $queuecontent) === FALSE) {
        echo "Cannot write to file (queue.out)";
    }
	if (fwrite($progress, $progresscontent) === FALSE) {
        echo "Cannot write to file (progress.out)";
    }
    if (fwrite($del, $delcontent) === FALSE) {
        echo "Cannot write to file (delete.out)";
    }	
    //Dateien schliessen
	fclose($fqueue);
	fclose($progress);
	fclose($del);
	return $statementsrowsnew[0];
}


