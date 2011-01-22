<?php
namespace dbpedia\dataparser
{
use dbpedia\core\DBpediaLogger;
use dbpedia\wikiparser\LinkNode;
use dbpedia\wikiparser\Node;
use dbpedia\wikiparser\PropertyNode;
use dbpedia\wikiparser\TemplateNode;
use dbpedia\wikiparser\TextNode;
use dbpedia\dataparser\GeoCoordinate;
/**
 * Description of GeocoordinatesParser
 *
 * @author Paul Kreis
 */
class GeocoordinatesParser implements DataParser
{
    const COORDINATES = "coordinates";
    const LATITUDE = "latitude";
    const LONGITUDE = "longitude";

    private $name = 'GeocoordinatesParser';
    private $type = null;
    private $language = null;
    private $logger = null;
    private $node = null;
    private $geoCoordinate;
    private $coordinateBelongsToArticle = false;
    private $acceptOnlyCoordinatesBelongingToArticle;

    public function __construct($type, $acceptOnlyCoordinatesBelongingToArticle = false)
    {
        $this->type = $type;
        $this->setLanguage('en');
        $this->logger = DBpediaLogger::getLogger(__CLASS__);
        $this->geoCoordinate = new GeoCoordinate();
        $this->acceptOnlyCoordinatesBelongingToArticle = $acceptOnlyCoordinatesBelongingToArticle;
    }

    public function setLanguage($language)
    {
        $this->language = $language;
    }

    public function parse(Node $node)
    {
        $this->node = $node;
        $result = null;
        if ($node instanceof TemplateNode)
        {
            $result = self::catchTemplate($node);
        }
        else if ($node instanceof PropertyNode)
        {
            $children = $node->getChildren('TemplateNode');
            foreach ($children as $child)
            {
                $result = self::catchTemplate($child);
                if ($result != null)
                {
                    break;
                }
            }
            if ($result == null)
            {
                $nodeText = self::nodeToString($node);
                $result = self::parseGeoCoord($nodeText);
                if ($result === null)
                {
                    $this->logger->debug("No geocoordinate found in: {$result}" . PHP_EOL .
                            "Property: " . $node->getKey() . PHP_EOL .
                            "Source: " . $this->node->getSourceUri());
                }
            }
        }
        else
        {
            throw new DataParserException("Wrong instance.");
        }
        if ($this->acceptOnlyCoordinatesBelongingToArticle && !$this->coordinateBelongsToArticle)
        {
            return null;
        }
        return $result;
    }
    

    /**
     *
     * @param TemplateNode $templateNode
     * @return bool
     */
    private function catchTemplate($templateNode)
    {
        if ($templateNode instanceof TemplateNode)
        {
            $children = $templateNode->getChildren();
            $templateName = $templateNode->getTitle()->decoded();

            foreach ($children as $index => $childNode)
            {
                // creates an array of TextNodes from the PropertyNodes of the TemplateNode
                $childrenTextNodes[$index] = $childNode->getChildren('TextNode');
                
            }
            
            //{{coord|latitude|longitude|coordinate parameters|template parameters}}
            //{{coord|dd|N/S|dd|E/W|coordinate parameters|template parameters}}
            //{{coord|dd|mm|N/S|dd|mm|E/W|coordinate parameters|template parameters}}
            //{{coord|dd|mm|ss|N/S|dd|mm|ss|E/W|coordinate parameters|template parameters}}
            if (preg_match('~coord~i', $templateName))
            {
                $lat = $children[0];
                $latMin = new PropertyNode();
                $latSec = new PropertyNode();
                $latHem = new PropertyNode();
                $long = new PropertyNode();
                $longMin = new PropertyNode();
                $longSec = new PropertyNode();
                $longHem = new PropertyNode();

                if (isset($childrenTextNodes[1][0]) && preg_match('~^(N|S)$~i', $childrenTextNodes[1][0]->getText()))
                { 
                    $long = $children[2];
                }
                elseif (isset($childrenTextNodes[2][0]) && preg_match('~^(N|S)$~i', $childrenTextNodes[2][0]->getText()))
                {
                    $latMin = $children[1];
                    $latHem = $children[2];
                    $long = $children[3];
                    $longMin = $children[4];
                    $longHem = $children[5];
                }
                elseif (isset($childrenTextNodes[3][0]) && preg_match('~^(N|S)$~i', $childrenTextNodes[3][0]->getText()))
                {
                    $latMin = $children[1];
                    $latSec = $children[2];
                    $latHem = $children[3];
                    $long = $children[4];
                    $longMin = $children[5];
                    $longSec = $children[6];
                    $longHem = $children[7];
                }
                else
                {
                    $long = $children[1];
                }
                
                if ($this->acceptOnlyCoordinatesBelongingToArticle)
                {
                    foreach ($children as $index => $childNode)
                    {
                        if ($childNode->getKey() == 'display')
                        {
                            $display = explode(",", $childrenTextNodes[$index][0]->getText());
                            if (in_array("title", $display) || in_array("t", $display))
                            {
                                $this->coordinateBelongsToArticle = true;
                            }
                        }
                    }
                }
                $this->geoCoordinate->parseLatitude($lat, $latMin, $latSec, $latHem);
                $this->geoCoordinate->parseLongitude($long, $longMin, $longSec, $longHem);
                return array('lat' => $this->geoCoordinate->getLatitude(), 'long' => $this->geoCoordinate->getLongitude());
            }
            // The following templates don't exist anymore
            elseif (preg_match('~coor\040(title|at)~i', $templateName))
            {
                return null;
            }
            elseif (preg_match('~geolinks~i', $templateName))
            {
                return null;
            }
            elseif (preg_match('~mapit~i', $templateName))
            {
                return null;
            }
            elseif (preg_match('~koordinate~i', $templateName))
            {
                return null;
            }

            elseif (preg_match('~coordinate~i', $templateName))
            {
                return null;
            }
        }
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
     * @return array [lat]=>'52.51666666',[long]=>'13.41666667'
     */
    private function createOutput ($latsec,$latmin,$latdeg,$latHemisphere,$longsec,$longmin,$longdeg,$longHemisphere)
    {
        $lat = $latdeg + ($latmin + $latsec/60)/60;
        $long = $longdeg + ($longmin + $longsec/60)/60;

        $lat = number_format($lat,8,'.','');
        $long = number_format($long,8,'.','');

        if ($latHemisphere == 'S')
        {
            $lat = $lat * -1;
        }
        if ($longHemisphere == 'W')
        {
            $long = $long * -1;
        }
        $lat = number_format($lat,8,'.','');
        $long = number_format($long,8,'.','');

        switch ($this->type)
        {
            case self::COORDINATES :
                if ($lat != '' && $long != '')
                {
                    return array('lat'=>$lat,'long'=>$long);
                }
                else
                {
                    $this->logger->debug("No coordinates found" . PHP_EOL .
                            "Property: " . $this->node->getKey() . PHP_EOL .
                            "Source: " . $this->node->getSourceUri());
                }
                break;
            default :
                return null;
                break;
        }
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
     * @return	array	'lat'=>'52.51666666','long'=>'13.41666667'
     *
     */
    private function parseGeoCoord($input)
    {

        // {{coord|51|30|29|N|00|07|29|W|display=title}}
        if (preg_match('~^\s?([0-9]{1,2})\s([0-9]{1,2})\s([.0-9]{1,8})?\s?(N|S)\s([0-9]{1,3})\s([0-9]{1,2})\s([.0-9]{1,8})?\s?(E|W|O)\s.*~',$input,$matches))
        {
            return $this->createOutput($matches[3],$matches[2],$matches[1],$matches[4],$matches[7],$matches[6],$matches[5],$matches[8]);
        }
        // {{coord|44.112|N|87.913|W|display=title}}
        elseif (preg_match('~^\s?([.0-9]{1,8})\s([N|S])\s([.0-9]{1,8})\s(W|E|O)\s.*~',$input,$matches))
        {
            return $this->createOutput(0,0,$matches[1],$matches[2],0,0,$matches[3],$matches[4]);
        }
        // {{coord|44.112|-87.913|display=title}}
        elseif (preg_match('~^\s([-.0-9]{1,8})\s([-.0-9]{1,8})\s~',$input,$matches))
        {
            return $this->createOutput(0,0,$matches[1],'nothing',0,0,$matches[2],'nothing');
        }
        // 38º32' N 2º89' W
        elseif (preg_match('~^([0-9]{1,2})º([0-9]{1,2})\'([0-9]{1,2}(\.[0-9]{1,2})?)?\"?[\s]?(N|S)[\s]([0-9]{1,3})º([0-9]{1,2})\'([0-9]{1,2}(\.[0-9]{1,2})?)?\"?[\s]?(E|W|O)$~',$input,$matches))
        {
            return $this->createOutput($matches[3],$matches[2],$matches[1],$matches[5],$matches[8],$matches[7],$matches[6],$matches[10]);
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
        if ($node instanceof TextNode)
        {
            $string .= $node->getText() . " ";
        }
        elseif ($node instanceof PropertyNode || $node instanceof TemplateNode || $node instanceof LinkNode)
        {
            $children = $node->getChildren();
            foreach ($children as $childNode)
            {
                $string .= self::nodeToString($childNode);
            }
        }
        return $string;
    }

    public function __toString()
    {
        return "Parser '".$this->name."'".PHP_EOL;
    }
}
}
