<?php
/**
 * the GeoParser parse strings for GeoCoordinates
 * and returns them as decimal number.
 *
 * @author	Paul Kreis <mail@paulkreis.de>
 *
 */
class GeoParser implements Parser
{
	const parserID = "http://dbpedia.org/parsers/GeoParser";

	public static function getParserID() {
		return self::parserID;
	}

	public static function parseValue($input, $language, $restrictions)
	{
		if (!isset($language)) $language = 'en';
		$output = self::parseGeoCoord($input);
		return $output;
	}
	/**
	 * transforms DMS-format Degrees:Minutes:Seconds (49�30'02"N, 123�30'30"W)
	 * to the DD-format Decimal Degrees (49.50055556,-123.50833333),  with 8 decimal numbers.
	 *
	 * @param string $latsec
	 * @param string $latmin
	 * @param string $latdeg
	 * @param string $latHemisphere
	 * @param string $longsec
	 * @param string $longmin
	 * @param string $longdeg
	 * @param string $longHemisphere
	 * @return array [georss]=>'52.51666667 13.41666667',[lat]=>'52.51666666',[long]=>'13.41666667'
	 */
	private static function createOutput ($latsec,$latmin,$latdeg,$latHemisphere,$longsec,$longmin,$longdeg,$longHemisphere) {
		$lat = $latdeg + ($latmin + $latsec/60)/60;
		$long = $longdeg + ($longmin + $longsec/60)/60;

		$lat = number_format($lat,8,'.','');
		$long = number_format($long,8,'.','');

		if ($latHemisphere == 'S') {
			$lat = $lat * -1;
		}
		if ($longHemisphere == 'W') {
			$long = $long * -1;
		}
		$lat = number_format($lat,8,'.','');
		$long = number_format($long,8,'.','');
		$georss = $lat.' '.$long;

		return array('georss'=>$georss,'lat'=>$lat,'long'=>$long);
	}

	/**
	 * catches formats like:
	 *  - {{coord|51|30|29|N|00|07|29|W|display=title}}
	 *  - {{coord|44.112|N|87.913|W|display=title}}
	 *  - {{coord|44.112|-87.913|display=title}}
	 *  - {{coord|59|21|N|18|04|E
	 *  - 38º32' N 2º89' W
	 *
	 * @param	string	$input	Literaltext, that matched to be a GeoCoordinat
	 * @return	array	'georss'=>'52.51666667 13.41666667','lat'=>'52.51666666','long'=>'13.41666667'
	 *
	 */
	private static function parseGeoCoord($input) {

	    // {{coord|51|30|29|N|00|07|29|W|display=title}}
	    if (preg_match('~^\{\{coord\|([0-9]{1,2})\|([0-9]{1,2})\|([.0-9]{1,8})?\|?(N|S)\|([0-9]{1,3})\|([0-9]{1,2})\|([.0-9]{1,8})?\|?(E|W)(\|.*)?\}?\}?$~',$input,$matches)) {
			return self::createOutput($matches[3],$matches[2],$matches[1],$matches[4],$matches[7],$matches[6],$matches[5],$matches[8]);
		}
		// {{coord|44.112|N|87.913|W|display=title}}
		elseif (preg_match('~^\{\{coord\|([.0-9]{1,8})\|([N|S])\|([.0-9]{1,8})\|(W|E)(\|.*)?\}?\}?$~',$input,$matches)) {
			return self::createOutput(0,0,$matches[1],$matches[2],0,0,$matches[3],$matches[4]);
		}
		// {{coord|44.112|-87.913|display=title}}
		elseif (preg_match('~^\{\{coord\|([-.0-9]{1,8})\|([-.0-9]{1,8})(\|.*)?\}?\}?$~',$input,$matches)) {
			return self::createOutput(0,0,$matches[1],'nothing',0,0,$matches[2],'nothing');
		}
		// 38º32' N 2º89' W
		elseif (preg_match('~^([0-9]{1,2})º([0-9]{1,2})\'([0-9]{1,2}(\.[0-9]{1,2})?)?\"?[\s]?(N|S)[\s]([0-9]{1,3})º([0-9]{1,2})\'([0-9]{1,2}(\.[0-9]{1,2})?)?\"?[\s]?(E|W)$~',$input,$matches)) {
			return self::createOutput($matches[3],$matches[2],$matches[1],$matches[5],$matches[8],$matches[7],$matches[6],$matches[10]);
		}
	}
}

