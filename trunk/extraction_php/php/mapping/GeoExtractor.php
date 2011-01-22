<?php
namespace dbpedia\mapping
{
use dbpedia\core\DBpediaLogger;
use dbpedia\dataparser\GeocoordinatesParser;
use dbpedia\core\RdfQuad;

class GeoExtractor implements Mapping
{
    const DESTINATION_ID = "GeoExtractor.destination";
    
    private $logger;
    protected $ontology;
    private $destination;
    private $parser;
        
    private function __construct($ontology, $context)
    {
        $this->logger = DBpediaLogger::getLogger(__CLASS__);
        $this->ontology = $ontology;
        $this->destination = $context->getDestinations()->getDestination(self::DESTINATION_ID);
        $this->parser = new GeocoordinatesParser(GeocoordinatesParser::COORDINATES, true);
    }

    public static function load($ontology, $context)
    {
        $extractor = new GeoExtractor($ontology, $context);
        return $extractor;
    }
    
    public function extract($node, $subjectUri, $pageContext)
    {
    	/* Iterate through all root templates. Not recursing into templates as these are presumed to be handled by template-based mechanisms (GeocoordinatesMapping.php). */
	$children = $node->getChildren();
        foreach ($children as $child)
        {
            if ($child instanceof \dbpedia\wikiparser\TemplateNode)
            {
                $result = $this->parser->parse($child);
                if ($result['lat'] != '' && $result['long'] != '')
                {
                    if ($this->writeGeoQuad($node, $subjectUri, $result['lat'], $result['long']) === true )
                    {
                        return true;
                    }
                }
            }
        }
    }
    
    private function writeGeoQuad($node, $subjectUri, $lat, $long)
    {
        if ($lat != '' && $long != '')
        {
            try
            {
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
        $str = '';
        $str .= "  ".get_class().PHP_EOL;
        $str .= "  -------".PHP_EOL;
        return $str;
    }
}
}
