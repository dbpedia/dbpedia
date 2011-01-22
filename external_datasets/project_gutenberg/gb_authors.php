<?PHP

/***************************

	Project Gutenberg:
	Extract authornames and respective 
	Wikipedia Links from HTML
	and insert them into gutenberg MySQL-DB

***************************/

///////////////////////////////
//
// Datenbank-Verbindung
//
///////////////////////////////

$db_user = "root";
$db_pwd = "root";
$db_host = "127.0.0.1:8889";

$db_connection = mysql_connect($db_host, $db_user, $db_pwd, true)
	or die('Verbindung nicht möglich: ' . mysql_error());


// Datenbank Name

$db_name = "gutenberg";

// Datenbank auswaehlen

$sql = "USE $db_name;";

$db_select = ( mysql_query($sql, $db_connection) )
	or die ("Fehler: " . mysql_error());
$sql = "SET NAMES utf8";
$sql_exec = mysql_query($sql, $db_connection)
	or die ($sql . mysql_error() );


// Select each file in subfolder "authors"

$directory = "authors";

foreach (glob("$directory/{*.html}", GLOB_BRACE) as $filename) {


$filestring =  file_get_contents($filename);


//////////////////////////////
//
//	$pos: Position at beginning of authorname
//	$posend: Position at the end of auhorname
//
//////////////////////////////

$pos = strpos($filestring,'<div class="pgdbbyauthor">');
$posend = $pos;

while ( $pos < strlen($filestring) ) {

$pos = strpos($filestring, '<h2><a name="', $pos);

// End Of file
if ($pos < $posend) break;


$pos = strpos($filestring, '">', $pos) + 1;

$posend = strpos($filestring,'</a></h2>', $pos);

$length = $posend - $pos - 1;
$author = substr($filestring,$pos+1,$length);


// Delete some special characters from author name

$author = trim($author);
$author = str_replace(",","",$author);
$author = str_replace(".","",$author);
$author = str_replace("'","",$author);
$author = str_replace(" ","_",$author);


// Set position to the end of authorname - string
$pos = $posend;

// Position of the next author
$posnext = strpos($filestring, '<h2><a name="', $pos);

// Position of the next Wikipedia-Links

$poslink = strpos($filestring, '.wikipedia.org/wiki', $pos) - 3;


// Break-condition: If no link is following: end of file
if ($poslink < $pos) break;
// Set $pos to Wikipedia-Link position
else $pos = $poslink;

// If Wikipedia Link is behind the next author, continue (as this link does not belong to current author)
if ($pos > $posnext) {
	$pos = $posend;
	
} else {
	
	$posend = strpos($filestring,'">', $pos);

	$length = $posend - $pos - 1;
	$link = substr($filestring,$pos+1,$length);
	$link = htmlentities($link, ENT_QUOTES);
	$pos = $posend;
	
	// echo $author . " " . $link . " " . $pos . " " . $posend . "<br>";
	
	$sql = "INSERT INTO dbpedialinks VALUES('$author', '$link');";
	$sql_exec = mysql_query($sql, $db_connection)
		or die($sql . mysql_error() );
	
}


} // END while

echo $filename;
echo " fertig<br>";

} // END foreach (files)

