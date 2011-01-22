<?php
include("config.inc.php");

/**
 *
 *
 * @author	Paul Kreis <mail@paulkreis.de>
 *
 */
class NumberParser implements Parser
{
	const parserID = "http://dbpedia.org/parsers/NumberParser";

	public static function getParserID() {
		return self::parserID;
	}

	public static function parseValue($input, $language, $restrictions)
	{
		if (!isset($language)) $language = 'en';
		self::catchLargeNumbers($input);
		if ($restrictions[0] == 'integer') $output = self::parseIntegerValue($input, $language, $restrictions);
		if ($output != "") return $output;
		if ($restrictions[0] == 'float' || $restrictions[0] == 'double') $output = self::parseFloatValue($input, $language, $restrictions);
		if ($output != "") return $output;
	}

	/**
	* Convert shortened Numbers
	*
	* The value for Literaltext will be returned
	* eg. $12.53 million => $12530000
	*
	* @param	string	$input	the String with the shortened Number
	* @return 	string	$input	the String with the lengthened Number
	*/
	private static function catchLargeNumbers(&$input) {
		global $scale;
		if(preg_match('~^([\D]*)([0-9\.]+)\s?\[?\[?('.implode('|',array_keys($scale)).')\]?\]?(.*)$~i',$input,$matches)) {
			$num = $matches[2]*$scale[strtolower($matches[3])];
			// make sure that large numbers are presented as decimals, not E notation
			$num = number_format($num, 0, '.', '');
			$input = $matches[1].$num.$matches[4];
		}
		elseif(preg_match('~^([\D]*)([0-9\,]+)\s?\[?\[?('.implode('|',array_keys($scale)).')\]?\]?(.*)$~i',$input,$matches)) {
			$num = str_replace(',','.',$matches[2])*$scale[strtolower($matches[3])];
			// make sure that large numbers are presented as decimals, not E notation
			$num = number_format($num, 0, '.', '');
			$input = $matches[1].$num.$matches[4];
		}
	}

	private static function parseIntegerValue($input, $language, $restrictions) {
		if ($language == "en" || $language == "ja" || $language == "zh") {
			if(preg_match('~^[\D]*([0-9\,]+).*$~i',$input,$matches)) {
				$output = str_replace(',','',$matches[1]);
			}
		}
		else {
			if(preg_match('~^[\D]*([0-9\.]+).*$~i',$input,$matches)) {
				$output = str_replace('.','',$matches[1]);
			}
		}
		if (is_numeric($output)) $output = intval($output);
		else return null;
		if (is_integer($output)) return strval($output);
		else return null;

	}

	private static function parseFloatValue($input, $language, $restrictions) {
		if ($language == "en" || $language == "ja" || $language == "zh") {
			if(preg_match('~^[\D]*([0-9]+(\,[0-9]{3})*(\.[0-9]+)?).*$~',$input,$matches)) {
				$output = str_replace(',','',$matches[1]);
			}
		}
		/* Eugenio's hack for fr planet eccentricy property, now removed
		elseif ($language == "fr") {
			if(preg_match('~^[\D]*([0-9]+(\,[0-9]{3})*(\.[0-9]+)?).*$~',$input,$matches)) {
				$output = str_replace(',','',$matches[1]);
			}
		}
		*/
		else {
			if(preg_match('~^[\D]*([0-9]+(\.[0-9]{3})*(\,[0-9]+)?).*$~',$input,$matches)) {
				$output = str_replace(',','.',str_replace('.','',$matches[1]));
			}
		}
		if (is_numeric($output)) $output = floatval($output);
		else return null;
		if (is_float($output) || is_double($output)) return strval($output);
		else return null;
	}
}
