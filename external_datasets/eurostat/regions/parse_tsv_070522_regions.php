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

$tablename2 = "regions";

// Spaltenname der Region / Land (PRIMARY KEY)
$idfield = "geocode";

$sql = "CREATE TABLE $tablename2 ($idfield VARCHAR (255) NOT NULL, PRIMARY KEY($idfield) );";

$sql_exec = mysql_query($sql, $db_connection)
	or die ($sql . mysql_error() );
	



// Array including Coloumnames:

$col_names_long = array(
	"faa10000.tsv" => "total_average_population",
	"fab10000.tsv" => "GDP",
	"fab12048.tsv" => "disposable_income",
	"fac11536.tsv" => "unemployment_rate_total"	
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

// PROBLEM: ":", und Flags (Bsp."s" fuuer geschaetzter Werte etc.)"
// Abhilfe: Array mit Flags und Sonderzeichen wie " : " definieren und
// in die Tyoenoruefung mit einbeziehen. Achtung: Muss bei SQl-Inserts dann
// mit NULL werten belegt werden.

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

$sql = "ALTER TABLE $tablename2 ADD $col_name $col_type;";

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
	
	$sql = "SELECT * FROM $tablename2 WHERE $idfield = '$id';";
	$sql_exec = mysql_query($sql, $db_connection)
	  or die ($sql . mysql_error());
	$found = mysql_num_rows($sql_exec);
	
	// Falls Region schon vorhanden...
	if ($found) {
			
			$sql = "UPDATE $tablename2 SET $col_name = ";

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
		
		$sql = "INSERT INTO $tablename2 ($idfield, $col_name) VALUES (";

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

$sql = "ALTER TABLE $tablename2 ADD $col_name VARCHAR (255) NOT NULL;";

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
	
	$sql = "SELECT * FROM $tablename2 WHERE $idfield = '$id';";
	$sql_exec = mysql_query($sql, $db_connection)
	  or die ($sql . mysql_error());
	$found = mysql_num_rows($sql_exec);
	
	// Falls Region schon vorhanden...
	if ($found) {
		
		$sql = "UPDATE $tablename2 SET $col_name = '$countryname' WHERE $idfield = '$id';";
		$sql_exec = mysql_query($sql, $db_connection)
		  or die ($sql . mysql_error());
	}
	
		
} // END foreach($rows as $row)

// Clear Database (Delte Rows with unknown Regionnames)

$sql = "DELETE FROM $tablename2 WHERE name = '';";
$sql_exec = mysql_query($sql, $db_connection)
  or die ($sql . mysql_error());


