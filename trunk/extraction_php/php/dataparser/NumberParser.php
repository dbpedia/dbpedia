<?php
namespace dbpedia\dataparser
{
use \dbpedia\wikiparser\TextNode;
use \dbpedia\wikiparser\Node;
/**
 * The NumberParser can parse integer and double datatypes.
 * With NumberParser::setLanguage(en/de/it/es ...) one could set up a Language.
 * The default language is english (en).
 *
 * @author Paul Kreis <mail@paulkreis.de>
 */
class NumberParser implements DataParser
{
    private $name = "NumberParser";

    const INTEGER = 'integer';
    const DOUBLE = 'double';

    private $integer = false;
    private $double = false;
    private $dataType = null;
    private $language;

    public function setLanguage($language)
    {
        $this->language = $language;
    }

    /**
     * The NumberParser can parse integer and double datatypes.
     * With NumberParser::setLanguage(en/de/it/es ...) one could set up a Language.
     * The default language is english (en).
     *
     * @param DataType $datatype
     */
    public function __construct($datatype)
    {
        $this->setLanguage('en');
        $this->dataType = $datatype;
        if ($datatype->getName() == 'xsd:integer')
        {
            $this->integer = true;
        }
        elseif ($datatype->getName() == 'xsd:double')
        {
            $this->double = true;
        }
        else
        {
            throw new DataParserException("wrong parameter.");
        }
    }

    /**
     * Parses integer or double values in a Node.
     * If the indicated data type is found, the value is returned.
     *
     * @param Node $node
     * @return string
     */
    public function parse(Node $node)
    {
            $children = $node->getChildren();
            foreach ($children as $child)
            {
                if ($child instanceof TextNode)
                {
                    $input = $child->getText();
                    self::catchLargeNumbers($input, $this->language);
                    if ($this->integer)
                    {
                        $output = self::parseIntegerValue($input);
                        if ($output != "") return $output;
                    }
                    if ($this->double)
                    {
                        $output = self::parseFloatValue($input);
                        if ($output != "") return $output;
                    }
                }
            }
    }

    /**
     * Convert shortened Numbers
     * eg. $12.53 million => $12530000
     *
     * @param	string	$input	the String with the shortened Number
     * @return 	string	$input	the String with the lengthened Number
     */
    public static function catchLargeNumbers(&$input, $language)
    {
        global $scale;
        if ($language == "en" || $language == "ja" || $language == "zh")
        {
            if (preg_match('~^([\D]*)([0-9]+(\,[0-9]{3})*(\.[0-9]+)?)\s?\[?\[?('.implode('|',array_keys($scale)).')\]?\]?(.*)$~i',$input,$matches))
            {
                $num = str_replace(',','',$matches[2])*$scale[strtolower($matches[5])];
                // make sure that large numbers are presented as decimals, not E notation
                $num = number_format($num, 0, '.', '');
                $input = $matches[1].$num.$matches[6];
            }
        }
        else
        {
            if (preg_match('~^([\D]*)([0-9]+(\.[0-9]{3})*(\,[0-9]+)?)\s?\[?\[?('.implode('|',array_keys($scale)).')\]?\]?(.*)$~i',$input,$matches))
            {
                $num = str_replace(',','.',str_replace('.','',$matches[2]))*$scale[strtolower($matches[5])];
                // make sure that large numbers are presented as decimals, not E notation
                $num = number_format($num, 0, '.', '');
                $input = $matches[1].$num.$matches[6];
            }
        }
    }
    
    /**
     * Formats a number
     *
     * @param mixed $number
     * @return mixed int/float
     */
    public static function formatNumber($number)
    {
        if ($number >= 1000)
        {
            return $number = number_format($number,0,'.','');
        }
        else
        {
            $strNumber = ''.$number;
            if (preg_match('~E-([0-9]{1,3})$~', $strNumber, $match))
            {
                $ex = $match[1]*1;
                return $number = number_format($number,$ex+3,'.','');
            }
            $v = explode('.',$number);
            if (count($v) == 2)
            {
                $x = 0;
                if ($v[0] == 0)
                {
                    $strNum = ''.$v[1];
                    while ($x < strlen($strNum) && $strNum[$x] == 0)
                    {
                        $x++;
                    }
                }
                return $number = number_format($number,$x + 4,'.','');
            }
            else return number_format($number,0,'.','');
        }
    }

    private function parseIntegerValue($input)
    {
        $output = null;
        if ($this->language == 'en' || $this->language == 'ja' || $this->language == 'zh')
        {
            if (preg_match('~^[\D]*(?<!-)([0-9\,\-]+).*$~i',$input,$matches))
            {
                $output = str_replace(',','',$matches[1]);
            }
        }
        else
        {
            if (preg_match('~^[\D]*(?<!-)([0-9\.\-]+).*$~i',$input,$matches))
            {
                $output = str_replace('.','',$matches[1]);
            }
        }
        if (isset($output) && is_numeric($output)) $output = intval($output);
        else return null;
        if (isset($output) && is_integer($output)) return strval($output);
        else return null;
    }

    private function parseFloatValue($input)
    {
        $output = null;
        if ($this->language == "en" || $this->language == "ja" || $this->language == "zh")
        {
            if (preg_match('~^[\D]*(?<!-)([0-9\-]+(\,[0-9]{3})*(\.[0-9]+)?).*$~',$input,$matches))
            {
                $output = str_replace(',','',$matches[1]);
            }
        }
        else
        {
            if(preg_match('~^[\D]*(?<!-)([0-9\-]+(\.[0-9]{3})*(\,[0-9]+)?).*$~',$input,$matches))
            {
                $output = str_replace(',','.',str_replace('.','',$matches[1]));
            }
        }
        if (is_numeric($output)) $output = floatval($output);
        else return null;
        if (is_float($output)) return strval($output);
        else return null;
    }

    public function __toString()
    {
        return "Parser '".$this->name."'".PHP_EOL;
        /*
        $str = '';
        $str .= "Parser".PHP_EOL;
        $str .= "-------".PHP_EOL;
        $str .= "Name:          '".$this->name."'".PHP_EOL;
        $str .= "Number format: '".(($this->integer) ? NumberParser::INTEGER : NumberParser::DOUBLE)."'".PHP_EOL;
        return $str;
        */
    }
}
}
