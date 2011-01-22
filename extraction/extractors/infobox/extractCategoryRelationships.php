<?php

// =============== WARNING ===============
// This file uses the global mysql connection.
// Do not use this code with other code that uses mysql.
// =============== WARNING ===============

if (!function_exists('fnmatch')) {
	function fnmatch($pattern, $string) {
		return @preg_match('/^' . strtr(addcslashes($pattern, '\\.+^$(){}=!<>|'), array('*' => '.*', '?' => '.?')) . '$/i', $string);
	}
}

// database configuration
$host = 'localhost';
$user = 'root';
$password = 'root';
$db = 'wikipedia_en';

//csv or nt
$outputFormat='nt';
$filename='categoryRel.'.$outputFormat;
$modelID='1';
$categoryLabel='Category';
$W2RCFG=array(
    //printed Categories, leave empty for all
    categories => array(),
    //printed Categories matching wildcard Pattern, leave empty for all
    categoriesPattern => array(),
	//Wikipedia Base URI
	wikipediaBase => 'http://en.wikipedia.org/wiki/',
	// Property used to link categories to categories
	subCategoryProperty=>'http://www.w3.org/1999/02/22-rdf-syntax-ns#type',
);

error_reporting(E_ALL & ~E_NOTICE);

mysql_connect($host,$user,$password);
mysql_select_db($db);
extractCategoryRelationships();


function decodeLocalName($string) {
	return urldecode(str_replace("_"," ",trim($string)));
}
/**
* Tests if the passed String is a Blanknote
*
* The style of the passed String have to be like:
*  ;:bn1;
*
* @param	string	$text	Text to Test
* @return 	boolean	Returns "true" if found a Blanknote
*/
function isBlanknote($text) {
	return preg_match('~^[;]?bn[0-9]*[;]?$~',$text);
}
function writeTripel($subject,$predicate,$object,$object_is='r',$dtype=NULL,$lang=NULL) {
	static $afp,$last,$j,$tc,$start;
	if(!$start)
		$start=microtime(true);
	if(!$fp[$GLOBALS['filename']])
		$fp[$GLOBALS['filename']]=fopen($GLOBALS['filename'],'a');
	if($GLOBALS['outputFormat']!='csv') {
		fwrite($fp[$GLOBALS['filename']],(isBlanknote($subject)?"_:$subject ":"<$subject>\t")."<$predicate>\t".
			($object_is=='r'?'<'.$object.'>':
				($object_is=='b'?'_:'.$object:"\"".str_replace(array("\n","\r","\r\n","\"","\\"),array('\n','\r','\r\n','\'\'',' '),$object)."\"".($lang?"@$lang":'').($dtype?"^^<$dtype>":'')))." .\n");
	} else
		fwrite($fp[$GLOBALS['filename']],$GLOBALS['modelID']."\t$subject\t$predicate\t".str_replace(array('\\',"\n","\r","\r\n"),array('\\\\','\n','\r','\r\n'),$object)."\t$lang\t$dtype\t".(isBlanknote($subject)?'b':'r')."\t$object_is\t\\N\n");
	fclose($fp[$GLOBALS['filename']]);
	$tc++;
	if(++$j%10000==0) {
		echo "10000 tripel written in ".round(microtime(true)-$last,2)."s (".round($tc/(microtime(true)-$start))." tripel/s)\n";
		$last=microtime(true);
	}
}


function extractCategoryRelationships() {
	$res=mysql_query('SELECT page_title,cl_to FROM page INNER JOIN categorylinks ON(page_id=cl_from) WHERE page_namespace=14');
	while($row=mysql_fetch_array($res)) {
		if ((empty($GLOBALS['W2RCFG']['categories'])&&empty($GLOBALS['W2RCFG']['categoriesPattern']))||preg_match('~^'.implode($GLOBALS['W2RCFG']['categories'],'|').'$~i',decodeLocalName($row[0]))||fnmatch(implode($GLOBALS['W2RCFG']['categoriesPattern'],'|'),decodeLocalName($row[0])))
			writeTripel($GLOBALS['W2RCFG']['wikipediaBase'].$GLOBALS['categoryLabel'].':'.$row[0],$GLOBALS['W2RCFG']['subCategoryProperty'],$GLOBALS['W2RCFG']['wikipediaBase'].$GLOBALS['categoryLabel'].':'.$row[1]);
	}
}


