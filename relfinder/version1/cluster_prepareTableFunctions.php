<?php

// =============== WARNING ===============
// This file uses the global mysql connection.
// Do not use this code with other code that uses mysql.
// =============== WARNING ===============

/*******
 * Functions to create the Database Schemes for Cluster- Algorithm (cluster_main.php)
 */
 function createCountTable() {
	$sqlcreate='CREATE TABLE `'.$GLOBALS['countTableName'].'` (
`id` SMALLINT NOT NULL PRIMARY KEY ,
`object_count` INT NOT NULL ,
`triple_count` INT NOT NULL 
) ENGINE = MYISAM CHARACTER SET latin1 COLLATE latin1_general_ci;';
mysql_query($sqlcreate);
}

function createClusterTable() {
	$sqlcreate='CREATE TABLE `'.$GLOBALS['clusterTableName'].'` (
`id` MEDIUMINT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`cluster_id` MEDIUMINT NOT NULL ,
`object` varchar(255) collate latin1_general_ci NOT NULL ,
`depth` MEDIUMINT NOT NULL , 
`referenced_by_object` varchar(255) collate latin1_general_ci NOT NULL,
`referenced_by_property` varchar(255) collate latin1_general_ci NOT NULL
) ENGINE = MYISAM CHARACTER SET latin1 COLLATE latin1_general_ci;';
mysql_query($sqlcreate);
	$sqlindex='CREATE INDEX clusterindex ON '.$GLOBALS['clusterTableName'].' (object)';
	mysql_query($sqlindex);
} 
function createDirectionConnectionTable() {
	$sqlcreate='CREATE TABLE `'.$GLOBALS['directionConnectionTableName'].'` (
  `id` int(11) NOT NULL auto_increment,
  `resource1` varchar(255) collate latin1_general_ci NOT NULL,
  `resource2` varchar(255) collate latin1_general_ci NOT NULL,
  `predicate` varchar(255) collate latin1_general_ci NOT NULL,
  `triple_id` int(11) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `resource1` (`resource1`),
  KEY `resource2` (`resource2`),
  KEY `predicate` (`predicate`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;';

	mysql_query($sqlcreate) or die(mysql_error());
}
function clearDirectionConnectionTable() {
	mysql_query('TRUNCATE TABLE '.$GLOBALS['directionConnectionTableName']);
}

function clearCopyTable() {
	mysql_query('TRUNCATE TABLE '.$GLOBALS['copyTableName']);
}
function createCopyTable() {
	$sqlcreate='CREATE TABLE `'.$GLOBALS['copyTableName'].'` (
  `subject` varchar(255) collate latin1_general_ci NOT NULL,
  `predicate` varchar(255) collate latin1_general_ci NOT NULL,
  `object` varchar(255) collate latin1_general_ci NOT NULL,
  `id` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci PACK_KEYS=0;
';
	mysql_query($sqlcreate);	
	$sqlindex='CREATE INDEX objectindex ON '.$GLOBALS['copyTableName'].' (object)';
	mysql_query($sqlindex);
	$sqlindexB='CREATE INDEX subjectindex ON '.$GLOBALS['copyTableName'].' (subject)';
	mysql_query($sqlindexB);
}

function copyLinkTriples() {
	mysql_query("INSERT INTO ".$GLOBALS['copyTableName']." SELECT subject,predicate,object,id FROM ".$GLOBALS['statementsTableName']." WHERE object LIKE '".$GLOBALS['wikipediaBase']."%'") or die (mysql_error());
	mysql_query("DELETE FROM ".$GLOBALS['copyTableName']." WHERE subject LIKE 'http://dbpedia.org/instances/%' OR subject LIKE 'bn%'");
	mysql_query("DELETE FROM ".$GLOBALS['copyTableName']." WHERE object='http://dbpedia.org/resource/ '");
	if ($GLOBALS['deleteIgnoredPages']==true) deleteIgnoredTriples();
	if ($GLOBALS['deleteCategoryPages']==true) deleteCategoryTriples();
	echo "Statementscopy ready!\n";
} 

function fillConnectionTable() {
	echo "Start filling Connection Table...\n";
	$res=mysql_query("SELECT subject,object,predicate,id FROM ".$GLOBALS['copyTableName']);
	while ($row=mysql_fetch_array($res)) {
		mysql_query("INSERT INTO ".$GLOBALS['directionConnectionTableName']." (resource1,resource2,predicate,triple_id) VALUES ('".$row[0]."','".$row[1]."','".$row[2]."','".$row[3]."')");
		mysql_query("INSERT INTO ".$GLOBALS['directionConnectionTableName']." (resource1,resource2,predicate,triple_id) VALUES ('".$row[1]."','".$row[0]."','".$row[2]."','".$row[3]."')");
	}
	echo "Connection Table ready!\n";
}

function clearClusterTables() {
	mysql_query("TRUNCATE TABLE ".$GLOBALS['countTableName']);
	mysql_query("TRUNCATE TABLE ".$GLOBALS['clusterTableName']);
}
function deleteCategoryTriples() {
	global $mysql2;
	$mysql1=mysql_connect($GLOBALS['host2'],$GLOBALS['user2'],$GLOBALS['password2']);	
	$result=mysql_query("SELECT object FROM ".$GLOBALS['db'].".".$GLOBALS['copyTableName'],$mysql2); //evtl subject dazu
	while ($row=mysql_fetch_array($result)) {
		$pageA=substr($row[0],-(strlen($row[0])-strlen($GLOBALS['wikipediaBase'])));
		$resA=mysql_query("SELECT count(*) FROM ".$GLOBALS['db2'].".page WHERE page_title='".$pageA."' AND page_namespace=14",$mysql1);
		$rowA=mysql_fetch_array($resA);
		if ($rowA[0]>0)
			$deletearray[]=$row[0];
	}
	mysql_close($mysql1);
	foreach ($deletearray as $delete) {
		mysql_query("DELETE FROM ".$GLOBALS['db'].".".$GLOBALS['copyTableName']." WHERE object='".$delete."'",$mysql2);
	}
	$deletearray=array();
	mysql_close($mysql2);
} 
function deleteIgnoredTriples() {
	global $ignoreProperty,$ignoreObject;
	if (count($ignoreProperty)>0) {
		foreach ($ignoreProperty as $prop) 
			mysql_query("DELETE FROM ".$GLOBALS['copyTableName']." WHERE predicate='".$GLOBALS['propertyBase'].$prop."'");		
	}
	if (count($ignoreObject)>0) {
		foreach ($ignoreObject as $obj)
			mysql_query("DELETE FROM ".$GLOBALS['copyTableName']." WHERE object='".$GLOBALS['wikipediaBase'].$obj."'");
	}
}
