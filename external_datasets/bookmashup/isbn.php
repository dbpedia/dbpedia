
<?php

if ($_SERVER['argc'] < 4 or $_SERVER['argc'] > 4) {
    echo "Usage: php abstracts.php <dbhost> <dbuser> <dbpassword>\n\n";
    echo "<dbhost>      Database server name, for example 'localhost'\n";
    echo "<dbuser>      Database user name\n";
    echo "<dbpassword>  Database password\n\n";
    exit(1);
}

$i = 1;
$db_host = $_SERVER['argv'][$i];
$i++;
$db_user = $_SERVER['argv'][$i];
$i++;
$db_password = @$_SERVER['argv'][$i];

$mywikiDB = "dbpedia_develop";

$link = mysql_connect($db_host, $db_user, $db_password, true)
	or die("Keine Verbindung m�glich: " . mysql_error());
echo "Verbindung zum Datenbankserver erfolgreich" . "\n";

mysql_select_db($mywikiDB, $link) or die("Auswahl der Datenbank fehlgeschlagen");

mysql_query("SET NAMES utf8", $link);


		$query2 = "SELECT id, name, isbn_text from dbpedia_bookmashup";

		
		$result2 = mysql_query($query2, $link) or die("Anfrage fehlgeschlagen: " . mysql_error());
			
		$rows = 0;
		while ($row = mysql_fetch_array($result2, MYSQL_ASSOC)) 
		{
			$ID = $row["id"];
			$ISBNTEXT = $row["isbn_text"];
			
			
			preg_match("/[0-9X-]{10,20}/", $ISBNTEXT, $match);
			$ISBN = $match[0];
			$ISBN = str_replace("-", "", $ISBN);
			if (strlen($ISBN) == 9) $ISBN = "0$ISBN";
			if (strlen($ISBN) != 10 && strlen($ISBN) != 13) continue;
			$query_update = "update dbpedia_bookmashup set isbn = '$ISBN' where id = $ID";
			mysql_query($query_update, $link) or die("Anfrage fehlgeschlagen: " . mysql_error());
						
			$rows++;
			if ($rows % 1000 == 0) echo "$rows\n";

		}

	
	
	function encode_title($s, $namespace = null) {
        $result = urlencode(str_replace(' ', '_', $s));
        if ($namespace) {
            $result = $namespace . ":" . $result;
        }
        return $result;
    }

    function decode_title($s) {
		if (is_null($s)) return null;
        return preg_replace("/^.*:/", "", str_replace('_', ' ', $s));
    }
