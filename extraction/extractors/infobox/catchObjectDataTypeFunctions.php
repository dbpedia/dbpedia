<?php

/**
 * This file contains all functions used for datatype recognition and parsing of externale  internal links.
 * These functions are called sequentially by the function parseAttributeValue in extractTemplates.php
 *
 */

/**
* Returns metric Unit and value for an Object
*
* The value and Unit of the passed value will be returned in an Array
*
* @param	string	$x	Literaltext, that matched to be a percent value
* @return 	array	the percent value (int/float) at offset[0], the Unit at Offset[1]
*/
function catchUnited(&$x) {
	global $units;

	if(@preg_match('~^([0-9]+((\.|,)[0-9]+)?) ('.join('|',array_keys($units)).')$~',$x,$matches)) {
		$x=str_replace(',', '', $matches[1]);
		return $GLOBALS['W2RCFG']['w2ruri'].$units[$matches[4]];
	} // If there is no space between number and unit
	else if(@preg_match('~^([0-9]+((\.|,)[0-9]+)?)('.join('|',array_keys($units)).')$~',$x,$matches)) {
		$x=str_replace(',', '', $matches[1]);
		return $GLOBALS['W2RCFG']['w2ruri'].$units[$matches[4]];
	} // If different units are present, e.g.: 12mm (13in); the first will be returned
	else if(@preg_match('~^([0-9]+(\.[0-9]+)?) ('.join('|',array_keys($units)).')[\s]*\([\s]*([0-9]+(\.[0-9]+)?)[\s]*('.join('|',array_keys($units)).')[\s]*\)[\s]*$~',$x,$matches)) {
		$x=$matches[1];
		return $GLOBALS['W2RCFG']['w2ruri'].$units[$matches[3]];
	}
}

/**
* Returns percent value
*
* The value of the passed String that is an percent value will be returned
*
* @param	string	$x	Literaltext, that matched a to be a percent value
* @return 	float	the percent value
*/
function catchPercent(&$x) {
	if(preg_match('~^([0-9]{1,3}(\.[0-9]+)?)%$~',$x,$matches)) {
		$x=$matches[1];
		return true;
	}
}

/**
* Returns currency and value
*
* The currency and value for Literaltext will be returned
* eg. $12.99 => [0]12.99, [1]Dollar
*
* @param	string	$x	Literaltext, that matched to be a currency value
* @return 	array	the value at offset[0], the Currency at Offset[1]
*/
function catchMoney(&$x) {
	global $currency;
	if(preg_match('~^('.str_replace('$','\$',join('|',array_keys($currency))).')([0-9]+)\.([0-9]{1,3})$~',$x,$matches)) {
		$x=$matches[2].'.'.$matches[3];
		return $GLOBALS['W2RCFG']['w2ruri'].$currency[$matches[1]];
	}
}

/**
* Returns currency and value
*
* The currency and value for Literaltext will be returned
* eg. $12,000 => [0]12000 [1]Dollar
*
* @param	string	$x	Literaltext, that matched to be a currency value(thousand)
* @return 	array	the value at offset[0], the Currency at Offset[1]
*/
function catchMoneyWoCent(&$x) {
	global $currency;
	if(preg_match('~^('.str_replace('$','\$',join('|',array_keys($currency))).')([0-9]{1,3})(\,([0-9]{3}))*$~',$x,$matches)) {
		$number=str_replace(",","",$matches[0]);	//Complete Match of previous preg_match
		preg_match('~[0-9]+~',$number,$matches2);	//get full value without ,
		$x=$matches2[0];
		return $GLOBALS['W2RCFG']['w2ruri'].$currency[$matches[1]];
	}
	// Added by Piet: $ sign behind value
	else if(preg_match('~^([0-9]{1,3})(\,([0-9]{3}))(\s*'.str_replace('$','\$',join('|',array_keys($currency))).')*$~',$x,$matches)) {
		$number=str_replace(",","",$matches[0]);	//Complete Match of previous preg_match
		preg_match('~[0-9]+~',$number,$matches2);	//get full value without ,
		$x=$matches2[0];
		return $GLOBALS['W2RCFG']['w2ruri'].$currency[$matches[1]];
	}
}

/**
* Returns currency and value
*
* The currency and value for Literaltext will be returned
* eg. $12.53 million => [0]12530000 [1]Dollar
*
* @param	string	$x	Literaltext, that matched to be a currency value(>thousand)
* @return 	array	the value at offset[0], the Currency at Offset[1]
*/
function catchLargeMoney(&$x) {
	global $scale,$currency;
	if(preg_match('~^('.str_replace('$','\$',implode('|',array_keys($currency))).')([0-9.]+) \[?\[?('.implode('|',array_keys($scale)).')\]?\]?$~i',$x,$matches)) {
		$x=$matches[2]*$scale[strtolower($matches[3])];
		// make sure that large numbers are presented as decimals, not E notation
		$x = number_format($x, 0, '.', '');
		return $GLOBALS['W2RCFG']['w2ruri'].$currency[$matches[1]];
	} else if(preg_match('~^([0-9.]+) \[?\[?('.implode('|',array_keys($scale)).')\]?\]?[\s]*('.str_replace('$','\$',implode('|',array_keys($currency))).')$~i',$x,$matches)) {
		$x=$matches[1]*$scale[strtolower($matches[2])];
		// make sure that large numbers are presented as decimals, not E notation
		$x = number_format($x, 0, '.', '');
		return $GLOBALS['W2RCFG']['w2ruri'].$currency[$matches[3]];
	}

}

/**
* Returns value of a large Number
*
* Returns calculated large Number
* eg. 12.53 million => [0]12530000
*
* @param	string	$x	Literaltext, that matched to be a large Number
* @return 	int	large Number
*/
function catchLargeNumber(&$x) {
	global $scale;
	if(preg_match('~^([0-9.]+) \[?\[?('.implode('|',array_keys($scale)).')\]?\]?$~i',$x,$matches)) {
		$x=$matches[1]*$scale[strtolower($matches[2])];
		// make sure that large numbers are presented as decimals, not E notation
		$x = number_format($x, 0, '.', '');
		return true;
	}
}

/**
* Returns Rank value
*
* When a found a Rank like: 5<sup>th</sup> the value of the Rank will be returned
*
* @param	string	$x	Literaltext, that matched to be a Rank
* @return 	int	Rank
*/
function catchRank(&$x) {
	if(preg_match('~^([0-9]+)(<sup>)?(st|nd|rd|th)(</sup>)?$~',$x,$matches)) {
		$x=$matches[1];
		return true;
	}
}

/**
 * Catch date like: August 2007
 *
 *
 */

function catchMonthYear(&$x) {
	global $month;
	if ( preg_match('~^\[?\[?('.implode('|',array_keys($month)).')\]?\]? +\[?\[?([0-9]{1,4})\s*(BCE|BC|CE|AD)?\]?\]?$~', $x, $matches) ) {
		if (!isset($matches[3])) $matches[3] = "";
		$x=(strtoupper(substr($matches[3],0,2))=='BC'?'-':'').substr('0000'.$matches[2],-4).'-'.$month[$matches[1]];
		return true;
	}
}


/**
* Returns Year,Month and Day of provided Date Literal
*
* Provided Data might be a Date like: [[January 20]] [[2001]] or [[1991-10-25]]
* Returns a normalized Date value, eg: 20010120
*
* @param	string	$x	Literaltext, that matched to be a Date
* @return 	int	Date
*/
function catchDate(&$x) {
	global $month;
	if(preg_match('~^\[?\[?('.implode('|',array_keys($month)).') ([0-9]{1,2})\]?\]?,? ?\[?\[?([0-9]{1,4})\s*(BCE|BC|CE|AD)?\]?\]?$~',$x,$matches)) {
		if (!isset($matches[4])) $matches[4] = "";
		$x=(strtoupper(substr($matches[4],0,2))=='BC'?'-':'').substr('0000'.$matches[3],-4).'-'.$month[$matches[1]].'-'.substr('00'.$matches[2],-2);
		return true;
	}
	if(preg_match('~^\[?\[?([0-9]{1,2})\.?\s*('.implode('|',array_keys($month)).')\]?\]?,? \[?\[?([0-9]{1,4})\s*(BCE|BC|CE|AD)?\]?\]?$~',$x,$matches)) {
		if (!isset($matches[4])) $matches[4] = "";
		$x=(strtoupper(substr($matches[4],0,2))=='BC'?'-':'').substr('0000'.$matches[3],-4).'-'.$month[$matches[2]].'-'.substr('00'.$matches[1],-2);
		return true;
	}
	if(preg_match('~^\[?\[?([0-9]{1,4})-([0-9]{1,2})-([0-9]{1,2})\]?\]?$~',$x,$matches)) {
		$x = substr('0000'.$matches[1],-4).'-'.substr('00'.$matches[2],-2).'-'.substr('00'.$matches[3],-2);			//yearmonthday
		return true;
	}
}

function catchYear(&$x) {
	if (preg_match('~^\[\[([0-9]{1,4})\s*(BCE|BC|CE|AD)?\]\]$~',$x,$matches)) {
		if (!isset($matches[2]))
			$x=substr('0000'.$matches[1],-4);
		else
			$x=(strtoupper(substr($matches[2],0,2))=='BC'?'-':'').substr('0000'.$matches[1],-4);
		return true;
	}
}

/**
* Returns Link to a Picture
*
* When found a picture file this function will return the Wikipedia- Link to that
* file.
*
* @param	string	$x	Literaltext, that matched to be a Picture File
* @return 	string	Picture Link
*/
function catchPictureURI(&$x) {
	global $pictureFilenames;
	global $infoboxDBconnection;

	return false;
	if (preg_match('~^[A-Za-z0-9\-_ ]+\.('.implode('|',$pictureFilenames).')$~i',$x,$matches)) {
		$match=str_replace(" ","_",trim($matches[0]));
		// SQl-query on english WikiPedia Database. If image is found in DB, Image-URL is .../wikipedia/en/thumbs
    	$sql = "SELECT * FROM image WHERE img_name = '".mysql_escape_string($match)."';";
    	$sqlQuery = mysql_query($sql,$infoboxDBconnection);
    	if ( mysql_num_rows($sqlQuery) ) $prefix = 'http://upload.wikimedia.org/wikipedia/en/';
		else $prefix = 'http://upload.wikimedia.org/wikipedia/commons/';
		$md5=md5($match); //md5 values of filename for Picture-Link
		$x= $prefix.$md5{0}.'/'.$md5{0}.$md5{1}.'/'.$match;
		return true;
	} else if(preg_match('~^\[\[Image:([A-Za-z0-9\-_ ]+\.('.implode('|',$pictureFilenames).'))\|[0-9]+px\]\]$~i',$x,$matches)) {
		$match=str_replace(" ","_",trim($matches[1]));
		// SQl-query on english WikiPedia Database. If image is found in DB, Image-URL is .../wikipedia/en/thumbs
    	$sql = "SELECT * FROM image WHERE img_name = '".mysql_escape_string($match)."';";
    	$sqlQuery = mysql_query($sql,$infoboxDBconnection);
    	if ( mysql_num_rows($sqlQuery) ) $prefix = 'http://upload.wikimedia.org/wikipedia/en/';
		else $prefix = 'http://upload.wikimedia.org/wikipedia/commons/';
		$md5=md5($match); //md5 values of filename for Picture-Link
		$x=$prefix.$md5{0}.'/'.$md5{0}.$md5{1}.'/'.$match;
		return true;
	} else if(preg_match('~^Image:([A-Za-z0-9\-_ ]+\.('.implode('|',$pictureFilenames).'))$~i',$x,$matches)) {
		$match=str_replace(" ","_",trim($matches[1]));
		// SQl-query on english WikiPedia Database. If image is found in DB, Image-URL is .../wikipedia/en/thumbs
    	$sql = "SELECT * FROM image WHERE img_name = '".mysql_escape_string($match)."';";
    	$sqlQuery = mysql_query($sql,$infoboxDBconnection);
    	if ( mysql_num_rows($sqlQuery) ) $prefix = 'http://upload.wikimedia.org/wikipedia/en/';
		else $prefix = 'http://upload.wikimedia.org/wikipedia/commons/';
		$md5=md5($match); //md5 values of filename for Picture-Link
		$x=$prefix.$md5{0}.'/'.$md5{0}.$md5{1}.'/'.$match;
		return true;
	} // Catch pictures, which are preceeded by text , still experimental
	// Only catches String ending with the pictue e.g. Alberta, flag_of_Alberta.png , not Alberta, flag.png, more text
	else if (preg_match('~^[A-Za-z0-9\-_, ]+\.('.implode('|',$pictureFilenames).') ?$~i',$x,$matches)) {
		$match=str_replace(" ","_",trim($matches[0]));
		// SQl-query on english WikiPedia Database. If image is found in DB, Image-URL is .../wikipedia/en/thumbs
    	$sql = "SELECT * FROM image WHERE img_name = '".mysql_escape_string($match)."';";
    	$sqlQuery = mysql_query($sql,$infoboxDBconnection);
    	if ( mysql_num_rows($sqlQuery) ) $prefix = 'http://upload.wikimedia.org/wikipedia/en/';
		else $prefix = 'http://upload.wikimedia.org/wikipedia/commons/';
		$md5=md5($match); //md5 values of filename for Picture-Link
		$x= $prefix.$md5{0}.'/'.$md5{0}.$md5{1}.'/'.$match;
		return true;
	}
	return false;
}

//	Catches internal links, changes $x to an image URI or the respective DBpedia.org/resource URI
function catchLink(&$x) {
    return false;

#preg_match('~^(\[\[[^\]]+(\|[\]]*)*\]\][, \s]*){3,}$~',$x);
//	IF LINK FOUND
	global $infoboxDBconnection;
	if (preg_match('~^\[\[([^\]\|]+)(\|[^\]]*)*\]\]$~m',$x,$matches)) {
		$img=str_ireplace('Image:','',$matches[1],$c);
//		IF LINK IS AN IMAGE $c != 0
		if($c) {
			// SQl-query on english WikiPedia Database. If image is found in DB, Image-URL is .../wikipedia/en/thumbs
    		$sql = "SELECT * FROM image WHERE img_name = '".mysql_escape_string($img)."';";
    		$sqlQuery = @mysql_query($sql,$infoboxDBconnection);
    		if ( @mysql_num_rows($sqlQuery) ) $prefix = 'http://upload.wikimedia.org/wikipedia/en/';
			else $prefix = 'http://upload.wikimedia.org/wikipedia/commons/';
			$img = str_replace(" ","_",trim($img));
			$imgmd=md5($img);
		}
		$x=$c?$prefix.$imgmd[0].'/'.$imgmd[0].$imgmd[1].'/'.$img:$GLOBALS['W2RCFG']['wikipediaBase'].$matches[1];
		return true;
	}
#	else if (preg_match('~^\[\[([^\]]+)\]\]$~',$x,$matches)) {
#		$x=$GLOBALS['W2RCFG']['wikipediaBase'].encodeLocalName($matches[1]);
#		return true;
#	}
}

/**
 * catches links to external websites, returns all matches as an array
 * @param string $o
 * @return array of string $matches or false
 */
function catchExternalLink($o) {
	// Parse for digits at the beginning of String: exclude statistical values, followed by referneces
	if ( preg_match("/^[0-9]+/",$o) ) return false;
	// Catch external links
	if ( preg_match_all("/(http:\/\/[:\.a-z_ A-Z0-9\/\-]+\.[a-zA-Z0-9]{2,4})([^\]]*)/", $o, $matches) ) {
		return $matches;
	}
	return false;
}


/**
 * Parses internal Links:
 * - If a Link is found: links to currencies are replaced with the respective symbol, external links are removed
 * (these are usually references), links to dates are removed (if more than one link was found).
 * - If only digits and currencies are at the beginning of the String, anything else is removed and the number
 * is parsed for it's type (int, float, unit)
 * - In any other cases, where internal links are mixed with text, the function compares the aggregated word-length
 * of the links, with the length of text items. If the links are longer, the String is parsed as a link list, else
 * the brackets are removed and the String is recognized as text.
 *
 *
 *
 */

function catchLinkList(&$o,$s, $p, &$dtype, &$extractor) {

	//	Match for any Link
	$foundLink = preg_match_all("/(\[{2})([^\]]+)(\]{2})/", $o, $matches);
	if (!$foundLink) return false;

	// Initialize object-type with literal
	$object_is = 'l';
	// echo "\n$o";

	// Test whether property is included in known Linklists and parse Links
	$knownLinkLists = $GLOBALS['linklistpredicates'];

	// Remove DBpedia Base URI
	$propertyName = substr($p,strlen($GLOBALS['W2RCFG']['propertyBase']),strlen($p) );

	// Compare property-name with known LinkList properties
	foreach ( $knownLinkLists as $linkList ) {
		if ( $linkList == $propertyName ) {
			preg_match_all( "/(\[{2})([^\]]+)(\]{2})/",$o,$matches);
			foreach($matches[2] as $l) {
					if(strlen($l)>1) {
						// Extract internal links of type [[abc|def]]
						$pos = stripos($l, "|");
						if ($pos) $l = substr($l,0,$pos);
						$object = $GLOBALS['W2RCFG']['wikipediaBase'].ucwords($l);
						$object_is='r';
						writeTripel($s,$p,$object,'main',$object_is);
						unset($object);
					}
				}
				return true;
		}

	}


	// $weight: If text is mixed with length, this is the weight assigned to the links
	// in order to decide whether the composite link/text String is parsed as link-list or text-litearal
	// any value > 1, gives more weight to links, any value between 0 and 1, morr to the text part
	$weight = 1.25;

	//	If an internal Link was found:

	// Replace Links to currencies with the respective Symbol
	$currencies = array("U.S. (D|d)ollar" => "\$", "United States (D|d)ollar" => "\$", "Dollar" => "\$", "Euro" => "€", "Yen" => "¥", "Pound" => "£", "KRW" => "₩");
	// $z = str_replace('$','\$',$o);
	foreach ( $currencies as $key => $currency) {
		// Do not match real Links to currencies e.g. United_States: currency = [[United States Dollar]] ($)
		if ( preg_match('/^\s*\[{2}'.$key.'\s?\|?[^\]]*\]{2}[\(\s ]*'.$currency.'[\)\s]*$/',$o) )
			break;
		$o = trim(preg_replace('/(^[^'.$currency.']*)(\[{2}'.$key.'\s?\|?[^\]]*\]{2})/','\\1'.$currency,$o));
		// Old Version
		// $o = trim(preg_replace("/\[{2}".$key."\s?\|?[^\]]*\]{2}(^$)/",$currency,$o));
	}

	// Remove External Links (these are usually references)
	$o = trim(preg_replace("/\[http:\/\/[^\]]+\]/","",$o));
	//	Remove any Links between parentheses

	//	Remove links in parentheses. Bug: Destroys Links with "()" inside an internal Link. e.g. Boris_Becker: birthplace
	$o = trim(preg_replace("/\([^\[\]]*\[{2}[^\)\]]*\]{2}[^\)]*\)/","",$o));
	//	If Link is a Date and more than one Link was found, remove Link
	if ($foundLink > 1) {
		$months = array("January", "February", "March", "April", "May", "June", "July", "August", "September", "November", "December");
		foreach ($months as $month) {
			// $o = trim(preg_replace("/\[{2}$month [0-9]{1,2}\]{2},?[\s]*,?/","",$o));
			$o = trim(preg_replace("/\[{2}$month [0-9]{1,2}\]{2},?[\s]*(,?[\s]*(\(?\[{2}|\()[0-9]{4}(\]{2}\)?|\)))?/","",$o));
		}

	// If Link is a year, remove Link
		$o = trim(preg_replace("/\(?\[{1,2}[0-9]{4}\]{1,2}\)?/", "", $o));
	}


	// Initialize ResultString
	$resultstring = "";

	// String begins with Text and is followed by one or more Links
	if ( preg_match("/^([^\[]+)(\[{2})*/",$o,$stringStart) ) {
		// String ends with a Link -> this means String is like: "abc [[def | jjj ]] ghi [[ xyz ]]"  (Problem "abc [[def]][[xyz]]")
		if( preg_match("/\]{2}$/",$o) ) {
			// Match Text, followed by a Link
			$found = preg_match_all("/([^\[\]]+)(\[{2})([^\]]+)(\]{2})/",$o,$matches);
			$linkPos = 3; // Position of Links in $matches
			$textPos = 1; // Position of Text in $matches
		// String ends with Text -> this means String is like: "abc [[def | jjj ]] ghi [[ xyz ]] klm"
		} else {
			// Initialize ResultString with "abc "
			$resultstring = $stringStart[1];
			// Match Link, followed by Text
			$found = preg_match_all("/(\[{2})([^\]]+)(\]{2})([^\[]+)/",$o,$matches);
			$linkPos = 2; // Position of Links in $matches
			$textPos = 4; // Position of Text in $matches
		}
		// String starts with numbers and/or currency Symbols
		if ( (preg_match("/(^[\s]*([0-9\$€£¥₩]+[\.,][0-9\$€£¥₩]+|[0-9\$€£¥₩]+)[\s]*(((B|b)illion)?|((M|m)illion)?|((T|t)rillion)?|((Q|q)uadrillion)?))(.*)/", $o, $numberMatch) && strlen(trim($numberMatch[1])) > 2)
			|| preg_match("/^([0-9\$€£¥₩]+[\.,][0-9\$€£¥₩]+|[0-9\$€£¥₩]+)([\s]*$)/",$o) ) {
			// CodeBlock for parsing Numbers

			// Remove any remaining Links
			// $o = preg_replace("/\[{2}[^\]]+\]{2}/","",$o);
			// echo "\n$o";
			// Read Links to numbers, e.g., BMW: revenue => € 4.9 [[10000000 (number)| billion]]
			if ( preg_match("/(^[\s]*([0-9\$€£¥₩]+[\.,][0-9\$€£¥₩]+|[0-9\$€£¥₩]+)[\s]*)(\[{2}[^\]\|]*\(number\)[^\]\|]*\|)([^\]]+)(\]{2})(.*$)/",$o,$numberMatch) ) {
				$o = trim($numberMatch[1]) . " " . trim($numberMatch[4]);
			} else if ( $startPos = strpos($o,"[[") )
				// Remove anything after first Link
				$o = substr($o,0,$startPos);

			// Remove year: e.g. p:revenue = "22 billion $ (2004) => "22 billion $";
			if ( preg_match("/^[\s]*[0-9\$€£¥₩]+[^\(]+\([0-9]{4}\).*/",$o) ) {
				$o = trim(preg_replace("/\([0-9]{4}\)/","",$o));
			}

			$o = trim($o);
			// echo "\n parsing for values $o";
			list($o,$o_is,$dtype,$lang)=$extractor->parseAttributeValue($o,$s,$p);
			if($o!==NULL) writeTripel($s,$p,$o,'main',$o_is,$dtype,$lang);
			return true;

		} else {
			// Calculate aggregate length of text and Links
			$lengthLink = 0;
			$lengthText = strlen($resultstring);
			foreach($matches[$linkPos] as $match) {
				if ($pos = strpos($match,"|")) $lengthLink += strlen(preg_replace("/\s/","",substr($match,$pos,strlen($match)-$pos)));
				else $lengthLink += strlen(preg_replace("/\s/","",$match));
			}
			foreach($matches[$textPos] as $match) {
				$lengthText += strlen(preg_replace("/\s/","",$match));
			}
			// compare aggregated length of links and literals ($weight is defined at the top of this function)
			if ( $weight*$lengthLink >= $lengthText ) {
				// CodeBlock for Start with Text and more Links than Text
				// echo "Start with Text: more Links ($lengthLink,$lengthText)";
				foreach($matches[$linkPos] as $l) {
					if(strlen($l)>1) {
						// Extract internal links of type [[abc|def]]
						$pos = stripos($l, "|");
						if ($pos) $l = substr($l,0,$pos);
						$object = $GLOBALS['W2RCFG']['wikipediaBase'].ucwords($l);
						$object_is='r';
						writeTripel($s,$p,$object,'main',$object_is);
						unset($object);
					}
				}
				return true;
				} else {
				// CodeBlock for Start with Text and more Text than Links
				// echo "Start with Text: more Text ($lengthLink,$lengthText) ($o)";
				// Replace Links with their Labels
				$o = preg_replace_callback("/(\[{2}[^\|^\]]+)(\|)([^\]]+)(\]{2})/",'getLabelForLink', $o);
				// Replace simple links with their link-text
				$o = preg_replace("/\[{2}|\]{2}/","",$o);
				writeTripel($s,$p,$o,'main',$object_is);
				return true;
			}

		}

	// String begins with Links and is followed by Text or Links)
	} else if ( preg_match("/^(\[{2})([^\]]+)(\]{2})/",$o,$stringStart) ) {
		// String ends with a Link -> this means String is like: "[[def | jjj ]] ghi [[ xyz ]]"
		if( preg_match("/\]{2}$/",$o) ) {
			// Initialize ResultString with "[[def | jjj ]]"
			$resultstring = $stringStart[2];
			// Match Text, followed by a Link
			$found = preg_match_all("/([^\[\]]+)(\[{2})([^\]]+)(\]{2})/",$o,$matches);
			$linkPos = 3; // Position of Links in $matches
			$textPos = 1; // Position of Text in $matches

		// String ends with Text -> this means String is like: "[[def | jjj ]] ghi [[ xyz ]] klm"
		} else {
			// Match Link, followed by Text
			$found = preg_match_all("/(\[{2})([^\]]+)(\]{2})([^\[]+)/",$o,$matches);
			$linkPos = 2; // Position of Links in $matches
			$textPos = 4; // Position of Text in $matches
		}

		// String is composed only of Links -> this means String is like: "[[abc]][[def]]"
		if (!$found) {
			// CodeBlock for returning only Links
			$found = preg_match_all("/(\[{2})([^\]]+)(\]{2})/",$o,$matches);
			foreach($matches[2] as $l) {
				if(strlen($l)>1) {
					// Extract internal links of type [[abc|def]]
					$pos = stripos($l, "|");
					if ($pos) $l = substr($l,0,$pos);
					$object = $GLOBALS['W2RCFG']['wikipediaBase'].ucwords($l);
					$object_is='r';
					writeTripel($s,$p,$object,'main',$object_is);
					unset($object);
				}
			}
			return true;
		} else {
			// Calculate aggregate length of text and Links

			// If String starts and ends with Link, add length of first Link
			$lengthLink = strlen($resultstring);
			if ($lengthLink > 0) {
				// If first Links of type [[abc | def]] only count "def"
				if ($pos = strpos($resultstring,"|")) $lengthLink += strlen(preg_replace("/\s/","",substr($resultstring,$pos,strlen($resultstring)-$pos)));
			}
			$lengthText = 0;
			// add length of current link (in $matches) to aggregate length (if link is like [[abc|def]], only def counts)
			foreach($matches[$linkPos] as $match) {
				if ($pos = strpos($match,"|")) $lengthLink += strlen(preg_replace("/\s/","",substr($match,$pos,strlen($match)-$pos)));
				else $lengthLink += strlen(preg_replace("/\s/","",$match));
			}
			// add length of literals to aggregate text-length
			foreach($matches[$textPos] as $match) {
				$lengthText += strlen(preg_replace("/\s/","",$match));
			}

			// compare aggregated length of links and literals ($weight is defined at the top of this function)
			if ( $weight*$lengthLink >= $lengthText ) {
				// CodeBlock for Start with Link and more Links than Text
				// echo "Start with Link: more Links ($lengthLink,$lengthText) ($o)";
				if (strlen($resultstring) > 1) array_unshift($matches[$linkPos],$resultstring);
				foreach($matches[$linkPos] as $l) {
					if(strlen($l)>1) {
						// Extract internal links of type [[abc|def]]
						$pos = stripos($l, "|");
						if ($pos) $l = substr($l,0,$pos);
						$object = $GLOBALS['W2RCFG']['wikipediaBase'].ucwords($l);
						$object_is='r';

						writeTripel($s,$p,$object,'main',$object_is);
						unset($object);
					}
				}
				return true;
			} else {
				// CodeBlock for Start with Link and more Text than Links
				// echo "Start with Link: more Text ($lengthLink,$lengthText) ($o)";
				// Replace Links with their Labels
				$o = preg_replace_callback("/(\[{2}[^\|^\]]+)(\|)([^\]]+)(\]{2})/",'getLabelForLink', $o);
				// Replace simple links with their link-text
				$o = preg_replace("/\[{2}|\]{2}/","",$o);
				writeTripel($s,$p,$o,'main',$object_is);
				return true;
			}

		}


	}
}

// Needed to replace internal Links of type [[ abc | def]] with their label ("def")
function getLabelForLink($text2) {
	return " ".ltrim($text2[3]) ;
}

/**
 * Parses numbers, with additional year behind, e.g.; numEmployees = 12,380 (2006)
 * Or an external link as reference, e.g.: revenue = 23 billion $ [http://moneyfacts.com]
 *
 */
function catchNumberWithReference($o, $s, $p, &$extractor) {
	// echo "\nNWR: $o";

	// Matches numbers / units followed by year reference
	if ( preg_match("/(^[0-9,\.\$£€¥₩ ]+((b|B)illion|(m|M)illion)?|((T|t)rillion)?|((Q|q)uadrillion)?)([\s]*\([0-9]{4}\))(.*)/",$o, $match) ) {
		//$o = preg_replace("/\([0-9]{4}\)/","",$o);
		$o = trim($match[1]);
		// parseAttributeValue
	} else if ( preg_match("/(^[0-9,\.\$£€¥₩ ]+((b|B)illion|(m|M)illion)?|((T|t)rillion)?|((Q|q)uadrillion)?)([\s]*\[http:\/\/[^\]]+\].*)/",$o,$match) ) {
		$o = trim($match[1]);
	}
	if ($match) {
		list($o,$o_is,$dtype,$lang)=$extractor->parseAttributeValue($o,$s,$p);
		if($o!==NULL) writeTripel($s,$p,$o,'main',$o_is,$dtype,$lang);	return true;
	}
	return false;
}

