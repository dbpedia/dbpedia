<?php
namespace dbpedia\mapping
{
use \dbpedia\dataparser\GeocoordinatesParser;
use \dbpedia\core\DBpediaLogger;
use \dbpedia\core\RDFQuad;
use \dbpedia\ontology\OntologyNamespaces;
use \dbpedia\ontology\dataTypes\EnumerationDataType;
use \dbpedia\dataparser\EnumerationParser;
use \dbpedia\dataparser\NumberParser;
use \dbpedia\dataparser\GeoCoordinate;

class GeocoordinatesMapping extends CustomMapping implements Mapping
{
    const TEMPLATE_NAME = "DBpediaGeocoordinatesMapping";
    const DESTINATION_ID = "GeocoordinatesMapping.destination";

    const COORDINATES_PROPERTY = "coordinates";
    const ONTOLOGY_PROPERTY = "ontologyProperty";

    protected $node;
    protected $ontology;
    private $logger;

    private $pageContext;

    private $destination = null;

    private $parser;
    private $geoCoordinate;
    private $ontologyProperty;

    private $latitude;
    private $latitudeDegrees;
    private $latitudeMinutes;
    private $latitudeSeconds;
    private $latitudeDirection;
    private $longitude;
    private $longitudeDegrees;
    private $longitudeMinutes;
    private $longitudeSeconds;
    private $longitudeDirection;
    private $coordinates;

    private function __construct($node, $ontology, $context)
    {
        $this->node = $node;
        $this->ontology = $ontology;
        $this->destination = $context->getDestinations()->getDestination(self::DESTINATION_ID);
        $this->buildMappings($context);
        $this->logger = DBpediaLogger::getLogger(__CLASS__);
    }

    public static function load($node, $ontology, $context)
    {
       $mapping = new GeocoordinatesMapping($node, $ontology, $context);
       return $mapping;
    }

    private function buildMappings($context)
    {
        $ontologyProperty = $this->node->getProperty(self::ONTOLOGY_PROPERTY);
        if (isset($ontologyProperty))
        {
            $ontologyPropertyName = $ontologyProperty->getText();
            $this->ontologyProperty = $this->ontology->getProperty($ontologyPropertyName);
            if(!$this->ontologyProperty)
            {
                throw new \Exception(get_class($this)." mapping defines an unknown ontology property: ".$ontologyPropertyName);
            }
        }

        //$coordinatesNode = $this->node->getProperty('coordinates');
        $this->coordinates = self::loadProperty('coordinates');

        $this->latitude = self::loadProperty('latitude');
        $this->longitude = self::loadProperty('longitude');

        $this->longitudeDegrees = self::loadProperty('longitudeDegrees');
        $this->longitudeMinutes = self::loadProperty('longitudeMinutes');
        $this->longitudeSeconds = self::loadProperty('longitudeSeconds');
        $this->longitudeDirection = self::loadProperty('longitudeDirection');
        $this->latitudeDegrees = self::loadProperty('latitudeDegrees');
        $this->latitudeMinutes = self::loadProperty('latitudeMinutes');
        $this->latitudeSeconds = self::loadProperty('latitudeSeconds');
        $this->latitudeDirection = self::loadProperty('latitudeDirection');

        // case 1: coordinates set (all coordinates in one template property)
        if (isset ($this->coordinates))
        {
            $this->parser = new GeocoordinatesParser(GeocoordinatesParser::COORDINATES);
        }
        // case 2: latitude and longitude set (all coordinates in two template properties)
        else if (isset ($this->latitude) && isset ($this->longitude))
        {
            $this->geoCoordinate = new GeoCoordinate();
        }
        // case 3: more than two latitude and longitude properties (all coordinates in more than two template properties)
        else if (isset ($this->latitudeDegrees) && isset ($this->longitudeDegrees))
        {
            $this->geoCoordinate = new GeoCoordinate();
        }
    }

    private function loadProperty($key)
    {
        $propertyNode = $this->node->getProperty($key);
        if(!$propertyNode)
        {
            return null;
        }
        $text = $propertyNode->getText();
        if(empty($text))
        {
           return null;
        }
        return $text;
    }

    public function extract($node, $subjectUri, $pageContext)
    {
        $this->pageContext = $pageContext;

        if (isset ($this->coordinates))
        {
            $propertyNode = $node->getProperty($this->coordinates);
            if ($propertyNode)
            {
                $result = $this->parser->parse($propertyNode);
                if ($result['lat'] != '' && $result['long'] != '')
                {
                    if($this->writeGeoQuad($node, $subjectUri, $result['lat'], $result['long']) === true )
                    {
                        return true;
                    }
                }
            }
        }
        // must be a IF query not ELSEIF, because in the mappings sometimes all Properties are set
        // but not all have content. And sometimes the infoboxes aren't filled correct.
        // Example: Berlin, the Infobox_German_Bundesland has only a latitude and a longitude property,
        //          but the Berlin article has properties for each degrees, minutes and seconds.
        if (isset ($this->latitude) && isset ($this->longitude))
        {
            $latitudeNode = $node->getProperty($this->latitude);
            $longitudeNode = $node->getProperty($this->longitude);
            if ($latitudeNode && $longitudeNode)
            {
                
                $this->geoCoordinate->parseLatitude($latitudeNode);
                $this->geoCoordinate->parseLongitude($longitudeNode);
                $long = $this->geoCoordinate->getLongitude();
                $lat = $this->geoCoordinate->getLatitude();
                if ($lat != '' && $long != '')
                {
                    if($this->writeGeoQuad($node, $subjectUri, $lat, $long) === true )
                    {
                        return true;
                    }
                }
            }
        }
        // must be a IF query not ELSEIF
        if (isset ($this->latitudeDegrees) && isset ($this->longitudeDegrees))
        {
            $latitudeDegNode = $node->getProperty($this->latitudeDegrees);
            $latitudeMinNode = $node->getProperty($this->latitudeMinutes);
            $latitudeSecNode = $node->getProperty($this->latitudeSeconds);
            $latitudeHemNode = $node->getProperty($this->latitudeDirection);
            $longitudeDegNode = $node->getProperty($this->longitudeDegrees);
            $longitudeMinNode = $node->getProperty($this->longitudeMinutes);
            $longitudeSecNode = $node->getProperty($this->longitudeSeconds);
            $longitudeHemNode = $node->getProperty($this->longitudeDirection);
            if ($latitudeDegNode && $longitudeDegNode)
            {
                $this->geoCoordinate->parseLatitude($latitudeDegNode, $latitudeMinNode, $latitudeSecNode, $latitudeHemNode);
                $this->geoCoordinate->parseLongitude($longitudeDegNode, $longitudeMinNode, $longitudeSecNode, $longitudeHemNode);
                $long = $this->geoCoordinate->getLongitude();
                $lat = $this->geoCoordinate->getLatitude();
                if ($lat != '' && $long != '')
                {
                    if($this->writeGeoQuad($node, $subjectUri, $lat, $long) === true )
                    {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    private function writeGeoQuad($node, $subjectUri, $lat, $long)
    {
        if ($lat != '' && $long != '')
        {
            try
            {
                if (isset($this->ontologyProperty))
                {
                    // TODO: replace label with local part w/o class
                    $ontologyPropertyLabel = $this->ontologyProperty->getLabel();
                    $originalSubjectUri = $subjectUri;
                    $subjectUri = $this->pageContext->generateUri(OntologyNamespaces::getUri($node->getRoot()->getTitle()->encoded(), OntologyNamespaces::DBPEDIA_INSTANCE_NAMESPACE).'__'.$ontologyPropertyLabel, null);
                    $quad = new RdfQuad($subjectUri, $this->ontology->getProperty("rdf:type"), $this->ontology->getClass("gml:_Feature")->getUri(), $node->getSourceUri());
                    $this->destination->addQuad($quad);
                    $quad = new RdfQuad($originalSubjectUri, $this->ontologyProperty, $subjectUri, $node->getSourceUri());
                    $this->destination->addQuad($quad);
                }
                $geoLat = $this->ontology->getProperty("geo:lat");
                $geoLong = $this->ontology->getProperty("geo:long");
                $georssPoint = $this->ontology->getProperty("georss:point");
                $quad = new RdfQuad($subjectUri, $geoLat, $lat, $node->getSourceUri());
                $this->destination->addQuad($quad);
                $quad = new RdfQuad($subjectUri, $geoLong, $long, $node->getSourceUri());
                $this->destination->addQuad($quad);
                $quad = new RdfQuad($subjectUri, $georssPoint, $lat." ".$long, $node->getSourceUri());
                $this->destination->addQuad($quad);

                return true;
            }
            catch (\InvalidArgumentException $e)
            {
                $this->logger->warn($e->getMessage().' (Page: '.$node->getRoot()->getTitle().')');
            }
        }
    }

    public function __toString()
    {
        $str = __CLASS__.PHP_EOL;
        return $str;
    }
}
}
