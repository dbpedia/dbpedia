<?php

/**
 *
 *
 *  author: Georgi Kobilarov (FU Berlin)
 */

class ObjectTypeParser implements Parser
{
	const parserID = "http://dbpedia.org/parsers/ObjectTypeParser";

	private static $mysql;

	public static function getParserID() {
		return self::parserID;
	}

	public static function parseValue($input, $language, $restrictions) {

		if (! self::$mysql) {
			include ("databaseconfig.php");
			$catalog = $dbprefix."extraction_".$language;
			self::$mysql = new MySQL($host, $user, $password, $catalog);
		}

		$results = array();
		$filteredresults = array();

		preg_match_all("/\[\[([^:\]]*)\]\]/", $input, $matches, PREG_SET_ORDER);

		foreach ($matches as $match) {
			if (strlen($match[1]) > 255) {
				continue;
			}
			
			$link = self::getLinkForLabeledLink($match[1]);
			if ($link != null) {
				$results[] = $link;
			}
		}

		self::$mysql->query("SET NAMES utf8");

		$restrictions = null;

		if(isset($restrictions)) {
			foreach($results as $r) {
				$resourcequeryname = self::encodeLocalName($r);
				$query = "select type from types where resource = '$resourcequeryname'";
				$qresult = self::$mysql->query($query);
				$correctType = false;
				//var_dump($restrictions);
				while($row = mysql_fetch_array($qresult, MYSQL_ASSOC)) {
					if($row['type'] == $restrictions) {
						$filteredresults[] = $r;
					}
				}
			}
		} else {
			return $results;
		}

		return $filteredresults;
	}

	private static function getLinkForLabeledLink($text2) {
		$text2 = preg_replace("/\|.*/", "", $text2) ;
		if (strlen($text2) > 0) {
			return $text2;
		}
	}

	static function encodeLocalName($string) {
		$string = urlencode(str_replace(" ","_",trim($string)));
		// Decode slash "/", colon ":", as wikimedia does not encode these
		$string = str_replace("%2F","/",$string);
		$string = str_replace("%3A",":",$string);

		return $string;
	}


}


