<?php

/////////////////////////////////////////////////////////
//
//  Parst Tab-Delimited Dateien von EUROSTAT
//    -erkennt automatisch den Datentyp der Spaltenr
//    -Legt Tabelle mit dem jeweiligen Dateinamen an
//    -Schreibt Daten in die Tabelle
//
/////////////////////////////////////////////////////////






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



// Datenbank auswaehlen

$sql = "USE $db_name;";

$db_select = ( mysql_query($sql, $db_connection) )
	or die ("Fehler: " . mysql_error());


// Set utf8-encoding

$sql = "SET NAMES utf8;";

$sql_exec = ( mysql_query($sql, $db_connection) )
	or die ("Fehler: " . mysql_error());


// Tabelle anlegen

$tablename1 = "countries";

// Spaltenname der Region / Land (PRIMARY KEY)
$idfield = "geocode";

$sql = "CREATE TABLE $tablename1 ($idfield VARCHAR (255) NOT NULL, PRIMARY KEY($idfield) );";

$sql_exec = mysql_query($sql, $db_connection)
	or die ($sql . mysql_error() );
	


// Array including Coloumnames:

$col_names_long = array(
	"cab10000.tsv" => "marriages",
	"cbb13584.tsv" => "death_rate",
	"cca10000.tsv" => "pupils_and_students",
	"eb011.tsv" => "GDP_per_capita_PPP",
	"eb040.tsv" => "inflation_rate",
	"em011.tsv" => "employment_rate_total",
	"em016.tsv" => "average_exit_age_from_laborforce",
	"em071.tsv" => "unemployment_rate_total",
	"ir031.tsv" => "level_of_internetaccess_households",
	"ir080.tsv" => "ecommerce_via_internet",
	"na010.tsv" => "GDP_current_prices",
	"sdi_as1200.tsv" => "fertility_rate_total"
	);


/************************
 *
 *  Function: delete flags
 * 
 ***********************/  


// Flags und Special characters, to replace in int/double fields
$allowed_chars_special = ":#u#c#s#p#f#i#e# ";

$allowed_chars_int = "-0123456789 " . $allowed_chars_special;
$allowed_chars_float = $allowed_chars_int . ".";

// Function replace_flags: deletes non-numeric characters if datatype is int/double
  
function replace_flags($string, $allowedchars) {
  $allowedchars = explode("#",$allowedchars);
  foreach ($allowedchars as $char) {
    $string = str_replace($char,"",$string);  
  } 
  if (strlen($string) == 0) $string = "NULL";
  echo "$string <br>";
  return $string;
}

/*********************************************
 *
 *  PARSING BEGINNT
 * 
 *********************************************/   


$directory = "tsv";

foreach (glob("{*.tsv}", GLOB_BRACE) as $filename_data) {



// Datei "Total average population"

//$filename_data = "ir080.tsv";
//$filename_dictionary = "";




$file_string = file_get_contents ( $filename_data );
//echo $file_string;


// String ein einzelne Strings zerlegen, die jeweils eine Zeile darstellen

$rows = explode("\n", $file_string );
// var_dump($lines);


// Read coloumname from filename

$col_name = $col_names_long["$filename_data"];

// $col_type: Datentyp initialisieren;
$col_type = "BIGINT"; 


//////////////////////////////////////////////////////////////////
//
// Datentyp der Spalten ermitteln und Daten in Array schreiben
// 
//////////////////////////////////////////////////////////////////


// Datentypen festlegen

$data_type_int = "BIGINT";
$data_type_float = "DOUBLE";
$data_type_text = "VARCHAR(255)";


// Anzahl der Zeilen ermitteln
$rows_length = count($rows);

// Jede Zeile ab der 2. Zeile durchlaufen;
for ( $i = 1; $i < $rows_length; $i++ ) {
  
  // Aktuelle Zeile
  $row = $rows[$i];
  
  // alle Spalten auslesen
  
  $cols = explode("\t", $row);
  
  $col = $cols[1];
  
// Datentyp fuer jede Spalte in jeder Zeile neu pruefen und ggf. aendern.
  // Nur moeglich aus INT -> DOUBLE oder VARCHAR und aus DOUBLE -> VARCHAR
      
  if ( strspn($col,$allowed_chars_int) == strlen($col) ) {
   	// Aus "DOUBLE" oder "VARCHAR" darf nicht "INTEGER" werden. 
	if ($col_type != $data_type_float && $col_type != $data_type_text)
    	$col_type = $data_type_int;		

	} else if ( strspn($col,$allowed_chars_float) == strlen($col) ) {
  		// Aus "VARCHAR" darf nicht "DOUBLE" werden
		if ($col_type != $data_type_text)
		$col_type = $data_type_float;		
	
	} else {
 		$col_type = $data_type_text;
		
	} // END if
    
} //END for

///////////////////////////////////////////////////////
//
// Spalte anhaengen
//
///////////////////////////////////////////////////////


// Spaltennamen als neue SQL-Tabelle anlegen
// und Datentype aus dem Array col_types auslesen

$sql = "ALTER TABLE $tablename1 ADD $col_name $col_type;";

$sql_exec = mysql_query($sql, $db_connection)
  or die ($sql . mysql_error());

//echo $sql;

/////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////////
//
// Daten auslesen und in die Datenbank einfuegen
//
////////////////////////////////////////////////////

for ( $i = 1; $i < $rows_length; $i++ ) {
	
	// Aktuelle Zeile
 	$row = $rows[$i];
	
	// Spalten auslesen
  	$cols = explode("\t", $row);

  	// Bei leerer Zeile abbrechen (Dateiende oder Fehler)

  	if ( count($cols) == 1) {
  		echo "Ende oder leere Zeile. Zeilennr: " . ($i+1);
  		break;
  	} // END IF
	
	
	// Pruefen ob es Region schon gibt, falls ja UPDATE... sonst INSERT
 	// Name der Region / des Landes in 1. Spalte einfuegen
	$id = $cols[0];
	
	$sql = "SELECT * FROM $tablename1 WHERE $idfield = '$id';";
	$sql_exec = mysql_query($sql, $db_connection)
	  or die ($sql . mysql_error());
	$found = mysql_num_rows($sql_exec);
	
	// Falls Region schon vorhanden...
	if ($found) {
			
			$sql = "UPDATE $tablename1 SET $col_name = ";

			$col = $cols[1];

		    // Je nach Datentyp, Werte mit oder ohne " ' " einfuegen
		    if ( $col_type == $data_type_text ) {
		        $sql .= "'$col'";
		    } else {
		     	if ( strpbrk($col,$allowed_chars_special) ) {
		     	 $col = replace_flags($col, $allowed_chars_special);     	
           //$col = "NULL";
           }
		      	$sql .= "$col";
		    } // END if ($col_type)
			
			$sql .= " WHERE $idfield = '$id';";
		
	} else {
		
		$sql = "INSERT INTO $tablename1 ($idfield, $col_name) VALUES (";

		// Name der Region / des Landes in 1. Spalte einfuegen
		$sql .= "'$id', ";

	   	$col = $cols[1];

	    // Je nach Datentyp, Werte mit oder ohne " ' " einfuegen
	    if ( $col_type == $data_type_text ) {
	        $sql .= "'$col');";
	    } else {
	     	if ( strpbrk($col,$allowed_chars_special) ) {
         $col = replace_flags($col, $allowed_chars_special);
         }
	      	$sql .= "$col);";
	    } // END if ($col_type)
		
		
		
	} // END if($found)
	
	//echo $sql;
 	
	$sql_exec = mysql_query($sql, $db_connection)
	  or die ($sql . mysql_error());


} //END for 


///////////////////////////////////////////////////////////////////////
//
//  ENDE SQL - INSERTS
//
///////////////////////////////////////////////////////////////////////

} // END foreach (files)





/////////////////////////////////
//
// Add coloum name
//
/////////////////////////////////

$col_name = "name";

$sql = "ALTER TABLE $tablename1 ADD $col_name VARCHAR (255) NOT NULL;";

$sql_exec = mysql_query($sql, $db_connection)
  or die ($sql . mysql_error());





///////////////////////////////
// Load country-names from .dic file
///////////////////////////////

$filename_dic = "../nuts2003.txt";


$file_string = file_get_contents ( $filename_dic );

$rows = explode("\n", $file_string );

foreach($rows as $row) {
	$cols = explode("\t",$row);
	$id = strtolower($cols[0]);
	$countryname = str_replace("'","",$cols[1]);
	$countryname = ucwords(strtolower($countryname));
//echo "$id $countryname<br>";
	
	$sql = "SELECT * FROM $tablename1 WHERE $idfield = '$id';";
	$sql_exec = mysql_query($sql, $db_connection)
	  or die ($sql . mysql_error());
	$found = mysql_num_rows($sql_exec);
	
	// Falls Region schon vorhanden...
	if ($found) {
		$sql = "UPDATE $tablename1 SET $col_name = '$countryname' WHERE $idfield = '$id';";
		$sql_exec = mysql_query($sql, $db_connection)
		  or die ($sql . mysql_error());
	}
	
		
} // END foreach($rows as $row)

// Clear Database (Delte Rows with unknown Countrynames)

$sql = "DELETE FROM $tablename1 WHERE name = '';";
$sql_exec = mysql_query($sql, $db_connection)
  or die ($sql . mysql_error());



