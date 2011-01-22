<?PHP

/************************************************

	Datei um das Datenbankschema dynamisch zu
	generieren
	
************************************************/

// File containing the elementnames ($element_names)

include("element_names.php");

// Contains the xml parsing functions
include("parsing.php");


$db_host = "127.0.0.1:8889"; // database hostname
$db_user = "root"; // database username
$db_pwd  = "root"; // database password

// DB connection

$db_connection = mysql_connect($db_host, $db_user, $db_pwd, true)
	or die( mysql_error());


// DB name

$db_name = "factbook";

// Datenbank loeschen
/*
$sql = "DROP DATABASE $db_name; ";

$db_drop = mysql_query($sql, $db_connection)
	or die ( "Fehler: " . mysql_error() );
*/

// Create DB

$sql = "CREATE DATABASE $db_name  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;";

$db_create = mysql_query($sql, $db_connection)
	or die ( "Fehler: " . mysql_error() );


// Use DB

$sql = "USE $db_name;";

$db_select = ( mysql_query($sql, $db_connection) )
	or die ("Fehler: " . mysql_error());
	
	
	
	

////////////////////////////////////////////////////////
//
//		Generate table dynamically from elementnames		
//
////////////////////////////////////////////////////////			
	
$filename = "test1.rdf";

// Create XML-Object 

$xml = xml_create($filename);

// Extract data
$element_data = xml_get_element_data($xml, $element_names);

// Get datatypes for each element
$element_types = xml_get_element_type($element_names, $element_data);

// Convert elementnames for the use as coloumnames
$element_names_new = xml_convert_element_names($element_names, $element_data);

/*
foreach ($element_data as $string ) {
	echo $string[0] . "<br>";
}

foreach ($element_types as $string ) {
	echo $string . "<br>";
}
*/


// Create table Countries

$item = 0;

$sql = "CREATE TABLE Countries (";

	foreach ( $element_names as $element ) {
		
		$colname = $element_names_new[$element];
		$type = $element_types[$element];
		
		// Coloumn "Name" as primary key
		
		if ($colname == "Name") $type = "CHAR(255) PRIMARY KEY";
		
		$sql .= "$colname $type,";
		
		
	}



// Delete last ","
$sql = rtrim($sql,",");		

// Close SQL-Statement
$sql .= ");";

	
		
// Execute SQL-Statement 	
		
$create_table = ( mysql_query($sql, $db_connection) )
	or die ( "Fehler: " . $sql . mysql_error() );

// Add table "bordercountries"

$sql = "CREATE TABLE bordercountries (Name VARCHAR(255), Landboundaries_bordercountries_title VARCHAR(255));";
// SQL-Statement ausfuehren								
		
$create_table = ( mysql_query($sql, $db_connection) )
	or die ( "Fehler: " . $sql . mysql_error() );
	
	
// DB Verbindung schliessen
$db_close = mysql_close($db_connection);
