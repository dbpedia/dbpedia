<?php
/**
 * This file contains helper functions for datatype recognition.
 * These are called by parseAttributeValue in extractTemplates.php
 *
 */

/**
* Tests if the passed String is an Integer value with Comma
*
* If the passed String is an Integer value with Comma "true" will be returned
*
* @param	string	$x	Literaltext
* @return 	boolean
*/
function isIntwithComma ($x) {
   return preg_match('~(^[\-]?[0-9]{1,3}(,[0-9]{3})*)+$~',$x);
}

/**
* Tests if the passed String is a Float value
*
* If the passed String is a Float value "true" will be returned
*
* @param	string	$x	Literaltext
* @return 	boolean
*/
function isFloat ($x){
	return preg_match('~^[\-]?[0-9\,]+\.[0-9]+$~',$x);
}

/**
* Tests if the passed String is a Picture File
*
* If the passed String is a Picture File "true" will be returned
*
* @param	string	$x	Literaltext
* @return 	boolean
*/
function isPicture ($x) {
	global $pictureFilenames;
	if (preg_match('~^[A-Za-z0-9\-_ ]+\.('.implode('|',$pictureFilenames).')$~i',$x)) return true;
	if (preg_match('~^\[\[Image:([A-Za-z0-9\-_ ]+\.('.implode('|',$pictureFilenames).'))\|[0-9]+px\]\]$~i',$x)) return true;
}

/**
* Tests if the passed String is a Date Value
*
* If the passed String is a Date value "true" will be returned
*
* @param	string	$x	Literaltext
* @return 	boolean
*/
function isDate ($x) {
	global $dates;
	if (preg_match('~^\[\[('.implode('|',array_keys($dates)).') ([0-9]{1,2})\]\],? \[\[([0-9]{1,4})\]\]$~',$x)) return true;
	if (preg_match('~^\[\[([0-9]{1,4})-([0-9]{1,2})-([0-9]{1,2})\]\]$~',$x)) return true;
}

/**
* Tests if the passed String is a Rank like: 11<sup>th</sup>
*
* If the passed String is a Rank "true" will be returned
*
* @param	string	$x	Literaltext
* @return 	boolean
*/
function isRank ($x) {
	return preg_match('~^([0-9]+)<sup>th</sup>$~',$x);
}

/**
* Tests if the passed String is a large Number like: 12 million
*
* If the passed String is a large Number "true" will be returned
*
* @param	string	$x	Literaltext
* @return 	boolean
*/
function isLargeNumber ($x) {
	global $largeNumbers;
	return preg_match('~^([\-]?[0-9.]+) \[?\[?('.implode('|',array_keys($largeNumbers)).')\]?\]?$~i',$x);
}

/**
* Tests if the passed String is a large currency value, like: $12 million
*
* If the passed String is a large currency value "true" will be returned
*
* @param	string	$x	Literaltext
* @return 	boolean
*/
function isLargeMoney ($x) {
	global $largeNumbers;
	global $currency;
	return preg_match('~^('.str_replace('$','\$',implode('|',array_keys($currency))).')([0-9.]+) \[?\[?('.implode('|',array_keys($largeNumbers)).')\]?\]?$~i',$x);
}

/**
* Tests if the passed String is a currency value with Comma, like: $12,000,000
*
* If the passed String is a currency value with Comma "true" will be returned
*
* @param	string	$x	Literaltext
* @return 	boolean
*/
function isMoneyWoCent ($x) {
	global $currency;
	return preg_match('~^('.str_replace('$','\$',implode('|',array_keys($currency))).')([0-9]{1,3})(\,([0-9]{3}))*$~',$x);
}

/**
* Tests if the passed String is a currency value, like: $12.99
*
* If the passed String is a currency value "true" will be returned
*
* @param	string	$x	Literaltext
* @return 	boolean
*/
function isMoney ($x) {
	global $currency;
	return preg_match('~^('.str_replace('$','\$',implode('|',array_keys($currency))).')([0-9]+)\.([0-9]{1,3})$~',$x);
}

/**
* Tests if the passed String is a Wikipedia-Link-List
*
* If the passed String is a Wikipedia-Link-List, like: [[Actor A]] [[Actor B]], [[Actor C]],
* true will be returned
*
* @param	string	$x	Literaltext
* @return 	boolean
*/
 function isLinkList ($x,$p) {
	$z=in_array($p,$GLOBALS['linklistpredicates'])?2:3;
	return preg_match('~^(\[\[[^\]]+(\|[\]]*)*\]\][, \s]*){'.$z.',}$~',$x);
}

/**
* Tests if the passed String has a Unit, like: 12.30 g
*
* If the passed String has a Unit "true" will be returned
*
* @param	string	$x	Literaltext
* @return 	boolean
*/
function isUnited ($x) {
	global $units;
	return preg_match('~^([0-9]+(\.[0-9]+)?) ('.implode('|',array_keys($units)).')$~',$x);
}

/**
* Tests if the passed String is a percent value, like: 10.90%
*
* If the passed String is a percent value "true" will be returned
*
* @param	string	$x	Literaltext
* @return 	boolean
*/
function isPercent ($x) {
	return preg_match('~^([0-9]{1,3}(\.[0-9]+)?)%$~',$x);
}

/**
* Test for Integer
*
* Returns true if the passed value is an Integer Value
*
* @param	string	$x	Text to Test
* @return 	boolean	Returns true if no Integer value was found
*/
function isInt ($x) {
   return (is_numeric($x) ? intval($x) == $x : false);
}

/**
* Tests if the passed String is a List of Blanknotes
*
* The style of the passed String have to be like:
*  ;:bn1; ;:bn2; ...
*
* @param	string	$text	Text to Test
* @return 	boolean	Returns "true" if found a Blanknotelist
*/
function isBlanknoteList($text) {

	#return preg_match('~.+(;bn[0-9]*;){1,}~is', $text);
	return (preg_match('~.+(;bn[0-9]*;){1,}.*~is', $text)? true:preg_match('~.*(;bn[0-9]*;){1,}.+~is', $text) ? true: false);
}

/**
* Test passed value for URI
*
* If the passed value starts with http://
* then "true" will be returned
*
* @param	string	$text	text to test
* @return 	boolean	If found a URI, true will be returned
*/
function isLink($text) {
return preg_match('~^http://.*$~is', $text);
// return preg_match('^((\[?http://.*\]).*)+^', $text); // Edited by Piet
// return preg_match('/^(.*?)([\n\r] *==+[^\n\r=]*==+.*)?$/s',$text);

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
