<?php
namespace dbpedia\mapping
{

use dbpedia\core\RdfQuad;
use dbpedia\util\PhpUtil;
use dbpedia\core\DBpediaLogger;
use dbpedia\ontology\Ontology;
use dbpedia\ontology\OntologyDataTypeProperty;

class LabelExtractor implements Mapping
{
    private $name = "LabelExtractor";
    
    const DESTINATION_ID = "LabelExtractor.destination";

    const PROPERTY = "rdfs:label";
    
    public static function addProperties( Ontology $ontology )
    {
        $ontology->addProperty(new OntologyDataTypeProperty(self::PROPERTY, $ontology->getClass("owl:Thing"), $ontology->getDataType("xsd:string")));
    }
    
    private $logger;

    protected $ontology;
    private $destination;

    private function __construct($ontology, $context)
    {
        $this->logger = DBpediaLogger::getLogger(__CLASS__);
        $this->ontology = $ontology;
        $this->destination = $context->getDestinations()->getDestination(self::DESTINATION_ID);
    }

    public static function load($ontology, $context)
    {
        $extractor = new LabelExtractor($ontology, $context);
        return $extractor;
    }

    public function extract($node, $subjectUri, $pageContext)
    {
        try
        {
            $quad = new RdfQuad($subjectUri, $this->ontology->getProperty(self::PROPERTY), $node->getRoot()->getTitle(), $node->getSourceUri());
            $this->destination->addQuad($quad);
            return true;
        }
        catch (\InvalidArgumentException $e)
        {
            $this->logger->warn($e->getMessage().' (Page: '.$node->getRoot()->getTitle().')');
            return false;
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
