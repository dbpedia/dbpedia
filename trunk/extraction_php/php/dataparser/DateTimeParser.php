<?php
namespace dbpedia\dataparser
{
use dbpedia\ontology\dataTypes\DataType;
use dbpedia\wikiparser\Node;
/**
 * Description of DateTimeParser
 *
 * @author Paul Kreis
 */
class DateTimeParser implements DataParser
{
    private $name = 'DateTimeParser';
    private $language = null;
    private $dataType = null;
    private $logger;
    private $node;

    const XSD_DATE = "xsd:date";
    const XSD_GYEAR = "xsd:gYear";
    const XSD_GYEARMONTH = "xsd:gYearMonth";
    const XSD_MONTHDAY = "xsd:gMonthDay";

    public function __construct(DataType $dataType)
    {
        $this->setLanguage('en');
        $this->dataType = $dataType;
        $this->logger = \dbpedia\core\DBpediaLogger::getLogger(__CLASS__);
    }

    public function setLanguage($language)
    {
        if($language != '')
        {
            $this->language = $language;
        }
        else
        {
            throw new DataParserException("\$language is an empty string.");
        }
    }

    public function parse(Node $node)
    {
        if ($node instanceof \dbpedia\wikiparser\PropertyNode)
        {
            $this->node = $node;
            $inProgress = null;
            if ($node instanceof \dbpedia\wikiparser\TemplateNode)
            {
                $inProgress = self::catchTemplate($node);
                if ($inProgress != null)
                {
                    return $inProgress;
                }
            }
            else
            {
                $children = $node->getChildren('TemplateNode');
                foreach ($children as $child)
                {
                    $inProgress = self::catchTemplate($child);
                    if ($inProgress != null)
                    {
                        return $inProgress;
                    }
                }
            }
            $inProgress = UnitValueParser::nodeToString($node);
            switch ($this->dataType->getName())
            {
                case self::XSD_DATE :
                    $output = self::catchDate($inProgress);
                    if ($output === null)
                    {
                        $this->logger->debug("No date found in: \"" . $inProgress ."\". " . PHP_EOL .
                                "Property: " . $node->getKey() . PHP_EOL .
                                "Source: " . $this->node->getSourceUri());
                    }
                    break;
                case self::XSD_GYEAR :
                    $output = self::catchYear($inProgress);
                    if ($output === null)
                    {
                        $this->logger->debug("No year found in: \"" . $inProgress ."\". " . PHP_EOL .
                                "Property: " . $node->getKey() . PHP_EOL .
                                "Source: " . $this->node->getSourceUri());
                    }
                    break;
                case self::XSD_GYEARMONTH :
                    $output = self::catchYearMonth($inProgress);
                    if ($output === null)
                    {
                        $this->logger->debug("No yearMonth found in: \"" . $inProgress ."\". " . PHP_EOL .
                                "Property: " . $node->getKey() . PHP_EOL .
                                "Source: " . $this->node->getSourceUri());
                    }
                    break;
                case self::XSD_MONTHDAY :
                    $output = self::catchMonthDay($inProgress);
                    if ($output === null)
                    {
                        $this->logger->debug("No monthDay found in: \"" . $inProgress ."\". " . PHP_EOL .
                                "Property: " . $node->getKey() . PHP_EOL .
                                "Source: " . $this->node->getSourceUri());
                    }
                    break;
                default :
                    $this->logger->debug("No or wrong data type set. " . PHP_EOL .
                            "Property: " . $node->getKey() . PHP_EOL .
                            "Source: " . $this->node->getSourceUri());
                    break;
            }
            if ($output != null)
            {
                return $output;
            }
            else
            {
                return null;
            }
        }
        else
        {
            throw new DataParserException("Wrong instance.");
        }
    }

    private function validateDate($date)
    {
        if (preg_match("~^-?(\d\d\d\d)-(0[1-9]|1[012])-(0[1-9]|[12][0-9]|3[01])$~", $date, $matches) == 1)
        {
            if (checkdate($matches[2], $matches[3], $matches[1]))
            {
                // http://www.w3.org/2001/XMLSchema#date
                return $date;
            }
        }
        elseif (preg_match("~^-?\d\d\d\d-(0[1-9]|1[012])$~", $date) == 1)
        {
            // http://www.w3.org/2001/XMLSchema#gYearMonth
            return $date;
        }
        elseif (preg_match("~^--(0[1-9]|1[012])-(0[1-9]|[12][0-9]|3[01])$~", $date) == 1)
        {
            // http://www.w3.org/2001/XMLSchema#gMonthDay
            return $date;
        }
        elseif (preg_match("~^-?\d\d\d\d$~", $date) == 1)
        {
            // http://www.w3.org/2001/XMLSchema#gYear
            return $date;
        }
        else
        {
            $this->logger->debug("Date validation failed: " . $date . ". " . PHP_EOL .
                    "Property: " . $this->node->getKey() . PHP_EOL .
                    "Source: " . $this->node->getSourceUri());
            return null;
        }
    }

    /**
     * This Method contains "mappings" for property templates like {{convert|...}
     *
     * @param TemplateNode $templateNode
     * @return array
     */
    private function catchTemplate($templateNode)
    {
        if ($templateNode instanceof \dbpedia\wikiparser\TemplateNode)
        {
            $date = null;
            $year = null;
            $month = null;
            $day = null;
            $children = $templateNode->getChildren();
            $templateName = $templateNode->getTitle()->decoded();

            foreach ($children as $childNode)
            {
                // creates an array of TextNodes from the PropertyNodes of the TemplateNode
                $childrenChilds[] = $childNode->getChildren('TextNode');
            }

            if ($templateName == 'Birth-date')
            {
                $i = 0;
                while (!$date && isset($childrenChilds[$i][0]))
                {
                    switch ($this->dataType->getName())
                    {
                        case self::XSD_DATE :
                            $date = self::catchDate($childrenChilds[$i][0]->getText());
                            if ($date === null)
                            {
                                $this->logger->debug("No date found in: \"" . $childrenChilds[$i][0]->getText() .
                                        "\". " . PHP_EOL . "Property: " . $this->node->getKey() . PHP_EOL .
                                        "Source: " . $this->node->getSourceUri());
                            }
                            break;
                        case self::XSD_GYEAR :
                            $date = self::catchYear($childrenChilds[$i][0]->getText());
                            if ($date === null)
                            {
                                $this->logger->debug("No year found in: \"" . $childrenChilds[$i][0]->getText() .
                                        "\". " . PHP_EOL . "Property: " . $this->node->getKey() . PHP_EOL .
                                        "Source: " . $this->node->getSourceUri());
                            }
                            break;
                        case self::XSD_GYEARMONTH :
                            $date = self::catchYearMonth($childrenChilds[$i][0]->getText());
                            if ($date === null)
                            {
                                $this->logger->debug("No yearMonth found in: \"" . $childrenChilds[$i][0]->getText() .
                                        "\". " . PHP_EOL . "Property: " . $this->node->getKey() . PHP_EOL .
                                        "Source: " . $this->node->getSourceUri());
                            }
                            break;
                        default :
                            $this->logger->debug("No or wrong data type set. " . PHP_EOL .
                                    "Property: " . $this->node->getKey() . PHP_EOL .
                                    "Source: " . $this->node->getSourceUri());
                            break;
                    }
                    $i++;
                }
            }

            /**
             * http://en.wikipedia.org/wiki/Template:Birth_date_and_age
             * {{Birth date|year_of_birth|month_of_birth|day_of_birth|...}}
             */
            elseif ($templateName == 'Birth date and age' || $templateName == 'Birth date and age2' ||
                    $templateName == 'Death date and age' || $templateName == 'Birth date' ||
                    $templateName == 'Death date' || $templateName == 'Bda' || $templateName == 'Dob')
            {
                /**
                 * $childrenChilds[0][0]->getText()
                 * gets the text from the single textNode of the first PropertyNode
                 * {{Birth date|YEAR_OF_BIRTH|month_of_birth|day_of_birth|...}}
                 */
                $year = $childrenChilds[0][0]->getText();

                /**
                 * $childrenChilds[1][0]->getText()
                 * gets the text from the single textNode of the second PropertyNode
                 * {{Birth date|year_of_birth|MONTH_OF_BIRTH|day_of_birth|...}}
                 */
                $month = $childrenChilds[1][0]->getText();

                /**
                 * $childrenChilds[2][0]->getText()
                 * gets the text from the single textNode of the third PropertyNode
                 * {{Birth date|year_of_birth|month_of_birth|DAY_OF_BIRTH|...}}
                 */
                $day = $childrenChilds[2][0]->getText();

                /**
                 * Sometimes the templates are udes wrong like this:
                 * {{birth date|df=yes|1833|10|21}}
                 * TODO: fix problem with gYear gDate e.q. Alfred Nobel
                 */
                if (!self::validateDate($year))
                {
                    $year = $childrenChilds[1][0]->getText();
                    $month = $childrenChilds[2][0]->getText();
                    $day = $childrenChilds[3][0]->getText();
                }
            }

            /**
             * http://en.wikipedia.org/wiki/Template:BirthDeathAge
             * {{BirthDeathAge|birth_or_death_flag|year_of_birth|month_of_birth|day_of_birth|year_of_death|month_of_death|day_of_death|...}}
             */
            elseif ($templateName == 'Birth Death Age')
            {
                /**
                 * $childrenChilds[0][0]->getText()
                 * gets the text from the single textNode of the first PropertyNode
                 * {{BirthDeathAge|BIRTH_OR_DEATH_FLAG|year_of_birth|month_of_birth|day_of_birth|year_of_death|month_of_death|day_of_death|...}}
                 */
                $flag = $childrenChilds[0][0]->getText();

                if ($flag == 'B')
                {
                    /**
                     * $childrenChilds[1][0]->getText()
                     * gets the text from the single textNode of the second PropertyNode
                     * {{BirthDeathAge|birth_or_death_flag|YEAR_OF_BIRTH|month_of_birth|day_of_birth|year_of_death|month_of_death|day_of_death|...}}
                     */
                    $year = $childrenChilds[1][0]->getText();

                    /**
                     * $childrenChilds[2][0]->getText()
                     * gets the text from the single textNode of the third PropertyNode
                     * {{BirthDeathAge|birth_or_death_flag|year_of_birth|MONTH_OF_BIRTH|day_of_birth|year_of_death|month_of_death|day_of_death|...}}
                     */
                    $month = $childrenChilds[2][0]->getText();

                    /**
                     * $childrenChilds[3][0]->getText()
                     * gets the text from the single textNode of the fourth PropertyNode
                     * {{BirthDeathAge|birth_or_death_flag|year_of_birth|month_of_birth|DAY_OF_BIRTH|year_of_death|month_of_death|day_of_death|...}}
                     */
                    $day = $childrenChilds[3][0]->getText();
                }
                else
                {
                    /**
                     * $childrenChilds[4][0]->getText()
                     * gets the text from the single textNode of the fifth PropertyNode
                     * {{BirthDeathAge|birth_or_death_flag|year_of_birth|month_of_birth|day_of_birth|YEAR_OF_DEATH|month_of_death|day_of_death|...}}
                     */
                    $year = $childrenChilds[4][0]->getText();

                    /**
                     * $childrenChilds[5][0]->getText()
                     * gets the text from the single textNode of the sixth PropertyNode
                     * {{BirthDeathAge|birth_or_death_flag|year_of_birth|month_of_birth|day_of_birth|year_of_death|MONTH_OF_DEATH|day_of_death|...}}
                     */
                    $month = $childrenChilds[5][0]->getText();

                    /**
                     * $childrenChilds[6][0]->getText()
                     * gets the text from the single textNode of the seventh PropertyNode
                     * {{BirthDeathAge|birth_or_death_flag|year_of_birth|month_of_birth|day_of_birth|year_of_death|month_of_death|DAY_OF_DEATH|...}}
                     */
                    $day = $childrenChilds[6][0]->getText();
                }
            }
            else
            {
                $this->logger->debug("Template not found: " . $templateName . ". " . PHP_EOL .
                        "Property: " . $this->node->getKey() . PHP_EOL .
                        "Source: " . $this->node->getSourceUri());
                return null;
            }
            if ($day < "10" && $day[0] != 0)
            {
                $day = "0".$day;
            }
            if ($month < "10" && $month[0] != 0)
            {
                $month = "0".$month;
            }
            switch ($this->dataType->getName())
            {
                case self::XSD_DATE :
                    $date = self::validateDate($year."-".$month."-".$day);

                    break;
                case self::XSD_GYEAR :
                    $date = self::validateDate($year);
                    break;
                case self::XSD_GYEARMONTH :
                    $date = self::validateDate($year."-".$month);
                    break;
                default :
                    $this->logger->debug("No or wrong data type set. " . PHP_EOL .
                            "Property: " . $this->node->getKey() . PHP_EOL .
                            "Source: " . $this->node->getSourceUri());
                    break;
            }
            if ($date === null)
            {
                $this->logger->debug("Template parsing failed: " . $templateName . ". " . PHP_EOL .
                        "Property: " . $this->node->getKey() . PHP_EOL .
                        "Source: " . $this->node->getSourceUri());
            }
            return $date;
        }
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
    private function catchDate($input)
    {
        global $month;
        $language = $this->language;
        // 1. catch dates like: "8 June 07" or "07 June 45" - Output: 2007-06-08 resp. 1945-06-07
        //    the century (1900 or 2000) depends on the last 2-digit number in the inputstring: >10 -> 1900
        if(preg_match('~^([0-9]{1,2})\s*('.implode('|',array_keys($month[$language])).')\s*([0-9]{2})$~i',$input,$matches))
        {
            if ($matches[3] > 10) $century = 19; else $century = 20;
            return self::validateDate($century.$matches[3].'-'.$month[$language][strtolower($matches[2])].'-'.substr('00'.$matches[1],-2));
        }
        // 2. catch dates like: "[[29 January]] [[300 AD]]", "[[23 June]] [[2008]] (UTC)", "09:32, 6 March 2000 (UTC)" or "3 June 1981"
        if(preg_match('~^.*?(?<!\d)\[?\[?([0-9]{1,2})(\.|st|nd|rd|th)?\s*('.implode('|',array_keys($month[$language])).')\]?\]?,? \[?\[?([0-9]{1,4})\s*(BCE|BC|CE|AD|AC|CE)?\]?\]?(?!\d).*$~i',$input,$matches))
        {
            if (!isset($matches[5])) $matches[5] = "";
            return self::validateDate(((strtoupper(substr($matches[5],0,2)))=='BC'?'-':''||(strtoupper($matches[5]))=='AC'?'-':'').substr('0000'.$matches[4],-4).'-'.$month[$language][strtolower($matches[3])].'-'.substr('00'.$matches[1],-2));
        }
        // 3. catch dates like: "[[January 20]] [[1995 AD]]", "[[June 17]] [[2008]] (UTC)" or "January 20 1995"
        if(preg_match('~\[?\[?('.implode('|',array_keys($month[$language])).')\s*,?\s+([0-9]{1,2})\]?\]?\s*[.,]?\s+\[?\[?([0-9]{1,4})\s*(BCE|BC|CE|AD|AC|CE)?\]?\]?~i',$input,$matches))
        {
            if (!isset($matches[4])) $matches[4] = "";
            return self::validateDate(((strtoupper(substr($matches[4],0,2)))=='BC'?'-':''||(strtoupper($matches[4]))=='AC'?'-':'').substr('0000'.$matches[3],-4).'-'.$month[$language][strtolower($matches[1])].'-'.substr('00'.$matches[2],-2));
        }
        // 4.  catch dates like: "24-06-1867", "24/06/1867" or "bla24-06-1867bla"
        if(preg_match('~^.*?(?<!\d)([0-9]{1,2}+)[-/]([0-9]{1,2}+)[-/]([0-9]{3,4}+)(?!\d).*$~',$input,$matches))
        {
            return self::validateDate(substr('0000'.$matches[3],-4).'-'.substr('00'.$matches[2],-2).'-'.substr('00'.$matches[1],-2));
        }
        // 5.  catch dates like: "24-june-1867", "24/avril/1867" or "bla24|juillet|1867bla"
        if(preg_match('~^.*?(?<!\d)([0-9]{1,2}+)[-/\|]('.implode('|',array_keys($month[$language])).')[-/\|]([0-9]{3,4}+)(?!\d).*$~i',$input,$matches))
        {
            return self::validateDate(substr('0000'.$matches[3],-4).'-'.$month[$language][strtolower($matches[2])].'-'.substr('00'.$matches[1],-2));
        }
        // 6.  catch dates like: "1990 06 24", "1990-06-24", "1990/06/24" or "1977-01-01 00:00:00.000000"
        if(preg_match('~^.*?(?<!\d)([0-9]{3,4})[-/\s]([0-9]{1,2})[-/\s]([0-9]{1,2})(?!\d).*$~',$input,$matches))
        {
            return self::validateDate(substr('0000'.$matches[1],-4).'-'.substr('00'.$matches[2],-2).'-'.substr('00'.$matches[3],-2));
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
    private function catchYear($input)
    {
        // find string containing only 1-4 numbers
        if (preg_match('~^(\d{1,4})$~', $input, $matches))
        {
            $output = substr('0000'.$matches[1],-4);
        }
        else if (preg_match('~^(\d{4})\D+~', $input, $matches))
        {
            $output = $matches[1];
        }
        // find 4 numbers in a row
        else if (preg_match_all('~(?<![\d\pL\w])(\d{4})(?!\d)\s*(BCE|BC|CE|AD|AC|CE)?~u', $input, $matches))
        {
            $output = ($matches[2][0])?'-':'';
            $output .= $matches[1][0];
        }
        // find 3 numbers in a row
        else if (preg_match_all('~(?<![\d\pL\w])(\d{3})(?!\d)\s*(BCE|BC|CE|AD|AC|CE)?~u', $input, $matches))
        {
            $output = ($matches[2][0])?'-':'';
            $output .= '0'.$matches[1][0];
        }
        // find 2 numbers in a row
        else if (preg_match_all('~(?<![\d\pL\w])(\d{2})(?!\d)\s*(BCE|BC|CE|AD|AC|CE)?~u', $input, $matches))
        {
            $output = ($matches[2][0])?'-':'';
            $output .= '00'.$matches[1][0];
        }
        // find 1 number in a row
        else if (preg_match_all('~(?<![\d\pL\w])(\d{1})(?!\d)\s*(BCE|BC|CE|AD|AC|CE)?~u', $input, $matches))
        {
            $output = ($matches[2][0])?'-':'';
            $output .= '000'.$matches[1][0];
        }
        if (isset($output)) return self::validateDate($output);
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
    private function catchYearMonth($input)
    {
        global $month;
        if ( preg_match('~\[?\[?('.implode('|',array_keys($month[$this->language])).')\]?\]?,?\s*\[?\[?([0-9]{1,4})\s*(BCE|BC|CE|AD|AC|CE)?\]?\]?~i', $input, $matches) )
        {
            if (!isset($matches[3])) $matches[3] = '';
            if ($matches[3] != '' || $matches[2] > 31)
            {
                return self::validateDate(((strtoupper(substr($matches[3],0,2)))=='BC'?'-':''||(strtoupper($matches[3]))=='AC'?'-':'').substr('0000'.$matches[2],-4).'-'.$month[$this->language][strtolower($matches[1])]);
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
    private function catchMonthDay($input)
    {
        global $month;

        if ( preg_match('~\[?\[?('.implode('|',array_keys($month[$this->language])).')\]?\]?\s*\[?\[?([1-9]|0[1-9]|[12][0-9]|3[01])\]?\]~i', $input, $matches) )
        {
            return self::validateDate('--'.$month[$this->language][strtolower($matches[1])].'-'.substr('00'.$matches[2],-2));
        }
        elseif ( preg_match('~\[?\[?(?<!\d)([1-9]|0[1-9]|[12][0-9]|3[01])\s*(st|nd|rd|th)?\]?\]?\s*(of)?\s*\[?\[?('.implode('|',array_keys($month[$this->language])).')\]?\]?~i', $input, $matches) )
        {
            return self::validateDate('--'.$month[$this->language][strtolower($matches[4])].'-'.substr('00'.$matches[1],-2));
        }
        return null;
    }

    public function __toString()
    {
        return "Parser '".$this->name."'".PHP_EOL;
        /*
        $str = '';
        $str .= "Parser".PHP_EOL;
        $str .= "-------".PHP_EOL;
        $str .= "Name:      '".$this->name."'".PHP_EOL;
        return $str;
        */
    }
}
}
