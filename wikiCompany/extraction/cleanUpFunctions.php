<?php
/*
 * Created on 30.06.2007
 * 
 * This file contains functions for cleaning MediaWiki-Markup
 * and conversion of property names. 
 *
 */
 
 // Filename of Log-File for parsing-errors (items that could not be parsed, due to unknown WikiCode )
$GLOBALS['parseErrorLogFile'] = "errorlog.txt";

 
 /**
 * Converts Multi-word properties to CamelCase (e.g. "place_of_birth" => "placeOfBirth")
 * @param $predicate
 * @return $predicate
 */
function propertyToCamelCase($predicate) {
//	Start Consistent Property Names (CamelCase)
	$predicate = strtolower($predicate);
	$pSingleWords = preg_split("/_+|\s+|\-|:+/",$predicate);
	$predicate = $pSingleWords[0];
	for($i=1; $i < count($pSingleWords); $i++) {
		$predicate .= ucwords($pSingleWords[$i]);
	}
	// Replace digits at the beginning of a property with _. E.g. 01propertyName => _01propertyName (edited by Piet)
	if ( preg_match("/^([0-9]).*$/",$predicate) ) $predicate = "_" . $predicate;
	$predicate = str_replace('/','%2F',$predicate);
	return $predicate;
}

/**
 * @param object
 * @return true, if the object still contains WikiCode, but is not recognized as other data
 * writes errors to log file specified in $GLOBALS['parseErrorLogFile']
 */
function containsWikiCode($s, $p, $o) {
	$result = preg_match_all("/\[{1,3}[^\]]*\]{1,3}/",$o,$matches);
	if ($result) {
		// remove dbpedia-basedirectory from subject, predidacte (only used for formatting the logfile)
		$s = substr( $s,strlen($GLOBALS["W2RCFG"]['wikipediaBase']) );
		$p = substr( $p,strlen($GLOBALS["W2RCFG"]['propertyBase']) );
		// open LogFile
		$errorLog = fopen($GLOBALS['parseErrorLogFile'],"a+");
		if (!$errorLog) echo "\n Could not open logfile ($parseErrorLogFile)";
		// write error-message to logfile
		$time = date("D, M d Y - H:i:s T");
		fwrite($errorLog,"$time\t Subject: $s.\t Predicate: $p.\t Object: $o.\n");
		fclose($errorLog);
	}
	return $result;
}

// Removes remainig WikiMeida-Markup
function removeWikiCode (&$o) {
	
	// remove bold, italic, bold-italic
	$o = preg_replace("/'{2,5}/","",$o);
	// remove bullet-lists
	$o = preg_replace("/\*{1,2}|#{1,2}|\*#{1,2}/"," ",$o);
	// remove String containig only "(", ")", ",", " " (these Strings result from malparsed blanknodes)	
	$o = preg_replace("/^[\(\), ]+/","",$o);
	// Remove empty link tags
	$o = str_replace("[[]]","",$o);
	
}

 
