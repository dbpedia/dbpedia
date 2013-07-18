<?php
namespace dbpedia\dataparser
{
use \dbpedia\core\DBpediaLogger;
use \dbpedia\ontology\dataTypes\EnumerationDataType;
use \dbpedia\dataparser\NumberParser;
use \dbpedia\dataparser\EnumerationParser;
use \dbpedia\wikiparser\PropertyNode;
use \dbpedia\wikiparser\Node;
use \dbpedia\ontology\dataTypes\DataType;

/**
 * Description of GeoCoordinate
 *
 * @author Paul
 */
class GeoCoordinate
{
    private $logger = null;

    private $latitudeDegrees = null;
    private $latitudeMinutes = null;
    private $latitudeSeconds = null;
    private $latitudeDirection = null;
    private $longitudeDegrees = null;
    private $longitudeMinutes = null;
    private $longitudeSeconds = null;
    private $longitudeDirection = null;
    private $directionDatatype;
    private $directionParser;
    private $numberParser;


    public function __construct()
    {
        $this->logger = DBpediaLogger::getLogger(__CLASS__);
        $this->directionDatatype = new EnumerationDataType('Direction');
        $this->directionDatatype->addLiteral('W');
        $this->directionDatatype->addLiteral('E', array('O'));
        $this->directionDatatype->addLiteral('N');
        $this->directionDatatype->addLiteral('S');
        $this->directionParser = new EnumerationParser($this->directionDatatype);
        $dataType = new DataType('xsd:double', 'http://bla');
        $this->numberParser = new NumberParser($dataType);
    }

    public function parseLatitude(PropertyNode $degrees, PropertyNode $minutes = null, PropertyNode $seconds = null, PropertyNode $direction = null)
    {
        if (isset ($degrees))
        {
            $this->latitudeDegrees = $this->numberParser->parse($degrees);
            if ($this->latitudeDegrees  === null)
            {
                $this->latitudeDegrees = self::parseDegrees($degrees);
            }
            if ($this->latitudeDegrees  === null)
            {
                $this->logger->debug("No Degrees found: " . __CLASS__ . " - line: " . __LINE__);
            }
        }
        if (isset ($minutes))
        {
            $this->latitudeMinutes = $this->numberParser->parse($minutes);
        }
        if (isset ($seconds))
        {
            $this->latitudeSeconds = $this->numberParser->parse($seconds);
        }
        if (isset ($direction))
        {
            $this->latitudeDirection = $this->directionParser->parse($direction);
        }
    }

    public function parseLongitude(PropertyNode $degrees, PropertyNode $minutes = null, PropertyNode $seconds = null, PropertyNode $direction = null)
    {
        if (isset ($degrees))
        {
            $this->longitudeDegrees = $this->numberParser->parse($degrees);
            if ($this->longitudeDegrees  === null)
            {
                $this->longitudeDegrees = self::parseDegrees($degrees);
            }
            if ($this->longitudeDegrees  === null)
            {
                $this->logger->debug("No Degrees found: " . __CLASS__ . " - line: " . __LINE__);
            }
        }
        if (isset ($minutes))
        {
            $this->longitudeMinutes = $this->numberParser->parse($minutes);
        }
        if (isset ($seconds))
        {
            $this->longitudeSeconds = $this->numberParser->parse($seconds);
        }
        if (isset ($direction))
        {
            $this->longitudeDirection = $this->directionParser->parse($direction);
        }
    }

    private static function parseDegrees(Node $node)
    {
        $result = null;
        foreach($node->getChildren() as $child)
        {
            if ($child instanceof TextNode)
            {
                if (preg_match('~(-?[.0-9]{1,12})~', $child->getText(), $matches))
                {
                    $result = $matches[1];
                }

            }
            else
            {
                $result = self::parseDegrees($child);
            }
            if($result !== null)
            {
                return $result;
            }
        }
    }

    public function getLatitude()
    {
        if ($this->latitudeDegrees === null)
        {
            return null;
        }
        else
        {
            $lat = $this->latitudeDegrees + ($this->latitudeMinutes + $this->latitudeSeconds/60)/60;
            if ($this->latitudeDirection == 'S')
            {
                $lat = $lat * -1;
            }
            $lat = number_format($lat,8,'.','');
            return $lat;
        }
    }
    public function getLongitude()
    {
        if ($this->latitudeDegrees === null)
        {
            return null;
        }
        else
        {
            $long = $this->longitudeDegrees + ($this->longitudeMinutes + $this->longitudeSeconds/60)/60;
            if ($this->longitudeDirection == 'W')
            {
                $long = $long * -1;
            }
            $long = number_format($long,8,'.','');
            return $long;
        }
    }

    public function __toString()
    {
        $str = __CLASS__.PHP_EOL;
        return $str;
    }
}
}
