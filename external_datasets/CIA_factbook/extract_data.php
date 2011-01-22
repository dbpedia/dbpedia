<?PHP

/************************************************

	Extracts data from the source RDF/XML files
	and writes them to the DB
	
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


// Use DB
$sql = "USE $db_name;";

$db_select = ( mysql_query($sql, $db_connection) )
	or die ("Fehler: " . mysql_error());
	
// Auf Utf-8 setzen
$sql = "SET NAMES utf8";
$sql_exec = mysql_query($sql, $db_connection)
	or die($sql . mysql_error() );


////////////////////////////////////////////////////////
//
//	Foreach loop, reading each .rdf file in $directory
//
////////////////////////////////////////////////////////

// Directory containing the .rdf files

$directory = "rdf";

foreach (glob("$directory/{*.rdf}", GLOB_BRACE) as $filename) {
	
// Exclude some files with bad encoding

if ($filename == "$directory/cq.rdf" || $filename == "$directory/cr.rdf" || $filename == "$directory/cs.rdf" || $filename == "$directory/so.rdf") continue;	

// Tablename
$table_name = "Countries";


// Create simple_xml_object 
$xml = xml_create($filename);

// array containing the data
$element_data = xml_get_element_data($xml, $element_names);

// array conatining the converted element names
$element_names_new = xml_convert_element_names($element_names, $element_data);


/////////////////////////////////////
//
// Create SQL-Inserts dynamically 
//
/////////////////////////////////////


// 	$item is index for the second dimension of the data-array. 
//	Constant at zero, for elements occurring only once in a source file

$item = 0;

$sql = "INSERT INTO $table_name (";


// Add coloumnames to the SQL-statement
	
foreach ($element_names_new as $colname)	{
	
		if ( strlen($colname) > 1 ) {
			$sql .= "$colname,";		
		}
	
} // END foreach

// Delete last comma
$sql = rtrim($sql,",");


$sql .= ") VALUES (";
		
// Add values to the SQL-statement

$item = 0;	
foreach ($element_data as $value)	{
	
	$data = $value[$item];
	
	// Delete " ' " from the strings
	
	$data = str_replace("'","",$data);
	
	
	if ( $data != NULL) {
	
		if ( strspn($data,".-0123456789") != strlen($data) ) {
		
			$sql .= "'$data',";		
	
		} else {
	
			$sql .= "$data,";		
		
		} // END if
	} // END if
	
} // END foreach
		
// Delete last comma
$sql = rtrim($sql,",");		

// Close SQL-Statement
$sql .= ");";

$sql_exec = ( mysql_query($sql, $db_connection) )
	or die ("Fehler: " . mysql_error() );


$sql = "";


/////////////////////////////////////
//
// Table bordercountries
//
/////////////////////////////////////


// Tablename
$table_name = "bordercountries";

// Array containing elementnames
$elementnames = array("Name", "Landboundaries-bordercountries-title");

// Extract data 
$element_data = xml_get_element_data($xml, $elementnames);

// Converted eleemntnames
$element_names_new = xml_convert_element_names($elementnames, $element_data);



/////////////////////////////////////
//
// SQL-Insert
//
/////////////////////////////////////

$name = $element_data["Name"][0];
$name = str_replace("'","",$name);

foreach ($element_data["Landboundaries-bordercountries-title"] as $data) {

$data = str_replace("'","",$data);

$sql = "INSERT INTO $table_name (";

// Add coloumnames to SQL-statement
	
foreach ($element_names_new as $colname)	{
	
		if ( strlen($colname) > 1 ) {
			$sql .= "$colname,";		
		}
	
} // END foreach

// Delete last comma
$sql = rtrim($sql,",");

$sql .= ") VALUES (";
		
// Add data

$sql .= "'$name', '$data');";	


// Execute SQL insert

$sql_exec = ( mysql_query($sql, $db_connection) )
	or die ("Fehler: " . mysql_error() );

$sql = "";

} // END foreach $data


///////////////////////////////////////////////////////
//
//	ENDE CODEBLOCK UEBER ALLE DATEIEN
//
///////////////////////////////////////////////////////



} // END foreach (Ende der Schleife ueber alle rdf/.rdf Dateien)



// Add coloum: "encodednames", used for the URIs

$sql = "ALTER TABLE Countries ADD `name_encoded` VARCHAR( 255 ) NOT NULL";
$sql_exec = mysql_query($sql)
	or die( $sql . mysql_error() );
$sql = "ALTER TABLE bordercountries ADD `bordercountry_encoded` VARCHAR( 255 ) NOT NULL";
$sql_exec = mysql_query($sql)
	or die( $sql . mysql_error() );
$sql = "UPDATE Countries SET name_encoded = name;";
$sql_exec = mysql_query($sql)
	or die( $sql . mysql_error() );
$sql = "UPDATE bordercountries SET bordercountry_encoded = Landboundaries_bordercountries_title;";
$sql_exec = mysql_query($sql)
	or die( $sql . mysql_error() );
$sql = "UPDATE Countries SET name_encoded = REPLACE(name_encoded,' ','_');";
$sql_exec = mysql_query($sql)
	or die( $sql . mysql_error() );
$sql = "UPDATE bordercountries SET bordercountry_encoded = REPLACE(bordercountry_encoded,' ','_');";
$sql_exec = mysql_query($sql)
	or die( $sql . mysql_error() );
$sql = "UPDATE Countries SET name_encoded = REPLACE(name_encoded,',','');";
$sql_exec = mysql_query($sql)
	or die( $sql . mysql_error() );
$sql = "UPDATE bordercountries SET bordercountry_encoded = REPLACE(bordercountry_encoded,',','');";
$sql_exec = mysql_query($sql)
	or die( $sql . mysql_error() );
$sql = "ALTER TABLE Countries ADD factbookcode CHAR(2) NOT NULL";
$sql_exec = mysql_query($sql)
	or die( $sql . mysql_error() );
$sql = "UPDATE Countries SET factbookcode = SUBSTR(has_url,59,2);";
$sql_exec = mysql_query($sql)
	or die( $sql . mysql_error() );


// END encodednames SQL


$db_close = mysql_close($db_connection);


