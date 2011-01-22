<?php
include("config.inc.php");

/**
 * the DateTimeParser parse strings for Dates
 * and returns them in the YYYY-MM-DD format.
 *
 * @author	Paul Kreis <mail@paulkreis.de>
 *
 */
class DateTimeParser implements Parser
{
    const parserID = "http://dbpedia.org/parsers/DateTimeParser";

    public static function getParserID() {
        return self::parserID;
    }

    public static function parseValue($input, $language='en', $restrictions)
    {
        $pageID = $restrictions[PAGEID];
        $propName = $restrictions[PROPERTYNAME];
        $unitType = $restrictions[UNITTYPE];
        $unitExactType = $restrictions[UNITEXACTTYPE];
        $targetUnit = $restrictions[TARGETUNIT];
        $originalInput = $input;
        if (!isset($language)) $language = 'en';
        $output = self::parseDateTemplates($pageID, $input, $language, $propName, $unitType, $unitExactType, $targetUnit, $originalInput);
        if ($output != null) return $output;
        $output = self::catchDate($pageID, $input, $language, $propName, $unitType, $unitExactType, $targetUnit, $originalInput);
        if ($output != null) return $output;
        $output = self::catchMonthYear($pageID, $input, $language, $propName, $unitType, $unitExactType, $targetUnit, $originalInput);
        if ($output != null) return $output;
        $output = self::catchMonthDay($pageID, $input, $language, $propName, $unitType, $unitExactType, $targetUnit, $originalInput);
        if ($output != null) return $output;
        return self::catchYear($pageID, $input, $language, $propName, $unitType, $unitExactType, $targetUnit, $originalInput);
    }

    private static function validateDate($date) {
        if(preg_match("~-?(\d\d\d\d)-(0[1-9]|1[012])-(0[1-9]|[12][0-9]|3[01])~",$date, $matches) == 1) {
            if(checkdate($matches[2], $matches[3], $matches[1])) {
                return $output = array($date, 'http://www.w3.org/2001/XMLSchema#date');
            }
        } elseif (preg_match("~-?\d\d\d\d-(0[1-9]|1[012])~",$date) == 1) {
            return $output = array($date, 'http://www.w3.org/2001/XMLSchema#gYearMonth');
        } elseif (preg_match("~--(0[1-9]|1[012])-(0[1-9]|[12][0-9]|3[01])~",$date) == 1) {
            return $output = array($date, 'http://www.w3.org/2001/XMLSchema#gMonthDay');
        } elseif (preg_match("~-?\d\d\d\d~",$date) == 1) {
            return $output = array($date, 'http://www.w3.org/2001/XMLSchema#gYear');
        } else return null;
    }


   /**
	* Returns Year,Month and Day of provided Date Literal
	*
	* Provided Data might be a Date like: {{birth date and age|1973|2|18}} or {{death date and age|1966|7|19|1887|5|21}}
	* Returns a normalized Date value (eg: 1984-01-29) if a Date is found in the string, NULL otherwise.
	*
	* @param	string	$input	Literaltext, that matched to be a Date
	* @return 	string	Date or NULL
	*/
    private static function parseDateTemplates($pageID, $input, $language, $propName, $unitType, $unitExactType, $targetUnit, $originalInput)
    {
        // clean up the input string
        $input = str_replace(' ','',$input);
        $input = preg_replace('~\|df=yes~','',$input);
        $input = preg_replace('~\|df=no~','',$input);
        $input = preg_replace('~\|df=y~','',$input);
        $input = preg_replace('~\|df=n~','',$input);
        $input = preg_replace('~\|mf=yes~','',$input);
        $input = preg_replace('~\|mf=no~','',$input);
        $input = preg_replace('~\|mf=y~','',$input);
        $input = preg_replace('~\|mf=n~','',$input);

        // date templates with one or two dates where the first is picked
        // {{templateName|YYYY|MM|DD|YYYY|MM|DD|something}}
        $templateNames = array('Deathdate', 'Deathdateandage','Dda','birthdate','startdate');
        if (preg_match('~^\{\{('.implode('|',$templateNames).')\|(\d{4})\|(\d{1,2})\|(\d{1,2})(\|\d{4}\|\d{1,2}\|\d{1,2})?(\|.*)?\}\}~i',$input,$matches)) {
            $output = substr('0000'.$matches[2],-4).'-'.substr('00'.$matches[3],-2).'-'.substr('00'.$matches[4],-2);
            return self::validateDate($output);
        }
        // date templates with two dates where the second is picked
        // {{templateName|YYYY|MM|DD|YYYY|MM|DD|something}}
        $templateNames = array('Birthdateandage','Birthdateandage2','Bda');
        if (preg_match('~^\{\{('.implode('|',$templateNames).')\|(\d{4}\|\d{1,2}\|\d{1,2}\|)?(\d{4})\|(\d{1,2})\|(\d{1,2})(\|.*)?\}\}~i',$input,$matches)) {
            $output = substr('0000'.$matches[3],-4).'-'.substr('00'.$matches[4],-2).'-'.substr('00'.$matches[5],-2);
            return self::validateDate($output);
        }
        // date templates with two dates and a variable B that sets which date should picked
        // {{templateName|B|YYYY|MM|DD|YYYY|MM|DD|something}}
        $templateNames = array('BirthDeathAge');
        if (preg_match('~^\{\{('.implode('|',$templateNames).')\|(B\|)?(\d{4})\|(\d{1,2})\|(\d{1,2})\|(\d{4})\|(\d{1,2})\|(\d{1,2})(\|.*)?\}\}~i',$input,$matches)) {
            if ($matches[2] == 'B|') {
                return self::validateDate(substr('0000'.$matches[3],-4).'-'.substr('00'.$matches[4],-2).'-'.substr('00'.$matches[5],-2));
            }
            else {
                return self::validateDate(substr('0000'.$matches[6],-4).'-'.substr('00'.$matches[7],-2).'-'.substr('00'.$matches[8],-2));
            }
        }
        // date templates with one or two dates where the first is picked
        // {{templateName|DD|MM|YYYY|DD|MM|YYYY|something}}
        $templateNames = array('Dateded�c�s','Dateded�c�set�ge','Datededécès','Datededécèsetâge');
        if (preg_match('~^\{\{('.implode('|',$templateNames).')\|(\d{1,2})\|(\d{1,2})\|(\d{4})(\|\d{1,2}\|\d{1,2}\|\d{4})?(\|.*)?\}\}~i',$input,$matches)) {
            return self::validateDate(substr('0000'.$matches[4],-4).'-'.substr('00'.$matches[3],-2).'-'.substr('00'.$matches[2],-2));
        }
        // date templates with two dates where the second is picked
        // {{templateName|DD|MM|YYYY|DD|MM|YYYY|something}}
        $templateNames = array('Datedenaissance');
        if (preg_match('~^\{\{('.implode('|',$templateNames).')\|(\d{1,2}\|\d{1,2}\|\d{4}\|)?(\d{1,2})\|(\d{1,2})\|(\d{4})(\|.*)?\}\}~i',$input,$matches)) {
            return self::validateDate(substr('0000'.$matches[5],-4).'-'.substr('00'.$matches[4],-2).'-'.substr('00'.$matches[3],-2));
        }
        // date templates with two dates and a variable B or N that sets which date should picked
        // {{templateName|N|DD|MM|YYYY|DD|MM|YYYY|something}}
        $templateNames = array('NDA','Naissanced�c�s�ge');
        if (preg_match('~^\{\{('.implode('|',$templateNames).')\|(.*\|)?(\d{1,2})\|(\d{1,2})\|(\d{4})\|(\d{1,2})\|(\d{1,2})\|(\d{4})(\|.*)?\}\}~i',$input,$matches)) {
            if ($matches[2] == 'B|' || $matches[2] == 'N|') {
                return self::validateDate(substr('0000'.$matches[5],-4).'-'.substr('00'.$matches[4],-2).'-'.substr('00'.$matches[3],-2));
            }
            else {
                return self::validateDate(substr('0000'.$matches[8],-4).'-'.substr('00'.$matches[7],-2).'-'.substr('00'.$matches[6],-2));
            }
        }
        return null;
    }

    /**
	* Returns Year,Month and Day of provided Date Literal
	*
	* Provided Data might be a Date like: [[January 20]] [[2001]], [[1991-10-25]] or 3 June 1981
	* Returns a normalized Date value (eg: 1984-01-29) if a Date is found in the string, NULL otherwise.
	*
	* @param	string	$input	Literaltext, that matched to be a Date
	* 			string	$language language of Literaltext, eg: 'en' or 'de'
	* @return 	string	Date or NULL
	*/
    private static function catchDate($pageID, $input, $language, $propName, $unitType, $unitExactType, $targetUnit, $originalInput)
    {
        global $month;
        // 1. catch dates like: "8 June 07" or "07 June 45" - Output: 2007-06-08 resp. 1945-06-07
        //    the century (1900 or 2000) depends on the last 2-digit number in the inputstring: >10 -> 1900
        if(preg_match('~^([0-9]{1,2})\s*('.implode('|',array_keys($month[$language])).')\s*([0-9]{2})$~i',$input,$matches)) {
            if ($matches[3] > 10) $century = 19; else $century = 20;
            return self::validateDate($century.$matches[3].'-'.$month[$language][strtolower($matches[2])].'-'.substr('00'.$matches[1],-2));
        }
        // 2. catch dates like: "[[29 January]] [[300 AD]]", "[[23 June]] [[2008]] (UTC)", "09:32, 6 March 2000 (UTC)" or "3 June 1981"
        if(preg_match('~^.*?(?<!\d)\[?\[?([0-9]{1,2})(\.|st|nd|rd|th)?\s*('.implode('|',array_keys($month[$language])).')\]?\]?,? \[?\[?([0-9]{1,4})\s*(BCE|BC|CE|AD|AC|CE)?\]?\]?(?!\d).*$~i',$input,$matches)) {
            if (!isset($matches[4])) $matches[4] = "";
            return self::validateDate(((strtoupper(substr($matches[5],0,2)))=='BC'?'-':''||(strtoupper($matches[5]))=='AC'?'-':'').substr('0000'.$matches[4],-4).'-'.$month[$language][strtolower($matches[3])].'-'.substr('00'.$matches[1],-2));
        }
        // 3. catch dates like: "[[January 20]] [[1995 AD]]", "[[June 17]] [[2008]] (UTC)" or "January 20 1995"
        if(preg_match('~\[?\[?('.implode('|',array_keys($month[$language])).')\s*,?\s+([0-9]{1,2})\]?\]?\s*[.,]?\s+\[?\[?([0-9]{1,4})\s*(BCE|BC|CE|AD|AC|CE)?\]?\]?~i',$input,$matches)) {
            if (!isset($matches[4])) $matches[4] = "";
            return self::validateDate(((strtoupper(substr($matches[4],0,2)))=='BC'?'-':''||(strtoupper($matches[4]))=='AC'?'-':'').substr('0000'.$matches[3],-4).'-'.$month[$language][strtolower($matches[1])].'-'.substr('00'.$matches[2],-2));
        }
        // 4.  catch dates like: "24-06-1867", "24/06/1867" or "bla24-06-1867bla"
        if(preg_match('~^.*?(?<!\d)([0-9]{1,2}+)[-/]([0-9]{1,2}+)[-/]([0-9]{3,4}+)(?!\d).*$~',$input,$matches)) {
            return self::validateDate(substr('0000'.$matches[3],-4).'-'.substr('00'.$matches[2],-2).'-'.substr('00'.$matches[1],-2));
        }
        // 5.  catch dates like: "24-june-1867", "24/avril/1867" or "bla24|juillet|1867bla"
        if(preg_match('~^.*?(?<!\d)([0-9]{1,2}+)[-/\|]('.implode('|',array_keys($month[$language])).')[-/\|]([0-9]{3,4}+)(?!\d).*$~i',$input,$matches)) {
            return self::validateDate(substr('0000'.$matches[3],-4).'-'.$month[$language][strtolower($matches[2])].'-'.substr('00'.$matches[1],-2));
        }
        // 6.  catch dates like: "1990-06-24", "1990/06/24" or "1977-01-01 00:00:00.000000"
        if(preg_match('~^.*?(?<!\d)([0-9]{3,4})[-/]([0-9]{1,2})[-/]([0-9]{1,2})(?!\d).*$~',$input,$matches)) {
            return self::validateDate(substr('0000'.$matches[1],-4).'-'.substr('00'.$matches[2],-2).'-'.substr('00'.$matches[3],-2));
        }
        return null;
    }

    /**
	* Returns Year and Month of provided Date Literal
	*
	* Provided Data might be a Date like: "August 2007" or "May 250 AD"
	* Returns a normalized Date value (eg: 1984-01) if a Date is found in the string, NULL otherwise.
	*
	* @param	string	$input	Literaltext, that matched to be a Date
	* 			string	$language language of Literaltext, eg: 'en' or 'de'
	* @return 	string	Date or NULL
	*/
    private static function catchMonthYear($pageID, $input, $language, $propName, $unitType, $unitExactType, $targetUnit, $originalInput) {
        global $month;
        if ( preg_match('~\[?\[?('.implode('|',array_keys($month[$language])).')\]?\]?,?\s*\[?\[?([0-9]{1,4})\s*(BCE|BC|CE|AD|AC|CE)?\]?\]?~i', $input, $matches) ) {
            if (!isset($matches[3])) $matches[3] = '';
            if ($matches[3] != '' || $matches[2] > 31) {
                return self::validateDate(((strtoupper(substr($matches[3],0,2)))=='BC'?'-':''||(strtoupper($matches[3]))=='AC'?'-':'').substr('0000'.$matches[2],-4).'-'.$month[$language][strtolower($matches[1])]);
            }
        }
        return null;
    }

    /**
     * Returns Month and Day of provided Date Literal
	 *
	 * Provided Data might be a Date like: "August 31" or "5 May" or "4th of July"
	 * Returns a normalized Date value (eg: --08-31) if a Date is found in the string, NULL otherwise.
     *
     * @param unknown_type $pageID
     * @param unknown_type $input
     * @param unknown_type $language
     * @param unknown_type $propName
     * @param unknown_type $unitType
     * @param unknown_type $unitExactType
     * @param unknown_type $targetUnit
     * @param unknown_type $originalInput
     * @return unknown
     */
    private static function catchMonthDay($pageID, $input, $language, $propName, $unitType, $unitExactType, $targetUnit, $originalInput) {
        global $month;
        if ( preg_match('~\[?\[?('.implode('|',array_keys($month[$language])).')\]?\]?\s*\[?\[?([1-9]|0[1-9]|[12][0-9]|3[01])\]?\]~i', $input, $matches) ) {
            return self::validateDate('--'.$month[$language][strtolower($matches[1])].'-'.substr('00'.$matches[2],-2));
        }
        elseif ( preg_match('~\[?\[?(?<!\d)([1-9]|0[1-9]|[12][0-9]|3[01])\s*(st|nd|rd|th)?\]?\]?\s*(of)?\s*\[?\[?('.implode('|',array_keys($month[$language])).')\]?\]?~i', $input, $matches) ) {
            return self::validateDate('--'.$month[$language][strtolower($matches[4])].'-'.substr('00'.$matches[1],-2));
        }
        return null;
    }

    /**
	* Returns Year of provided Date Literal
	*
	* Provided Data might be a string like: "2007" or "[[250 AD]]"
	* Returns a normalized Date value (eg: 1984) if a Year is found in the string, NULL otherwise.
	*
	* @param	string	$input	Literaltext, that matched to be a Year
	* @return 	string	Year or NULL
	*/
    private static function catchYear($pageID, $input, $language, $propName, $unitType, $unitExactType, $targetUnit, $originalInput) {
        if (preg_match('~(?<!\d)\[?\[?([0-9]{1,4})\s*(BCE|BC|CE|AD|AC|CE)?(?!\d)\]?\]?~',$input,$matches)) {
            if (!isset($matches[2]) && $matches[1] > 31) {
                $output = substr('0000'.$matches[1],-4);
            } elseif (isset($matches[2])) {
                $output = ((strtoupper(substr($matches[2],0,2)))=='BC'?'-':''||(strtoupper($matches[2]))=='AC'?'-':'').substr('0000'.$matches[1],-4);
            } elseif ($matches[1] > 31) {
                $output = substr('0000'.$matches[1],-4);
            }
            if (isset($output)) return self::validateDate($output);
        }
        Util::writeLogMsg($pageID,'DateTimeParser', $language, $propName, $originalInput, 'failed to parse a date');
        return null;
    }
    
    private static function match( $pattern, $subject, &$matches, $unitType ) {
        $index = $unitType == 'End date' ? 1 : 0;
        $success = preg_match_all($pattern, $subject, $all_matches, PREG_SET_ORDER);
        if ($success === false) return false;
        
        if (isset($all_matches[$index])) {
            $matches = $all_matches[$index];
            return 1;
        } else {
            return 0;
        }
    }
}
