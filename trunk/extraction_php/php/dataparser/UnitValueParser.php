<?php
namespace dbpedia\dataparser
{
use \dbpedia\ontology\dataTypes\DimensionDataType;
use \dbpedia\ontology\dataTypes\UnitDataType;
use \dbpedia\wikiparser\Node;
use \dbpedia\wikiparser\TemplateNode;
use \dbpedia\wikiparser\PropertyNode;
use \dbpedia\wikiparser\LinkNode;
use \dbpedia\wikiparser\TextNode;

/**
 * UnitValueParser
 * With UnitValueParser::setLanguage(en/de/it/es ...) one could set up a Language.
 * The default language is english (en).
 *
 * @author Paul Kreis <mail@paulkreis.de>
 */
class UnitValueParser implements DataParser
{
    private $name = 'UnitValueParser';
    private $language = null;
    private $dimension = null;
    private $stringUnitRegexLabels = null;
    private $isUnitDataType = false;
    private $unitNames = array();
    private $unit = null;
    private $logger;
    private $node;

    public function __construct($dataType)
    {
        $this->logger = \dbpedia\core\DBpediaLogger::getLogger(__CLASS__);
        $this->setLanguage('en');
        if ($dataType instanceof DimensionDataType)
        {
            $this->dimension = $dataType;
            $this->unitNames = $dataType->getUnitLabels();
            $this->stringUnitRegexLabels = self::regexCleaner(join('|',$this->unitNames));
        }
        elseif ($dataType instanceof UnitDataType)
        {
            $this->dimension = $dataType->getDimension();
            $this->unitNames = $dataType->getLabels();
            $this->unit = $dataType;
            $this->stringUnitRegexLabels = self::regexCleaner(join('|',$this->dimension->getUnitLabels()));
            $this->isUnitDataType = true;
        }
        else
        {
            throw new DataParserException('Wrong parameter.');
        }
    }

    /**
     * With UnitValueParser::setLanguage(en/de/it/es ...) one could set up a Language.
     * The default language is english (en).
     * @param string $language
     */
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
            $inProgress = self::catchTemplates($node);
            if ($inProgress != null)
            {
                return $inProgress;
            }
            // If catchTemplate returns null we try the old regex parser
            $inProgress = self::nodeToString($node);
            NumberParser::catchLargeNumbers($inProgress, $this->language);
            switch (strtolower($this->dimension->getName()))
            {
                case 'currency' :
                    $output = self::catchMoney($inProgress);
                    if ($output != null)
                    {
                        return $output;
                    }
                    else
                    {
                        $this->logger->debug("No currency or value found in: \"" . $inProgress ."\". " . PHP_EOL .
                                             "Property:" . $node->getKey() . PHP_EOL .
                                             "Source: " . $this->node->getSourceUri());
                    }
                    break;
                case 'time' :
                    $output = self::catchTime($inProgress);
                    if ($output != null)
                    {
                        return $output;
                    }
                    else
                    {
                        $this->logger->debug("No time found in: \"" . $inProgress ."\". " . PHP_EOL .
                                             "Property:" . $node->getKey() . PHP_EOL .
                                             "Source: " . $this->node->getSourceUri());
                    }
                    break;
                default :
                    $output = self::catchUnitValue($inProgress);
                    if ($output != null)
                    {
                        return $output;
                    }
                    else
                    {
                        if ($this->isUnitDataType)
                        {
                            $output = $this->catchValue($inProgress);
                            if ($output != null)
                            {
                                return array($output, $this->unit);
                            }
                        }
                    }
                    break;
            }
            $this->logger->debug("No unit or value found in: \"" . $inProgress ."\". " . PHP_EOL .
                                 "Property: " . $node->getKey() . PHP_EOL .
                                 "Source: " . $this->node->getSourceUri());
        }
        else
        {
            throw new DataParserException("Wrong instance.");
        }
    }

    /**
     *
     * @param $node
     * @return string
     */
    public static function nodeToString(Node $node)
    {
        $string = '';
        if ($node instanceof Node)
        {
            if ($node instanceof TextNode)
            {
                $string .= $node->getText() . " ";
            }
            elseif ($node instanceof PropertyNode ||
                    $node instanceof TemplateNode ||
                    $node instanceof LinkNode)
            {
                $children = $node->getChildren();
                foreach ($children as $childNode)
                {
                    $string .= self::nodeToString($childNode);
                }
            }
            return $string;
        }
        else
        {
            throw new DataParserException('Wrong paramter.');
        }
    }

    /**
     * Creates the output array, the number at key 0, the unit at key 1.
     * Eliminates the thousand separator.
     *
     * @param string $value
     * @param string $unit
     * @return array
     */
    private function outputGenerator($value, $unit)
    {
        if ($value !='')
        {
            $language = $this->language;
            if ($language == "en" || $language == "ja" || $language == "zh")
            {
                $v = str_replace(',','',$value);
            }
            else
            {
                $v = str_replace(',','.',str_replace('.','',$value));
            }

            if(!$v || !is_numeric($v))
            {
                return null;
            }

            if (!$this->isUnitDataType)
            {
                $unit = $this->dimension->getUnit($unit);
                if ($unit instanceof UnitDataType)
                {
                    return $output = array(NumberParser::formatNumber($v) ,$unit);
                }
                else
                {
                    return null;
                }
            }
            else
            {
                if ($unit != '')
                {
                    return $output = array(NumberParser::formatNumber($v), $this->dimension->getUnit($unit));
                }
                else
                {
                    return $output = array(NumberParser::formatNumber($v), $this->unit);
                }
            }
        }

        // Nothing found
        return null;
    }

    /**
     * escapes characters that would otherwise be interpreted as a meta-character in the regexp
     *
     * @param string $regex
     * @return string
     */
    private static function regexCleaner($regex)
    {
        $regex = str_replace('\\','\\\\',$regex);
        $regex = str_replace('^','\^',$regex);
        $regex = str_replace('$','\$',$regex);
        return $regex;
    }

    /**
     * This Method parse property templates like {{convert|...}
     *
     * @param Node $node
     * @return array
     */
    private function catchTemplates($node)
    {
        if ($node instanceof TemplateNode)
        {
            $children = $node->getChildren();
            $templateName = $node->getTitle()->decoded();
            $value = null;
            $unit = null;
            foreach ($children as $childNode)
            {
                // creates an array of TextNodes from the PropertyNodes of the TemplateNode
                $childrenChilds[] = $childNode->getChildren('TextNode');
            }

            ///////////////////////////////////////////////////////////////////////////////////////
            // Start of template parsing
            ///////////////////////////////////////////////////////////////////////////////////////
            // How to:
            // There are two cases how templates are build
            //  - only values
            //    {{convert|original_value|original_unit|conversion_unit|round_to|...}}
            //  - key and value as a pair connected by "="
            //    {{height|first_unit=first_value|second_unit=second_value|...}}
            // The first value after "{{" is the templateName and every "|" will result in a new
            // PropertyNode of the TemplateNode. The $childrenChilds[][] array contains the
            // TextNodes of these children.
            // With $childrenChilds[0][0]->getText() you get the text from the first TextNode of
            // the first PropertyNode. For example:
            // {{convert|ORIGINAL_VALUE|original_unit|conversion_unit|round_to|...}} or
            // {{height|first_unit=FIRST_VALUE|second_unit=second_value|...}}
            // With $childrenChilds[1][0]->getText() you get the text from the first TextNode
            // of the second PropertyNode.
            // With $childrenChilds[0][0]->getParent()->getKey() you get the key of the first
            // PropertyNode. For example:
            // {{height|FIRST_UNIT=first_value|second_unit=second_value|...}}
            // The first case (convert template example) has no key.
            ///////////////////////////////////////////////////////////////////////////////////////

            // http://en.wikipedia.org/wiki/Template:Convert
            // http://it.wikipedia.org/wiki/Template:Converti
            // {{convert|original_value|original_unit|conversion_unit|round_to|...}}
            if ($templateName == 'Convert' || $templateName == 'Converti')
            {
                $value = self::catchValue($childrenChilds[0][0]->getText());
                $unit = self::catchUnit($childrenChilds[1][0]->getText());
            }

            // http://en.wikipedia.org/wiki/Template:Height
            // {{height|first_unit=first_value|second_unit=second_value|...}}
            elseif ($templateName == 'Height')
            {
                $value = self::catchValue($childrenChilds[0][0]->getText());
                $unit = self::catchUnit($childrenChilds[0][0]->getParent()->getKey());
                // If the TemplateNode has a second PropertyNode ...
                if (isset($childrenChilds[1][0]))
                {
                    $secondUnit = self::catchUnit($childrenChilds[1][0]->getParent()->getKey());
                    // If the height template contains foot and inch they will converted into centimetres.
                    if ($unit == 'ft' && $secondUnit == 'in')
                    {
                        $secondValue = self::catchValue($childrenChilds[1][0]->getText());
                        $ftToCm = $value * 30.48;
                        $inToCm = $secondValue * 2.54;
                        $value = $ftToCm + $inToCm;
                        $unit = 'centimetre';
                    }
                }
            }
            
            // http://en.wikipedia.org/wiki/Template:Auto_in
            // {{Auto in|value|round_to}}
            elseif ($templateName == 'Auto in')
            {
                $value = self::catchValue($childrenChilds[0][0]->getText());
                $unit = "inch";
            }

            // http://en.wikipedia.org/wiki/Template:Km_to_mi
            // {{km to mi|value|...}}
            elseif ($templateName == 'Km to mi')
            {
                $value = self::catchValue($childrenChilds[0][0]->getText());
                $unit = "kilometre";
            }

            // http://en.wikipedia.org/wiki/Template:Km2_to_mi2
            // {{km2 to mi2|value|...}}
            elseif ($templateName == 'Km2 to mi2')
            {
                $value = self::catchValue($childrenChilds[0][0]->getText());
                $unit = "square kilometre";
            }

            // http://en.wikipedia.org/wiki/Template:Pop_density_km2_to_mi2
            // {{Pop density km2 to mi2|value|...}}
            // {{PD km2 to mi2|value|...}}
            elseif ($templateName == 'Pop density km2 to mi2' || $templateName == 'Pd km2 to mi2')
            {
                $value = self::catchValue($childrenChilds[0][0]->getText());
                $unit = "inhabitants per square kilometre";
            }
            ///////////////////////////////////////////////////////////////////////////////////////
            // End of template parsing
            ///////////////////////////////////////////////////////////////////////////////////////

            // If there is no mapping defined for the templat -> return null and log it
            else
            {
                $this->logger->debug("Template not found: \"" . $templateName ."\". " . PHP_EOL.
                                     "Property: " . $this->node->getKey() . PHP_EOL .
                                     "Source: " . $this->node->getSourceUri());
                return null;
            }
            // If there is a mapping but the parsing falied -> return null and log it
            if($value === null || $unit === null)
            {
                $this->logger->debug("Template parsing failed: \"" . $templateName ."\". " . PHP_EOL.
                                     "NodeToString:" . self::nodeToString($node) . PHP_EOL .
                                     //"child0:" .$childrenChilds[0][0]->getText() . PHP_EOL .
                                     //"value:" .$value . PHP_EOL .
                                     //"child1:" .$childrenChilds[1][0]->getText() . PHP_EOL .
                                     //"unit:" .$unit. PHP_EOL .
                                     //"regexUnits:" .$this->stringUnitRegexLabels. PHP_EOL .
                                     "Property: " . $this->node->getKey() . PHP_EOL .
                                     "Source: " . $this->node->getSourceUri());
                return null;
            }
            else
            {
                return self::outputGenerator($value, $unit);
            }
        }
        // If the node is not a TemplateNode run catchTemplates() for all childs
        else
        {
            foreach ($node->getChildren() as $child)
            {
                $result = $this->catchTemplates($child);
                if ($result != null)
                {
                    return $result;
                }
            }
            return null;
        }
    }

    private function catchUnit($input)
    {
        if (preg_match('~(?<!\w)('.$this->stringUnitRegexLabels.')(?!/)(?!\\\)(?!\w)(?!\d)~',$input,$matches))
        {
            return $matches[1];
        }
        else
        {
            return null;
        }
    }

    private function catchValue($input)
    {
        if ($this->language == "en" || $this->language == "ja" || $this->language == "zh")
        {
            if (preg_match('~([0-9]+(\,[0-9]{3})*(\.[0-9]+)?)~',$input,$matches))
            {
                return str_replace(',','',$matches[1]);
            }
            else
            {
                return null;
            }
        }
        else
        {
            if(preg_match('~[\D]([0-9]+(\.[0-9]{3})*(\,[0-9]+)?)[\D]~',$input,$matches))
            {
                return str_replace(',','.',str_replace('.','',$matches[1]));
            }
            else
            {
                return null;
            }
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
     * @param	string	$input	text
     * @return 	array	the value at offset[0] and a UnitDataType object at offset[1].
     */
    private function catchUnitValue($input)
    {
        $output = array();
        // english, japanese and chinese wikepedia artikels
        // numbers with a . as decimal separator and a , as thousand separator
        if ($this->language == "en" || $this->language == "ja" || $this->language == "zh")
        {
        // Merging strings with feet and inches: 'x ft y in'
        // and convert them into centmetres
            if (preg_match('~([0-9]+)\040?ft\040*([0-9]+)\040*in~',$input,$matches))
            {
            // convert to cenitmeter
                $ft_to_cm = $matches[1]*30.48;
                $in_to_cm = $matches[2]*2.54;
                $cm = $ft_to_cm + $in_to_cm;
                $output = self::outputGenerator($cm, $this->dimension->getUnit('centimetre'));
                return $output;
            }
            elseif(preg_match('~^[\D]*([0-9]+(\,[0-9]{3})*(\.[0-9]+)?)(&nbsp;)*\040*\(?\[?\[?('.$this->stringUnitRegexLabels.')(?!/)(?!\\\)(?!\w).*$~',$input,$matches))
            {
                $output = self::outputGenerator($matches[1],$matches[5]);
                return $output;
            }
            // If different units are present, e.g.: 10 mi. (16.0934 km); the first will be returned
            elseif(preg_match('~^[\D]*([0-9]+(\,[0-9]{3})*(\.[0-9]+)?)(&nbsp;)*\040*\(?\[?\[?('.$this->stringUnitRegexLabels.')[\s]*\([\s]*([0-9]+(\.[0-9]+)?)[\s]*('.$this->stringUnitRegexLabels.')[\s]*\)[\s]*$~',$input,$matches))
            {
                $output = self::outputGenerator($matches[1],$matches[5]);
                return $output;
            }
            // http://en.wikipedia.org/wiki/Template:Height
            elseif (preg_match('~^.*?\{\{height\|('.$this->stringUnitRegexLabels.')=([0-9]+(\,[0-9]{3})*(\.[0-9]+)?)(\|('.$this->stringUnitRegexLabels.')=([0-9]+(\,[0-9]{3})*(\.[0-9]+)?))?.*\}\}.*$~',$input,$matches))
            {
                $converted = false;
                $matches[2] = str_replace(',','',$matches[2]);
                $matches[7] = str_replace(',','',$matches[7]);
                // convert to cenitmeter
                if ($matches[1] == 'ft')
                {
                    $matches[2] = $matches[2]*30.48;
                    $converted = true;
                }
                if ($matches[1] == 'in')
                {
                    $matches[2] = $matches[2]*2.54;
                    $converted = true;
                }
                if ($matches[1] == 'yd')
                {
                    $matches[2] = $matches[2]*91.44;
                    $converted = true;
                }
                if ($matches[6] == 'ft' && $converted)
                {
                    $matches[7] = $matches[7]*30.48;
                }
                if ($matches[6] == 'in' && $converted)
                {
                    $matches[7] = $matches[7]*2.54;
                }
                if ($matches[6] == 'yd' && $converted)
                {
                    $matches[7] = $matches[7]*91.44;
                }
                if ($converted === true)
                {
                    $output = self::outputGenerator($matches[2] + $matches[7],'cm');
                    return $output;
                }
                else
                {
                    $output = self::outputGenerator($matches[2] + $matches[7],$matches[1]);
                    return $output;
                }
            }
            // http://en.wikipedia.org/wiki/Template:Convert
            // http://zh.wikipedia.org/wiki/Template:Convert
            elseif (preg_match('~^.*?\{\{\s*[Cc]onvert\s*\|\s*([0-9]+(\,[0-9]{3})*(\.[0-9]+)?)\s*\|\s*('.$this->stringUnitRegexLabels.')(?!/)(?!\\\)(?!\w).*$~',$input,$matches))
            {
                $output = self::outputGenerator($matches[1],$matches[4]);
                return $output;
            }
            // http://en.wikipedia.org/wiki/Template:Km_to_mi
            // http://en.wikipedia.org/wiki/Template:Km2_to_mi2
            // http://en.wikipedia.org/wiki/Template:Pop_density_km2_to_mi2
            // etc.
            elseif (preg_match('~^.*?\{\{([Pp]op\040density\040)?('.$this->stringUnitRegexLabels.')\040to\040[^.\|]+\|\s*([0-9]+(\,[0-9]{3})*(\.[0-9]+)?)\s*\|.*$~',$input,$matches))
            {
                $output= self::outputGenerator($matches[3],$matches[2]);
                return $output;
            }
            // catch numbers and unit: e.q. 1,120,500.55 Kilometer
            elseif(preg_match('~^[\D]*(?<!-)([\-0-9]+(\,[0-9]{3})*(\.[0-9]+)?)\040*\(?\[?\[?('.$this->stringUnitRegexLabels.')(?!\w).*$~',$input,$matches))
            {
                $output = self::outputGenerator($matches[1], $matches[4]);
                return $output;
            }
            // If different units are present, e.g.: 10 miles (16.0934 kilometer); the first will be returned
            elseif(preg_match('~^[\D]*(?<!-)([\-0-9]+(\,[0-9]{3})*(\.[0-9]+)?)(&nbsp;)?\040*\(?\[?\[?('.$this->stringUnitRegexLabels.')[\s]*\([\s]*([0-9]+(\.[0-9]+)?)[\s]*('.$this->stringUnitRegexLabels.')[\s]*\)[\s]*$~',$input,$matches))
            {
                $output = self::outputGenerator($matches[1], $matches[5]);
                return $output;
            }
            elseif (array_key_exists(0, $output))
            {
                if ((($output[0] == null) || ($output[0] == "")) && (preg_match('~^[\D]*(?<!-)([\-0-9]+(\.[0-9]{3})*(\,[0-9]+)?)\040*\(?\[?\[?('.$this->stringUnitRegexLabels.')(?!\\\)(?!\w).*$~',$input,$matches)))
                {
                    $this->setLanguage('de');
                    $output = self::outputGenerator($matches[1], $matches[4]);
                    return $output;
                }
            }
        }

        // for wikipedia artikels in german, french, italian, spanish ...
        // numbers with a , as decimal separator and a . as thousand separator
        else
        {
        // catch number and unit: e.q. 1.120.500,55 km
            if(preg_match('~^[\D]*(?<!-)([\-0-9]+(\.[0-9]{3})*(\,[0-9]+)?)\040*\(?\[?\[?('.$this->stringUnitRegexLabels.')(?!/)(?!\\\)(?!\w).*$~',$input,$matches))
            {
                $output = self::outputGenerator($matches[1], $matches[4]);
                return $output;
            }
            // If different units are present, e.g.: 10 mi. (16.0934 km); the first will be returned
            elseif(preg_match('~^[\D]*(?<!-)([\-0-9]+(\.[0-9]{3})*(\,[0-9]+)?)\040*\(?\[?\[?('.$this->stringUnitRegexLabels.')[\s]*\([\s]*([0-9]+(\,[0-9]+)?)[\s]*('.$this->stringUnitRegexLabels.')[\s]*\)[\s]*$~',$input,$matches))
            {
                $output = self::outputGenerator($matches[1], $matches[4]);
                return $output;
            }
            // http://en.wikipedia.org/wiki/Template:Convert
            // http://es.wikipedia.org/wiki/Plantilla:Convert
            // http://it.wikipedia.org/wiki/Template:Converti
            // http://no.wikipedia.org/wiki/Mal:Convert
            elseif (preg_match('~^.*?\{\{\s*[Cc]onvert(i)?\s*\|\s*([0-9]+(\.[0-9]{3})*(\,[0-9]+)?)\s*\|\s*('.$this->stringUnitRegexLabels.')(?!/)(?!\\\)(?!\w).*$~',$input,$matches))
            {
                $output = self::outputGenerator($matches[2], $matches[5]);
                return $output;
            }
            // catch number and unit: e.q. 1.120.500,55 Kilometer
            elseif(preg_match('~^[\D]*(?<!-)([\-0-9]+(\.[0-9]{3})*(\,[0-9]+)?)\040*\(?\[?\[?('.$this->stringUnitRegexLabels.')(?!\w).*$~',$input,$matches))
            {
                $output = self::outputGenerator($matches[1], $matches[4]);
                return $output;
            }
            // If different units are present, e.g.: 10 miles (16,0934 kilometer); the first will be returned
            elseif(preg_match('~^[\D]*(?<!-)([\-0-9]+(\.[0-9]{3})*(\,[0-9]+)?)\040*\(?\[?\[?('.$this->stringUnitRegexLabels.')[\s]*\([\s]*([0-9]+(\,[0-9]+)?)[\s]*('.$this->stringUnitRegexLabels.')[\s]*\)[\s]*$~',$input,$matches))
            {
                $output = self::outputGenerator($matches[1],$matches[4]);
                return $output;
            }
            elseif (array_key_exists(0, $output))
            {
                if ((($output[0] == null) || ($output[0] == "")) && (preg_match('~^[\D]*(?<!-)([\-0-9]+(\,[0-9]{3})*(\.[0-9]+)?)\040*\(?\[?\[?('.$this->stringUnitRegexLabels.')(?!\\\)(?!\w).*$~',$input,$matches)))
                {
                    $language = "en";
                    $output = self::outputGenerator($matches[1],$matches[4]);
                    return $output;
                }
            }
        }
    }

    /**
     * Returns currency and value
     *
     * The currency and value for Literaltext will be returned
     * eg. $12.99 => [0]12.99 [1]UnitDataType
     *     12,000$ => [0]12000 [1]UnitDataType
     *
     * @param	string	$input	Literaltext, that matched to be a currency value
     * @return 	array	the value at offset[0] and a UnitDataType at Offset[1]
     */
    private function catchMoney($input)
    {
        global $scale;
        // numbers with a . as decimal seperator and a , as thousand seperator
        if ($this->language === 'en' || $this->language === 'ja' || $this->language === 'zh')
        {
        // 51.218 [[1,000,000,000 (number)|billion]] [[USD]] (2007)
            if(preg_match('~^[\D]*(?<!-)([\-0-9]+(\,[0-9]{3})*(\.[0-9]+)?)\s?\[\[[1,0]+\s\(number\)\|('.implode('|',array_keys($scale)).')\]\]\s\[\[('.$this->stringUnitRegexLabels.')\]\].*$~',$input,$matches))
            {
                $num = str_replace(',','',$matches[1])*$scale[strtolower($matches[4])];
                $num = number_format($num, 0, '.', '');
                $output = self::outputGenerator($num,$matches[5]);
                return $output;
            }
            // [[Euro|€]]7.682 [[1000000000 (number)|billion]] (2007)
            elseif(preg_match('~^[\D]*\[\[[\D]+\|('.$this->stringUnitRegexLabels.')\]\]\s?(?<!-)([\-0-9]+(\,[0-9]{3})*(\.[0-9]+)?)\s?\[\[[1,0]+\s\(number\)\|('.implode('|',array_keys($scale)).')\]\].*$~',$input,$matches))
            {
                $num = str_replace(',','',$matches[2])*$scale[strtolower($matches[5])];
                $num = number_format($num, 0, '.', '');
                $output = self::outputGenerator($num, $matches[1]);
                return $output;
            }
            // 10$
            elseif(preg_match('~^[\D]*(?<!-)([\-0-9]+(\,[0-9]{3})*(\.[0-9]+)?)\040*\[?\[?('.$this->stringUnitRegexLabels.').*$~',$input,$matches))
            {
                $output = self::outputGenerator($matches[1], $matches[4]);
                return $output;
            }
            // {{profit}} 358600000 [[United States dollar|USD]] (2006)
            elseif(preg_match('~^[\D]*(?<!-)([\-0-9]+(\,[0-9]{3})*(\.[0-9]+)?)\s*?\[?\[?[\w\s]*\|('.$this->stringUnitRegexLabels.').*$~',$input,$matches))
            {
                $output = self::outputGenerator($matches[1], $matches[4]);
                return $output;
            }
            // currency symbol as prefix: $10
            elseif(preg_match('~^[\D]*('.$this->stringUnitRegexLabels.')\]?\]?\040*(?<!-)([\-0-9]+(\,[0-9]{3})*(\.[0-9]+)?).*$~',$input,$matches))
            {
                $output = self::outputGenerator($matches[2], $matches[1]);
                return $output;
            }
            // catch string like "10 Euro" or "15 Dollar"
            elseif(preg_match('~^[\D]*(?<!-)([\-0-9]+(\,[0-9]{3})*(\.[0-9]+)?)\040*\[?\[?('.$this->stringUnitRegexLabels.').*$~',$input,$matches))
            {
                $output = self::outputGenerator($matches[1],$matches[4]);
                return $output;
            }
            // currency symbol as prefix: Euro 10
            elseif(preg_match('~^[\D]*('.$this->stringUnitRegexLabels.')\]?\]?\040*(?<!-)([\-0-9]+(\,[0-9]{3})*(\.[0-9]+)?)\s*(?!\d).*$~',$input,$matches))
            {
                $output = self::outputGenerator($matches[2],$matches[1]);
                return $output;
            }
        }
        // numbers with a , as decimal seperator and a . as thousand seperator
        else
        {
            if(preg_match('~^[\D]*(?<!-)([\-0-9]+(\.[0-9]{3})*(\,[0-9]+)?)\040*\[?\[?('.$this->stringUnitRegexLabels.').*$~',$input,$matches))
            {
                $output = self::outputGenerator($matches[1], $matches[4]);
                return $output;
            }
            // currency symbol as prefix
            elseif(preg_match('~^[\D]*('.$this->stringUnitRegexLabels.')\040*(?<!-)([\-0-9]+(\.[0-9]{3})*(\,[0-9]+)?).*$~',$input,$matches))
            {
                $output = self::outputGenerator($matches[2],$matches[1]);
                return $output;
            }
            // catch string like "10 Euro" or "Dollar 15"
            elseif(preg_match('~^[\D]*(?<!-)([\-0-9]+(\.[0-9]{3})*(\,[0-9]+)?)\040*\[?\[?('.$this->stringUnitRegexLabels.')[\D]*$~',$input,$matches))
            {
                $output = self::outputGenerator($matches[1],$matches[4]);
                return $output;
            }
            // currency as prefix
            elseif(preg_match('~^[\D]*('.$this->stringUnitRegexLabels.')\040*(?<!-)([\-0-9]+(\.[0-9]{3})*(\,[0-9]+)?)\s*(?!\d).*$~',$input,$matches))
            {
                $output = self::outputGenerator($matches[2],$matches[1]);
                return $output;
            }
        }
    }

    /**
     * Returns duration
     *
     * @param	string	$input	Literaltext, that matched a to be a time
     * @return 	array	time at offset[0] and unit at offset[1]
     */
    private function catchTime($input)
    {
        $output = null;
        if (preg_match('~[\D]*([0-9]+)(:([0-9]{1,2}))?\s*('.$this->stringUnitRegexLabels.')(?!\w)~',$input,$matches))
        {
            if ($matches[1] != '')
            {
                if ($matches[3] == '')
                {
                    $output = self::outputGenerator($matches[1], $matches[4]);
                }
                elseif ($matches[3] < 60)
                {
                    $value = $matches[1] / 60 + $matches[3];
                    if ($this->dimension->getUnit($matches[4]) === 'hour')
                    {
                        $output = self::outputGenerator($value, 'minute');
                    }
                    elseif ($this->dimension->getUnit($matches[4]) === 'minute')
                    {
                        $output = self::outputGenerator($value, 'second');
                    }
                }
            }
        }
        if (preg_match('~[\D]([0-9]+):([0-9]{1,2}):([0-9]{1,2})[\D]~',$input,$matches))
        {
            $value = $matches[1] / 60 + $matches[2];
            $value = $value / 60 + $matches[3];
            $output = self::outputGenerator($value, 'second');
        }
        if(preg_match('~^[\D]*([0-9]+(\,[0-9]{3})*(\.[0-9]+)?)(&nbsp;)*\040*\(?\[?\[?('.$this->stringUnitRegexLabels.')(?!/)(?!\\\)(?!\w).*$~',$input,$matches))
        {
            $output = self::outputGenerator($matches[1],$matches[5]);
        }
        return $output;
    }

    public function __toString()
    {
        $str = '';
        $str .= "Parser".PHP_EOL;
        $str .= "-------".PHP_EOL;
        $str .= "Name:      '".$this->name."'".PHP_EOL;
        $str .= "Dimension: '".$this->dimension->getName()."'".PHP_EOL;
        $str .= "Unit(s)  : ";
        foreach ($this->unitNames as $unit)
        {
            $str .= "'".$unit."' ";
        }
        return $str;
    }
}
}
