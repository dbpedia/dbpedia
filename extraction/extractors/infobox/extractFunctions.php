<?php


/**
 * Ignores specified Templates (see config.inc.php)
 */
function isIgnored($tpl,&$tplName) {
	$tplName=trim(strtolower(substr($tpl,0,strpos($tpl,'|'))));
	// if(!$GLOBALS['templateStatistics'] && $GLOBALS['tplStat'][$tplName]['count']==1)
	// 	return true;
	if(strlen($tplName) < 1) return true;
	if($tplName[0]=='#' || in_array($tplName,$GLOBALS['W2RCFG']['ignoreTemplates']))
		return true;
	foreach($GLOBALS['W2RCFG']['ignoreTemplatesPattern'] as $pattern)
		if(fnmatch($pattern,$tplName))
			return true;
}

/**
* @param	string	$string	any text
* @return	string	$string	Text mit valid Ascii symbols
*/
function encodeLocalName($string) {
	$string = urlencode(str_replace(" ","_",trim($string)));
	// Decode slash "/", colon ":", as wikimedia does not encode these
	$string = str_replace("%2F","/",$string);
	$string = str_replace("%3A",":",$string);

	return $string;
}

function decodeLocalName($string) {
	return urldecode(str_replace("_"," ",trim($string)));
}


// Helpfunction for preg_replace_callback, to replace "|" with #### inside subtemplates
function replaceBarInSubTemplate($stringArray) {
	return str_replace("|","####",$stringArray[0]);
}


/**
 * Writes the triple + additional information such as language, whether an object is a reference
 * or a literal and the datatype into a global array ($parseResult)
 *
 * @param subject: String containing the triples subject
 * @param predicate: String containing the triples predicate
 * @param object: String containing the triples object
 * @param file: Legacy, should be removed in the future
 * @param object_is: 'r' if object is a reference, 'l' if object is a literal, 'b' if object is a blanknode
 * @param dtype: String containing a literals XS D:datatype
 * @param lang: String containing a literals language
 *
 * TODO: Should encodeLocalName be used for the whole URL? Should URI objects be used?
 *
 */

function writeTripel($subject,$predicate,$object,$file='main',$object_is='r',$dtype=NULL,$lang=NULL)
{
	global $parseResult;

	if ( $object_is == 'r' && !URI::validate(encodeLocalName($object)) ) {
		return null;
	}
	// If $object_is == 'l', encodeLocalName shouldn't be used, the string will be encoded like e.g. \uBC18\uC57C
	if ( $object_is != 'l' ) {
		$object = encodeLocalName($object);
	}

	$predicate = encodeLocalName($predicate);
	if ( USE_PERCENT_ENCODING ) {
		$predicate = str_replace("%","_percent_",$predicate);
	} else if ( ereg("%([A-F0-9]{2})", substr($predicate, -3)) )  {
		$predicate .= "_";
	}

    $parseResult[] = array(encodeLocalName($subject), $predicate, $object, $object_is, $dtype, $lang );
}



/**
 * This function is legacy Code and should be removed in the future
 *
 *
* Speichert Ausgabe in Variable, die sp‰ter in Datei geschrieben wird
*
* Diese Funktion wird nur benutzt, wenn als Wert eines Pr‰dikates mehrere Blanknotes erscheinen
* Es wird f¸r jede Blanknote eine Zeile in der Ausgabe generiert
*
* @param	string	$page	aktuelle Wikiseite bzw. aktuell bearbeitete Blanknote
* @param	string	$p	Pr‰dikat, welches als Objekt eine Blanknoteliste hat
* @param	string	$o	Blanknoteliste der Form _:a1;_:a2;
* @param	string	$propertyBase	Standarduri
* @param	string	$template	aktuelles Template
*/
function printList($s,$p,$o) {
	$o=explode(';',$o);
	foreach($o as $object)
		if(strlen($object)>1) {
			list($ob,$ob_is,$dtype,$lang)=parseAttributeValue(trim($object),$s,$p);
			if ($ob)
				writeTripel($s,$p,trim(str_replace("\n",'',$ob)),'main',$ob_is);
		}
}

/**
* This function is legacy Code and should be removed in the future.
*
*
* Gibt Kategorien als rdf:type aus
*
* Kategorien auf den Seiten mit den Templates werden als rdf:type ausgegeben.
* um den nat¸rlichsprachlichen Inhalt der Klassen nicht zu verlieren wird ausserdem
* ein rdfs:label ausgegeben
*
* @param	string	$category	Kategoriename
* @param	string	$page	aktueller Seitenname
* @param	string	$propertyBase	Standarduri
* @param	string	$template	aktuelles Template
*/
function printCategory($category,$subject) {
	#static $done;
	//$categoryURI=$GLOBALS['W2RCFG']['wikipediaBase'].$GLOBALS['categoryLabel'].':'.encodeLocalName($category);
	$categoryURI=$GLOBALS['W2RCFG']['wikipediaBase'].$GLOBALS['categoryLabel'].':'.$category;
	if ((empty($GLOBALS['W2RCFG']['categories'])&&empty($GLOBALS['W2RCFG']['categoriesPattern']))||preg_match('~^'.implode($GLOBALS['W2RCFG']['categories'],'|').'$~i',$category)||fnmatch(implode($GLOBALS['W2RCFG']['categoriesPattern'],'|'),$category)) {
		writeTripel($subject,$GLOBALS['W2RCFG']['categoryProperty'],$categoryURI,$GLOBALS['filedecisionCategory']);
		#if($done[$category])
		#	return;
		#$done[$category]=true;
		if (!categoryTypeWritten($categoryURI,$GLOBALS['filename'],'c')) {
			writeTripel($categoryURI,$GLOBALS['W2RCFG']['labelProperty'],trim($category),'label','l');
			if ($GLOBALS['addExplicitTypeTriples'])
				printexplicitTyping($categoryURI,$GLOBALS['filename'],'c');
		}
	}
}

function categoryTypeWritten($categoryName,$filename,$catortemp) {
	static $categoryarray=array();
	$categoryName.=($catortemp=='c')?':Cat':':Temp';
	if (!arrayMultiSearch($categoryName,$categoryarray[$filename])) {
		$categoryarray[$filename][]=$categoryName;
		return false;
	}
	return true;
}

function printexplicitTyping($name,$filename,$name_is,$object_is='n') {
	static $namearray=array();
	static $predicatetypearray=array();
	if ($name_is=='c')
		$save=$name.':Cat';
	if ($name_is=='t')
		$save=$name.':Temp';
	if ($name_is=='p')
		$save=$name.':Pred';
	if (!arrayMultiSearch($save,$namearray[$filename])) {
		$namearray[$filename][]=$save;
		if ($object_is!='n')
			$predicatetypearray[$save][$filename]['is']=$object_is;
		$filedecisionTemplate=($GLOBALS['rdftypeProperty']!=$GLOBALS['W2RCFG']['templateProperty'])?'main':'type';
		$filedecisionCategory=($GLOBALS['rdftypeProperty']!=$GLOBALS['W2RCFG']['categoryProperty'])?'main':'type';
		if ($name_is=='c'&&$filedecisionCategory=='type')
			writeTripel($name,$GLOBALS['W2RCFG']['categoryProperty'],$GLOBALS['W2RCFG']['classBase'],'type');
		if ($name_is=='c'&&$filedecisionCategory=='main')
			printexplicitTyping($GLOBALS['W2RCFG']['categoryProperty'],$filename,'p','r');
		if ($name_is=='t'&&$filedecisionTemplate=='type')
			writeTripel($GLOBALS['W2RCFG']['wikipediaBase'].$GLOBALS['templateLabel'].':'.$name,$GLOBALS['rdftypeProperty'],$GLOBALS['W2RCFG']['classBase'],'type');
		if ($name_is=='t'&&$filedecisionTemplate=='main')
			printexplicitTyping($GLOBALS['W2RCFG']['templateProperty'],$filename,'p','r');
		if ($name_is=='p')
			writeTripel($name,$GLOBALS['rdftypeProperty'],($object_is=='l')?$GLOBALS['W2RCFG']['datatypePropertyBase']:$GLOBALS['W2RCFG']['objectPropertyBase'],'type');
		return;
	} else
		return $predicatetypearray[$save][$filename]['is'];
}

/**
 * Help function to search in arrays
 */
function arrayMultiSearch( $p_needle, $p_haystack ) {
   if( !is_array( $p_haystack ) )
       return false;

   if( in_array($p_needle, $p_haystack ))
       return true;

   foreach( $p_haystack as $row ) {
       if(arrayMultiSearch( $p_needle, $row ))
           return true;
   }
   return false;
}

/**
 * This function is legacy code and should be removed in the future
 *
 * Serializes Strings to their unicode representation
 */
function unicodeLiterals($str) {
	$transTable = array();
	$utf8reg = '/[\xC0-\xDF][\x80-\xBF]|[\xE0-\xEF][\x80-\xBF][\x80-\xBF]/';
	if(!preg_match_all($utf8reg, $str, $matches))
		return $str;

	foreach($matches[0] as $utf8char) {
		if(isset($transTable[$utf8char])) {
			continue;
		}
		$transTable[$utf8char] = '\u'. substr('0000'. bin2hex(mb_convert_encoding($utf8char, 'UTF-16', 'UTF-8')), -4);
	}
	return strtr($str, $transTable);
}

function replacePredicate($p)
{
	//read predicate derivates
	$filename = "extractors/infobox/predicateDerivates.ini";
	$predicateArray = parse_ini_file( $filename );
	$predicateArrayKeys = array_keys($predicateArray);
	if (in_array($p, $predicateArrayKeys) )
		return $predicateArray[$p];
	return $p;
}

/**
 * Help function to check if a string is already urlencoded or not
 */
function isUrlEncoded($string) {
  return ereg("%([A-F0-9]{2})", $string) ;
}
