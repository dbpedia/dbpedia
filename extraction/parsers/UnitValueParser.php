<?php
include("config.inc.php");

/**
 * the UnitValueParser parse strings for units
 * and returns the number and the unit in a array.
 *
 * @author	Paul Kreis <mail@paulkreis.de>
 *
 */
class UnitValueParser implements Parser
{
    const parserID = "http://dbpedia.org/parsers/UnitValueParser";

    public static function getParserID() {
        return self::parserID;
    }

    /**
     * parseValue will parse a string and returns an array that includes
     * the results in an subarray with the value at offset[0],
     * the unit at offset[1] and the abbreviation at offset[2]
     *
     * TODO: use parameters instead of $restrictions array
     * 
     * @param string $input
     * @param string $language
     * @param array $restrictions PAGEID=>pageID, PROPERTYNAME=>propertyName, UNITTYPE=>unitType, UNITEXACTTYPE=>unitExactType, TARGETUNIT=>targetUnit
     * @return array
     */
    public static function parseValue($input, $language='en', $restrictions) {
    	$pageID = $restrictions[PAGEID];
        $propName = $restrictions[PROPERTYNAME];
        $unitType = $restrictions[UNITTYPE];
        $unitExactType = $restrictions[UNITEXACTTYPE];
        $targetUnit = $restrictions[TARGETUNIT];
        
        if (isset($restrictions[IGNOREUNIT]) && $restrictions[IGNOREUNIT]) $ignoreUnit = true;
        else $ignoreUnit = false;
        
        $originalInput = $input;
        self::catchLargeNumbers($input);
        $output = self::catchUnited($pageID, $input, $language, $unitType, $unitExactType, $targetUnit, $ignoreUnit);
        if ($output != null) return $output;
        $output = self::catchPercent($pageID, $input, $language, $unitType, $unitExactType, $targetUnit, $ignoreUnit);
        if ($output != null) return $output;
        $output = self::catchMoney($pageID, $input, $language, $unitType, $unitExactType, $targetUnit, $ignoreUnit);
        if ($output != null) return $output;
        $outputTime[] = self::catchTime($pageID, $input, $language, $propName, $unitType, $unitExactType, $targetUnit, $ignoreUnit, $originalInput);
        if ($outputTime[0][0] != null) return $outputTime;
        $outputNum[] = self::catchNumber($pageID, $input, $language, $propName, $unitType, $unitExactType, $targetUnit, $ignoreUnit, $originalInput);
        if ($outputNum[0] != null) return $outputNum;
        else return null;

    }

    /**
     * Make string's URI conform
     *
     * @param string $str
     * @return string the resulting string.
    */
    private static function uriCleaner($string)
    {
        if(false === function_exists('lcfirst')) {
            /**
		    * Make a string's first character lowercase
		    *
		    * @param string $str
		    * @return string the resulting string.
		    */
            function lcfirst( $str ) {
                $str[0] = strtolower($str[0]);
                return (string)$str;
            }
        }
        return str_replace(" ", "", lcfirst(ucwords(strtolower(trim($string)))));
    }

    /**
     * Creates the output array, the number at key 0, the uri of the unit at key 1
     * and the abbreviation of the unit at key 2.
     * Eliminates the thousand separator.
     *
     * @param string $inputNum
     * @param string $inputUnit
     * @param string $language
     * @param string $abbreviation
     * @param bool $untouched optinal, if true no separators will be eliminated
     * @return array
     */
    private static function outputGenerator($value, $unit, $language, $unitType, $unitExactType, $targetUnit, $ignoreUnit)
    {
        global $units;
        if ($unit != null && $unit != '')
        {
            if ($language == "en" || $language == "ja" || $language == "zh"){
                $v = str_replace(',','',$value);
            }
            else {
                $v = str_replace(',','.',str_replace('.','',$value));
            }

            if ($unit == 'noUnit' || $ignoreUnit === true) {
                return $output = array(self::formatNumber($v) ,null,null);
            } else {

                // make sure that the datatype is not a string
                $v = $v*1;

                if ($unit == 'noUnit') return $output = array(self::formatNumber($v) ,null,null);

                // convert to target unit
                $converted = self::convert($v, $unit, $targetUnit, $unitType);
                // formate decimals

                $number = self::formatNumber($converted[0]);

                $unitUri = $GLOBALS['W2RCFG']['w2ruri'].self::uriCleaner($converted[1]);

                $abbriviations = array_keys($units,$converted[1]);
                $unitAbbr = $abbriviations[0];

                return $output = array($number,$unitUri,$unitAbbr);
            }
        }
    }

    private static function convert($value, $fromUnit, $toUnit, $unitType)
    {
        global $units;
        global $conversionFactor;
        if ($toUnit == null && $unitType == null) {
            return $output = array($value, $fromUnit);
        }
        elseif ($toUnit != null && $unitType!=  null) {
        	// WARNING - supressing warnings 
            @$unitTypeStandard = $GLOBALS[$unitType]['STANDARD_UNIT'];
            //if ($conversionFactor[$fromUnit][$unitTypeStandard] == null) {
            if (!isset($conversionFactor[$fromUnit][$unitTypeStandard])) {
                return $output = array($value, $fromUnit);
            }
            else {
                $newValue = $value * $conversionFactor[$fromUnit][$unitTypeStandard];
                $newValue = $newValue * $conversionFactor[$unitTypeStandard][$toUnit];
                return $output = array($newValue, $toUnit);
            }
        }
        elseif ($unitType == 'Length' || $unitType == 'Area' || $unitType == 'Volume' || $unitType == 'Speed' || $unitType == 'Force' || $unitType == 'Mass' || $unitType == 'Power') {
            $unitTypeStandard = $GLOBALS[$unitType]['STANDARD_UNIT'];
            //if ($conversionFactor[$fromUnit][$unitTypeStandard] != '') {
            if (isset($conversionFactor[$fromUnit][$unitTypeStandard])) {
                $newValue = $value * $conversionFactor[$fromUnit][$unitTypeStandard];
                return $output = array($newValue, $unitTypeStandard);
            }
            return $output = array($value, $fromUnit);
        }
        else {
            return $output = array($value, $fromUnit);
        }

    }

    /**
    * Formats a number
    *
    * @param mixed $number
    * @return int/float
    */
    public static function formatNumber($number) {
        if ($number >= 1000) {
            return $number = number_format($number,0,'.','');
        }
        else {
            $strNumber = ''.$number;
            if (preg_match('~E-([0-9]{1,3})$~', $strNumber, $match)) {
                $ex = $match[1]*1;
                return $number = number_format($number,$ex+3,'.','');
            }
            $v = explode('.',$number);
            if (count($v) == 2) {
                $x = 0;
                if ($v[0] == 0) {
                    $strNum = ''.$v[1];
                    while ($x < strlen($strNum) && $strNum[$x] == 0) {
                        $x++;
                    }
                }
                return $number = number_format($number,$x + 4,'.','');
            }
            else return number_format($number,0,'.','');
        }
    }

    /**
	* escapes characters that would otherwise be interpreted as a meta-character in the regexp
    *
    * @param string $regex
    * @return string
    */
    private static function regexCleaner($regex) {
        $regex = str_replace('\\','\\\\',$regex);
        $regex = str_replace('^','\^',$regex);
        $regex = str_replace('$','\$',$regex);
        return $regex;
    }

    /**
	* Convert shortened Numbers
	*
	* The value for Literaltext will be returned
	* eg. $12.53 million => $12530000
	* 	  25,123.5 mio => 25123500000
	*
	* @param	string	$input	the String with the shortened Number
	* @return 	string	$input	the String with the lengthened Number
	*/
    private static function catchLargeNumbers(&$input) {
        global $scale;
        if(preg_match('~^([\D]*)([0-9]+(\,[0-9]{3})*(\.[0-9]+)?)\s?\[?\[?('.implode('|',array_keys($scale)).')\]?\]?(.*)$~i',$input,$matches)) {
            $num = str_replace(',','',$matches[2])*$scale[strtolower($matches[5])];
            // make sure that large numbers are presented as decimals, not E notation
            $num = number_format($num, 0, '.', '');
            $input = $matches[1].$num.$matches[6];
        }
        elseif(preg_match('~^([\D]*)([0-9\,]+)\s?\[?\[?('.implode('|',array_keys($scale)).')\]?\]?(.*)$~i',$input,$matches)) {
            $num = str_replace(',','.',$matches[2]) * $scale[strtolower($matches[3])];
            // make sure that large numbers are presented as decimals, not E notation
            $num = number_format($num, 0, '.', '');
            $input = $matches[1].$num.$matches[4];
        }
    }

    /**
	* Returns unit and value for an Object
	* string with feet and inches will be converted in centimetre
	* 1 in = 2.54 cm
	* 1 ft = 30.48 cm
	*
	* The value and Unit of the passed value will be returned in an Array
	*
	* @param	string	$input	Literaltext
	* @return 	array	the value at offset[0], the Unit at offset[1] and the abbreviation at offset[2]
	*/
    private static function catchUnited($pageID, $input, $language, $unitType, $unitExactType, $targetUnit, $ignoreUnit) {
        if ($unitType != 'Time' && $unitType != 'Currency')
        {
            global $units;
            $unitKeys = self::regexCleaner(join('|',array_keys($units)));

            $output = array();

            // english, japanese and chinese wikepedia artikels
            // numbers with a . as decimal separator and a , as thousand separator
            if ($language == "en" || $language == "ja" || $language == "zh")
            {
                // Merging strings with feet and inches: 'x ft y in'
                // and convert them into centmetres
                if (preg_match('~([0-9]+)\040?ft\040?([0-9]+)\040?in~',$input,$matches)) {
                    // convert to cenitmeter
                    $ft_to_cm = $matches[1]*30.48;
                    $in_to_cm = $matches[2]*2.54;
                    $cm = $ft_to_cm + $in_to_cm;
                    $output[] = self::outputGenerator($cm, 'centimetre', $language, $unitType, $unitExactType, $targetUnit, $ignoreUnit);
                }
                elseif(preg_match('~^[\D]*([0-9]+(\,[0-9]{3})*(\.[0-9]+)?)(&nbsp;)?\040?\(?\[?\[?('.$unitKeys.')(?!/)(?!\\\)(?!\w).*$~',$input,$matches)) {
                    $output[] = self::outputGenerator($matches[1],$units[$matches[5]],$language, $unitType, $unitExactType, $targetUnit, $ignoreUnit);
                }
                // If different units are present, e.g.: 10 mi. (16.0934 km); the first will be returned
                elseif(preg_match('~^[\D]*([0-9]+(\,[0-9]{3})*(\.[0-9]+)?)(&nbsp;)?\040?\(?\[?\[?('.$unitKeys.')[\s]*\([\s]*([0-9]+(\.[0-9]+)?)[\s]*('.$unitKeys.')[\s]*\)[\s]*$~',$input,$matches)) {
                    $output[] = self::outputGenerator($matches[1],$units[$matches[5]],$language, $unitType, $unitExactType, $targetUnit, $ignoreUnit);
                }
                // http://en.wikipedia.org/wiki/Template:Height
                elseif (preg_match('~^.*?\{\{height\|('.$unitKeys.')=([0-9]+(\,[0-9]{3})*(\.[0-9]+)?)(\|('.$unitKeys.')=([0-9]+(\,[0-9]{3})*(\.[0-9]+)?))?.*\}\}.*$~',$input,$matches)) {
                    $converted = false;
                    $matches[2] = str_replace(',','',$matches[2]);
                    $matches[7] = str_replace(',','',$matches[7]);
                    // convert to cenitmeter
                    if ($matches[1] == 'ft') { $matches[2] = $matches[2]*30.48; $converted = true; }
                    if ($matches[1] == 'in') { $matches[2] = $matches[2]*2.54; $converted = true; }
                    if ($matches[1] == 'yd') { $matches[2] = $matches[2]*91.44; $converted = true; }
                    if ($matches[6] == 'ft' && $converted) $matches[7] = $matches[7]*30.48;
                    if ($matches[6] == 'in' && $converted) $matches[7] = $matches[7]*2.54;
                    if ($matches[6] == 'yd' && $converted) $matches[7] = $matches[7]*91.44;
                    if ($converted === true) {
                        $output[] = self::outputGenerator($matches[2] + $matches[7],'centimetre', $language, $unitType, $unitExactType, $targetUnit, $ignoreUnit);
                    }
                    else {
                        $output[] = self::outputGenerator($matches[2] + $matches[7],$units[$matches[1]], $language, $unitType, $unitExactType, $targetUnit, $ignoreUnit);
                    }
                }
                // http://en.wikipedia.org/wiki/Template:Convert
                // http://zh.wikipedia.org/wiki/Template:Convert
                elseif (preg_match('~^.*?\{\{\s*[Cc]onvert\s*\|\s*([0-9]+(\,[0-9]{3})*(\.[0-9]+)?)\s*\|\s*('.$unitKeys.')(?!/)(?!\\\)(?!\w).*$~',$input,$matches)) {
                    $output[] = self::outputGenerator($matches[1],$units[$matches[4]],$language, $unitType, $unitExactType, $targetUnit, $ignoreUnit);
                }
                // http://en.wikipedia.org/wiki/Template:Km_to_mi
                // http://en.wikipedia.org/wiki/Template:Km2_to_mi2
                // http://en.wikipedia.org/wiki/Template:Pop_density_km2_to_mi2
                // etc.
                elseif (preg_match('~^.*?\{\{([Pp]op\040density\040)?('.$unitKeys.')\040to\040[^.\|]+\|\s*([0-9]+(\,[0-9]{3})*(\.[0-9]+)?)\s*\|.*$~',$input,$matches)) {
                    $output[] = self::outputGenerator($matches[3],$units[$matches[2]],$language, $unitType, $unitExactType, $targetUnit, $ignoreUnit);
                }
                // http://en.wikipedia.org/wiki/Template:Auto_in
                // http://en.wikipedia.org/wiki/Template:Auto_kg
                // etc.
                // 1996-98: {{Auto in|50.7|0}}1999-2002 Roadster: {{Auto in|50.9|0}}Coupe: {{Auto in|51.4|0}}
                elseif (preg_match_all('~\{\{Auto\040('.$unitKeys.')\|([0-9]+(\,[0-9]{3})*(\.[0-9]+)?)(\|[0-9])?\}\}~',$input,$matches)) {
                    $x = 0;
                    foreach ($matches[0] as $match) {
                        $output[] = self::outputGenerator($matches[2][$x],$units[$matches[1][$x]],$language, $unitType, $unitExactType, $targetUnit, $ignoreUnit);
                        $x++;
                    }
                }
                // catch numbers and unit: e.q. 1,120,500.55 Kilometer
                elseif(preg_match('~^[\D]*([0-9]+(\,[0-9]{3})*(\.[0-9]+)?)\040?\(?\[?\[?('.join('|',$units).')(?!\w).*$~i',$input,$matches)) {
                    $output[] = self::outputGenerator($matches[1],$matches[4],$language, $unitType, $unitExactType, $targetUnit, $ignoreUnit);
                }
                // If different units are present, e.g.: 10 miles (16.0934 kilometer); the first will be returned
                elseif(preg_match('~^[\D]*([0-9]+(\,[0-9]{3})*(\.[0-9]+)?)(&nbsp;)?\040?\(?\[?\[?('.join('|',$units).')[\s]*\([\s]*([0-9]+(\.[0-9]+)?)[\s]*('.join('|',$units).')[\s]*\)[\s]*$~i',$input,$matches)) {
                    $output[] = self::outputGenerator($matches[1],$matches[5],$language, $unitType, $unitExactType, $targetUnit, $ignoreUnit, $ignoreUnit);
                }
                elseif (array_key_exists(0, $output)) {
                    if ((($output[0] == null) || ($output[0] == "")) && (preg_match('~^[\D]*([0-9]+(\.[0-9]{3})*(\,[0-9]+)?)\040?\(?\[?\[?('.$unitKeys.')(?!\\\)(?!\w).*$~',$input,$matches))) {
                        $language = "de";
                        $output[] = self::outputGenerator($matches[1],$units[$matches[4]],$language, $unitType, $unitExactType, $targetUnit, $ignoreUnit);
                    }
                }
            }

            // for wikipedia artikels in german, french, italian, spanish ...
            // numbers with a , as decimal separator and a . as thousand separator
            else {
                // catch number and unit: e.q. 1.120.500,55 km
                if(preg_match('~^[\D]*([0-9]+(\.[0-9]{3})*(\,[0-9]+)?)\040?\(?\[?\[?('.$unitKeys.')(?!/)(?!\\\)(?!\w).*$~',$input,$matches)) {
                    $output[] = self::outputGenerator($matches[1],$units[$matches[4]],$language, $unitType, $unitExactType, $targetUnit, $ignoreUnit);
                }
                // If different units are present, e.g.: 10 mi. (16.0934 km); the first will be returned
                elseif(preg_match('~^[\D]*([0-9]+(\.[0-9]{3})*(\,[0-9]+)?)\040?\(?\[?\[?('.$unitKeys.')[\s]*\([\s]*([0-9]+(\,[0-9]+)?)[\s]*('.join('|',$units).')[\s]*\)[\s]*$~',$input,$matches)) {
                    $output[] = self::outputGenerator($matches[1],$units[$matches[4]],$language, $unitType, $unitExactType, $targetUnit, $ignoreUnit);
                }
                // http://en.wikipedia.org/wiki/Template:Convert
                // http://es.wikipedia.org/wiki/Plantilla:Convert
                // http://it.wikipedia.org/wiki/Template:Converti
                // http://no.wikipedia.org/wiki/Mal:Convert
                elseif (preg_match('~^.*?\{\{\s*[Cc]onvert(i)?\s*\|\s*([0-9]+(\.[0-9]{3})*(\,[0-9]+)?)\s*\|\s*('.$unitKeys.')(?!/)(?!\\\)(?!\w).*$~',$input,$matches)) {
                    $output[] = self::outputGenerator($matches[2],$units[$matches[5]],$language, $unitType, $unitExactType, $targetUnit, $ignoreUnit);
                }
                // catch number and unit: e.q. 1.120.500,55 Kilometer
                elseif(preg_match('~^[\D]*([0-9]+(\.[0-9]{3})*(\,[0-9]+)?)\040?\(?\[?\[?('.join('|',$units).')(?!\w).*$~i',$input,$matches)) {
                    $output[] = self::outputGenerator($matches[1],$matches[4],$language, $unitType, $unitExactType, $targetUnit, $ignoreUnit);
                }
                // If different units are present, e.g.: 10 miles (16,0934 kilometer); the first will be returned
                elseif(preg_match('~^[\D]*([0-9]+(\.[0-9]{3})*(\,[0-9]+)?)\040?\(?\[?\[?('.join('|',$units).')[\s]*\([\s]*([0-9]+(\,[0-9]+)?)[\s]*('.join('|',$units).')[\s]*\)[\s]*$~i',$input,$matches)) {
                    $output[] = self::outputGenerator($matches[1],$matches[4],$language, $unitType, $unitExactType, $targetUnit, $ignoreUnit);
                }
                elseif (array_key_exists(0, $output)) {
                    if ((($output[0] == null) || ($output[0] == "")) && (preg_match('~^[\D]*([0-9]+(\,[0-9]{3})*(\.[0-9]+)?)\040?\(?\[?\[?('.$unitKeys.')(?!\\\)(?!\w).*$~',$input,$matches))) {
                        $language = "en";
                        $output[] = self::outputGenerator($matches[1],$units[$matches[4]],$language, $unitType, $unitExactType, $targetUnit, $ignoreUnit);
                    }
                }
            }
            return $output;
        }
    }

    /**
	* Returns percent value
	*
	* The value of the passed String that is an percent value will be returned
	*
	* @param	string	$input	Literaltext, that matched a to be a percent value
	* @return 	float	the percent value
	*/
    private static function catchPercent($pageID, $input, $language, $unitType, $unitExactType, $targetUnit, $ignoreUnit)
    {
        if ($unitType != 'Currency' && $unitType != 'Time')
        if ($language == "en" || $language == "ja" || $language == "zh")
        {
            if(preg_match('~(-?[0-9]{1,3}(\.[0-9]+)?)\040?%~',$input,$matches)) {
                $outp = str_replace(',','.', $matches[1]);
                $output[0][0] = $outp/100;
                return $output;
            }
        }
        else {
            if(preg_match('~(-?[0-9]{1,3}(,[0-9]+)?)\040?%~',$input,$matches)) {
                $outp = str_replace(',','.', $matches[1]);
                $output[0][0] = $outp/100;
                return $output;
            }
        }
    }

    /**
	* Returns duration in the PnYnMnDTnHnMnS format
	*
	* @param	string	$input	Literaltext, that matched a to be a time
	* @return 	array	time at offset[0] and unit at offset[1] and the abbreviation at offset[2]
	*/
    private static function catchTime($pageID, $input, $language, $propName, $unitType, $unitExactType, $targetUnit, $ignoreUnit, $originalInput)
    {
        if ($unitType == "Time" || $unitType == null)
        {
            global $Time;

            if (preg_match('~^[\D]*?([\-]?[0-9]+)\040?('.join('|',array_keys($Time)).')\040?[,]?\040?([0-9]+)\040?('.join('|',array_keys($Time)).')[\D]*$~i',$input,$matches)) {
                if ($Time[$matches[2]] == 'minute') {
                    //$output[0] = '00:'.$matches[1].':'.$matches[3];
                    $output[0] = 'PT'.$matches[1].'M'.$matches[3].'S';
                }
                elseif ($Time[$matches[2]] == 'hour') {
                    //$output[0] = $matches[1].':'.$matches[3].':00';
                    $output[0] = 'PT'.$matches[1].'H'.$matches[3].'M';
                }
                $output[1] = 'http://www.w3.org/2001/XMLSchema#duration';
                $output[2] = null;

                return $output;
            }

            if (preg_match('~([0-9]+)([\.,])([0-9]+)\040?([d|days|day])?~i',$input,$matches)) {
                if ($unitExactType == 'day' || $matches[4] != null) {
                    $hCheck = false;
                    $mCheck = false;
                    if ($matches[3] != null) {
                        $h = '0.'.$matches[3];
                        $h = doubleval($h);
                        $hours = 24*$h;
                        $hCheck = true;
                        if (preg_match('~([0-9]+)\.([0-9]+)~',$hours, $match)) {
                            $m = '0.'.$match[2];
                            $m = doubleval($m);
                            $min = 60*$m;
                            $min = number_format($min,0).'M';
                            $hours = $match[1].'H';
                            $mCheck = true;
                        }
                    }
                    $output[0] = 'P'.$matches[1].'D';
                    if ($hCheck) $output[0] = $output[0].'T'.$hours;
                    if ($mCheck) $output[0] = $output[0].$min;
                    $output[1] = 'http://www.w3.org/2001/XMLSchema#duration';
                    $output[2] = null;
                    return $output;
                }
            }

            if (preg_match('~(-)?([0-9]+)(\:[0-9]{2})?(\:[0-9]{2})?\040?('.join('|',array_keys($Time)).')?(?!\w)~i',$input,$matches)) {

                $colonCount = substr_count($matches[0],':');

                $matches5 = "";
                if(isset($matches[5]))
                	$matches5 = $matches[5];
                
                if ($unitExactType != null || $Time[strtolower($matches5)] != null) {

                    if (isset($Time[strtolower($matches5)]))
                    	$unit = $Time[strtolower($matches5)];
                    
                    if ($unitExactType != null) {
                        if (!in_array($unitExactType, array_values($Time))) {
                            $unit = $Time[$unitExactType];
                            if ($unit == null) Util::writeLogMsg($pageID, 'UnitValueParser', $language, $propName, $originalInput, 'Could not find unitExactType "'.$unitExactType.'" in GLOBAL[Time] !');
                        } else {
                            $unit = $unitExactType;
                        }
                    }

                    switch ($unit) {
                        case 'second':
                            if ($colonCount == 0) {
                                $output[0] = 'PT'.$matches[2].'S';
                            }
                            break;

                        case 'minute':
                            if ($colonCount == 0) {
                                $output[0] = 'PT'.$matches[2].'M';
                            } elseif ($colonCount == 1) {
                                $output[0] = 'PT'.$matches[2].'M'.substr($matches[3],1).'S';
                            }
                            break;

                        case 'hour':
                            if ($colonCount == 0) {
                                $output[0] = 'PT'.$matches[2].'H';
                            } elseif ($colonCount == 1) {
                                $output[0] = 'PT'.$matches[2].'H'.substr($matches[3],1).'M';
                            } elseif ($colonCount == 2) {
                                $output[0] = 'PT'.$matches[2].'H'.substr($matches[3],1).'M'.substr($matches[4],1).'S';
                            }
                            break;
                        case 'day':
                            if ($colonCount == 0) {
                                $output[0] = 'P'.$matches[2].'D';
                            }
                            break;
                    }
                }
                else {
                    if ($colonCount == 2) {
                        $output[0] = 'PT'.$matches[2].'H'.$matches[3].'M'.$matches[4].'S';
                    }
                }
                if ($matches[1] == '-') $output[0] = '-'.$output[0];
                $output[1] = 'http://www.w3.org/2001/XMLSchema#duration';
                $output[2] = null;

                if ($output[0] == '' || $output[0] == '-' ||$output[0] == null) return array(null, null, null);
                else return $output;
            }
        }
    }


    /**
	* Returns currency and value
	*
	* The currency and value for Literaltext will be returned
	* eg. $12.99 => [0]12.99 [1]Dollar
	*     12,000$ => [0]12000 [1]Dollar
	*
	* @param	string	$input	Literaltext, that matched to be a currency value
	* @return 	array	the value at offset[0], the currency at Offset[1] and the abbreviation at offset[2]
	*/
    private static function catchMoney($pageID, $input, $language, $unitType, $unitExactType, $targetUnit, $ignoreUnit)
    {
        if ($unitType == "Currency" || $unitType == null)
        {
            global $scale;
            global $Currency;
            $currencys = self::regexCleaner(join('|',array_keys($Currency)));

            // numbers with a . as decimal seperator and a , as thousand seperator
            if ($language == "en" || $language == "ja" || $language == "zh"){
                // 51.218 [[1,000,000,000 (number)|billion]] [[USD]] (2007)
                if(preg_match('~^[\D]*([\-]?[0-9]+(\,[0-9]{3})*(\.[0-9]+)?)\s?\[\[[1,0]+\s\(number\)\|('.implode('|',array_keys($scale)).')\]\]\s\[\[('.$currencys.')\]\].*$~',$input,$matches)) {
                    $num = str_replace(',','',$matches[1])*$scale[strtolower($matches[4])];
                    $num = number_format($num, 0, '.', '');
                    $output[] = self::outputGenerator($num,$Currency[$matches[5]],$language, $unitType, $unitExactType, $targetUnit, $ignoreUnit);
                    return $output;
                }
                // [[Euro|€]]7.682 [[1000000000 (number)|billion]] (2007)

                elseif(preg_match('~^[\D]*\[\[[\D]+\|('.$currencys.')\]\]\s?([\-]?[0-9]+(\,[0-9]{3})*(\.[0-9]+)?)\s?\[\[[1,0]+\s\(number\)\|('.implode('|',array_keys($scale)).')\]\].*$~',$input,$matches)) {
                    $num = str_replace(',','',$matches[2])*$scale[strtolower($matches[5])];
                    $num = number_format($num, 0, '.', '');
                    $output[] = self::outputGenerator($num,$Currency[$matches[1]],$language, $unitType, $unitExactType, $targetUnit, $ignoreUnit);
                    return $output;
                }
                // 10$
                elseif(preg_match('~^[\D]*([\-]?[0-9]+(\,[0-9]{3})*(\.[0-9]+)?)\040?\[?\[?('.$currencys.').*$~',$input,$matches)) {
                    $output[] = self::outputGenerator($matches[1],$Currency[$matches[4]],$language, $unitType, $unitExactType, $targetUnit, $ignoreUnit);
                    return $output;
                }
                // {{profit}} 358600000 [[United States dollar|USD]] (2006)
                elseif(preg_match('~^[\D]*([\-]?[0-9]+(\,[0-9]{3})*(\.[0-9]+)?)\s*?\[?\[?[\w\s]*\|('.$currencys.').*$~',$input,$matches)) {
                    $output[] = self::outputGenerator($matches[1],$Currency[$matches[4]],$language, $unitType, $unitExactType, $targetUnit, $ignoreUnit);
                    return $output;
                }
                // currency symbol as prefix: $10
                elseif(preg_match('~^[\D]*('.$currencys.')\]?\]?\040?([\-]?[0-9]+(\,[0-9]{3})*(\.[0-9]+)?).*$~',$input,$matches)) {
                    $output[] = self::outputGenerator($matches[2],$Currency[$matches[1]],$language, $unitType, $unitExactType, $targetUnit, $ignoreUnit);
                    return $output;
                }
                // catch string like "10 Euro" or "Dollar 15"
                elseif(preg_match('~^[\D]*([\-]?[0-9]+(\,[0-9]{3})*(\.[0-9]+)?)\040?\[?\[?('.join('|',$Currency).').*$~i',$input,$matches)) {
                    $output[] = self::outputGenerator($matches[1],$matches[4],$language, $unitType, $unitExactType, $targetUnit, $ignoreUnit);
                    return $output;
                }
                // currency symbol as prefix: Euro 10
                elseif(preg_match('~^[\D]*('.join('|',$Currency).')\]?\]?\040?([\-]?[0-9]+(\,[0-9]{3})*(\.[0-9]+)?)\s*(?!\d).*$~i',$input,$matches)) {
                    $output[] = self::outputGenerator($matches[2],$matches[1],$language, $unitType, $unitExactType, $targetUnit, $ignoreUnit);
                    return $output;
                }
            }
            // numbers with a , as decimal seperator and a . as thousand seperator
            else {
                if(preg_match('~^[\D]*([\-]?[0-9]+(\.[0-9]{3})*(\,[0-9]+)?)\040?\[?\[?('.self::regexCleaner(join('|',array_keys($Currency))).').*$~',$input,$matches)) {
                    $output[] = self::outputGenerator($matches[1],$Currency[$matches[4]],$language, $unitType, $unitExactType, $targetUnit, $ignoreUnit);
                    return $output;
                } // currency symbol as prefix
                else if(preg_match('~^[\D]*('.self::regexCleaner(join('|',array_keys($Currency))).')\040?([\-]?[0-9]+(\.[0-9]{3})*(\,[0-9]+)?).*$~',$input,$matches)) {
                    $output[] = self::outputGenerator($matches[2],$Currency[$matches[1]],$language, $unitType, $unitExactType, $targetUnit, $ignoreUnit);
                    return $output;
                }
                // catch string like "10 Euro" or "Dollar 15"
                else if(preg_match('~^[\D]*([\-]?[0-9]+(\.[0-9]{3})*(\,[0-9]+)?)\040?\[?\[?('.join('|',$Currency).')[\D]*$~i',$input,$matches)) {
                    $output[] = self::outputGenerator($matches[1],$matches[4],$language, $unitType, $unitExactType, $targetUnit, $ignoreUnit, $ignoreUnit);
                    return $output;
                } // currency as prefix
                else if(preg_match('~^[\D]*('.join('|',$Currency).')\040?([\-]?[0-9]+(\.[0-9]{3})*(\,[0-9]+)?)\s*(?!\d).*$~i',$input,$matches)) {
                    $output[] = self::outputGenerator($matches[2],$matches[1],$language, $unitType, $unitExactType, $targetUnit, $ignoreUnit);
                    return $output;
                }
            }
        }
    }
    /**
	* catch a single number
	*
	* Removes the thousand separator
	* eg. 12.999 => 12999 $language: 'de'
	*     12,000 => 12000 $language: 'en'
	*
	* @param	string
	* @return 	string
	*/
    private static function catchNumber($pageID, $input, $language, $propName, $unitType, $unitExactType, $targetUnit, $ignoreUnit, $originalInput)
    {
        global $units;

        if (isset($units[$unitExactType])) $unit = $units[$unitExactType];
        else $unit = 'noUnit';

        if ($language == "en" || $language == "ja" || $language == "zh")
        {
            if(preg_match('~^[\D]*(?<!-)([\-]?[0-9]+(\,[0-9]{3})*(\.[0-9]+)?)\040?(\([0-9]{4}\))?[\D]*$~',$input,$matches)) {
                return $output[] = self::outputGenerator($matches[1],$unit,$language, $unitType, $unitExactType, $targetUnit, $ignoreUnit);
            } else {
                Util::writeLogMsg($pageID, 'UnitValueParser', $language, $propName, $originalInput, 'failed to parse string');
            }
        } else {
            if(preg_match('~^[\D]*(?<!-)([\-]?[0-9]+(\.[0-9]{3})*(\,[0-9]+)?)(\040?\([0-9]{4}\))?[\D]*$~',$input,$matches)) {
                return $output[] = self::outputGenerator($matches[1],$unit,$language, $unitType, $unitExactType, $targetUnit, $ignoreUnit);
            } else {
                Util::writeLogMsg($pageID, 'UnitValueParser', $language, $propName, $originalInput, 'failed to parse string');
            }
        }
    }
}

