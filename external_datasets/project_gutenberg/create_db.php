<?PHP

/*********************************************

	Create Database fpr project gutenberg
	author: Piet Hensel

*********************************************/

$db_host = "127.0.0.1:8889"; // MySQL - host
$db_user = "root"; // MySQL - user
$db_pwd  = "root"; // MySQL - password

// DB Verbindung aufbauen

$db_connection = mysql_connect($db_host, $db_user, $db_pwd, true)
	or die('Verbindung nicht möglich: ' . mysql_error());


// Database name 

$db_name = "gutenberg";

// Create DB

$sql = "CREATE DATABASE $db_name DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci; ";

$db_create = mysql_query($sql, $db_connection)
	or die ( "Fehler: " . mysql_error() );

$sql = "USE $db_name;";

$db_select = ( mysql_query($sql, $db_connection) )
	or die ("Fehler: " . mysql_error());
	

// Table contributors

$sql = "CREATE TABLE `contributors` (
  `textId` int NOT NULL,
  `name` varchar(150) NOT NULL,
  `type` varchar(40) NOT NULL,
  PRIMARY KEY  (`textId`,`name`,`type`)
) ENGINE=MyISAM;";

$sql_exec = mysql_query( $sql, $db_connection )
	or die ($sql . mysql_error() );


// Table creators

$sql = "CREATE TABLE `creators` (
  `textId` int NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY  (`textId`,`name`)
) ENGINE=MyISAM;";

$sql_exec = mysql_query( $sql, $db_connection )
	or die ($sql . mysql_error() );


// Table files

$sql = "CREATE TABLE `files` (
  `textId` int NOT NULL,
  `url` varchar(255) NOT NULL,
  `format1` varchar(255) NOT NULL,
  `format2` varchar(255) default NULL,
  `modified` date NOT NULL,
  `extent` bigint(20) NOT NULL,
  PRIMARY KEY  (`url`),
  KEY `textId` (`textId`)
) ENGINE=MyISAM;";

$sql_exec = mysql_query( $sql, $db_connection )
	or die ($sql . mysql_error() );


// Table texts

$sql = "CREATE TABLE `texts` (
  `textId` int NOT NULL,
  `publisher` varchar(255) NOT NULL,
  `title` varchar(255) default NULL,
  `friendlytitle` text default NULL,
  `language` varchar(255) default NULL,
  `created` date default NULL,
  `rights` varchar(255) default NULL,
  `description` varchar(255) default NULL,
  PRIMARY KEY  (`textId`)
) ENGINE=MyISAM;";

$sql_exec = mysql_query( $sql, $db_connection )
	or die ($sql . mysql_error() );
	
// Table subject

$sql = "CREATE TABLE `subject` (
			textId int NOT NULL,
			subject varchar(255) NOT NULL
		) ENGINE=MyISAM;";

$sql_exec = mysql_query( $sql, $db_connection )
	or die ($sql . mysql_error() );
	
// Table DBPedia Links


$sql = "CREATE TABLE `dbpedialinks` (
	name_encoded varchar(255) NOT NULL,
	link varchar(255) NOT NULL
) ENGINE=MyISAM;";

$sql_exec = mysql_query( $sql, $db_connection )
	or die ($sql . mysql_error() );
	


		
// Close DB-Connection

$sql_close = mysql_close($db_connection);

