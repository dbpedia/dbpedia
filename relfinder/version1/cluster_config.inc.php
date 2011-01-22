<?php
/******
 * Configuration File for Cluster Algorithm (cluster_main.php) and DBpedia Relationship Finder
 */
// database configuration
	$host = 'localhost';
	$user = 'root';
	$password = '';
	$db = 'powl';

	$copyTableName='statementscopy';
	$clusterTableName='cluster';
	$countTableName='clustercount';
	$directionConnectionTableName='direct_connections';

//statements Table built by Wikipedia Extraction - for creating the reduced Statements Table and for showing Informations in Relationship Finder
	$statementsTableName='statements';
	$modelID=3;
	$wikipediaBase='http://dbpedia.org/resource/';
	$propertyBase='http://dbpedia.org/property/';


/*
 * Creates necessary Tables for Cluster Algorithm and Relationship Finder
 * (CopyTable with Objects,ClusterTable for Clusters,Clustercount for Cluster Statistics, Direct Connection for Relationship Finder)
 */
$createNecessaryTables=false;

//Copy Triples from Statements to CopyTable
	$copyLinkTriples=true;
		//deleting Pages from statementscopy that have a Category:Page Page while copying
		$deleteCategoryPages=true;
		//Database configuration for Wikipedia Database
			$host2='localhost';
			$user2='root';
			$password2='';
			$db2='wikipedia_en';
		//Properties & Objects that will be ignored (deleted from CopyTable) while copying
		$deleteIgnoredPages=true;
			$ignoreProperty=array('Type','type');
			$ignoreObject=array('Album');
	
	
//Copy Triples from CopyTable to ConnectionTable
	$copyToConnectionTable=false;

//Delete all Triples from specified Tables
	$clearCopyTable=false;
	$clearClusterTables=false;
	$clearDirectionConnectionTable=false;

//Starting Cluster Algorithm after preparing the Tables
	$startClusterAlg=false; 
 
//Configuration for Relationship finder
	$objectLinkingURL='http://en.pediax.org/';
	$pictureFilenames=array('jpg','png','jpeg','gif','bmp');
	//No Object will be seen twice in Connection Path when set
	$excludeDuplicateObjects=true;
	$usingClusterTable=true;
