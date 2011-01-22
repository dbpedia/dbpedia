<?PHP


/*********************************************

	DATENBANK FUER EUROSTAT ANLEGEN

*********************************************/

$db_host = "127.0.0.1:8889";
$db_user = "root";
$db_pwd  = "root";

// DB Verbindung aufbauen

$db_connection = mysql_connect($db_host, $db_user, $db_pwd, true)
	or die('Verbindung nicht m�glich: ' . mysql_error());


// Datenbank Name

$db_name = "eurostat";


// Datenbank loeschen

$sql = "DROP DATABASE $db_name; ";

$sql_exec = mysql_query($sql, $db_connection)
	or die ( "Fehler: " . mysql_error() );


	
// Datenbank anlegen

$sql = "CREATE DATABASE $db_name; ";

$sql_exec = mysql_query($sql, $db_connection)
	or die ( "Fehler: " . mysql_error() );
	
	
	
	
	
	
	
