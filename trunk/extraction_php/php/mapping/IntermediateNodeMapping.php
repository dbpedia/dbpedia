<?php
namespace dbpedia\mapping
{
use dbpedia\core\RdfQuad;
use dbpedia\ontology\OntologyNamespaces;

class IntermediateNodeMapping extends PropertyMapping implements Mapping
{
    private $name = "IntermediateNodeMapping";
    
    private $logger;

    const TEMPLATE_NAME = "DBpediaIntermediateNodeMapping";

    const DESTINATION_ID = "IntermediateNodeMapping.destination";
    
    const NODE_CLASS = "nodeClass";
    const CORRESPONDING_PROPERTY = "correspondingProperty";
    const NODE_URI = "nodeURI";
    const MAPPINGS = "mappings";

    protected $node;
    protected $ontology;

    private $propertiesMap = array();
    private $nodeClass;
    private $correspondingProperty;
    private $nodeUri;

    private $destination = null;

    private function __construct($node, $ontology, $context)
    {
        $this->logger = \dbpedia\core\DBpediaLogger::getLogger(__CLASS__);
        $this->node = $node;
        $this->ontology = $ontology;
        $this->destination = $context->getDestinations()->getDestination(self::DESTINATION_ID);
        $this->buildMappings($context);
    }

    public static function load($node, $ontology, $context)
    {
       $mapping = new IntermediateNodeMapping($node, $ontology, $context);
       return $mapping;
    }

    private function buildMappings( $context )
    {
        $nodeClassPropertyNode = $this->node->getProperty(IntermediateNodeMapping::NODE_CLASS);
        $correspondingPropertyPropertyNode = $this->node->getProperty(IntermediateNodeMapping::CORRESPONDING_PROPERTY);
        $nodeUriPropertyNode = $this->node->getProperty(IntermediateNodeMapping::NODE_URI);
        $mappingsPropertyNode = $this->node->getProperty(IntermediateNodeMapping::MAPPINGS);

        if ($nodeClassPropertyNode)
        {
            $nodeClassName = $nodeClassPropertyNode->getText();
            $this->nodeClass = $this->ontology->getClass($nodeClassName);
            if(!$this->nodeClass)
            {
                throw new \Exception("IntermediateNodeMapping mapping defines an unknown corresponding class: ".$nodeClassName);
            }
        }
        if ($correspondingPropertyPropertyNode)
        {
            $correspondingPropertyName = $correspondingPropertyPropertyNode->getText();
            $this->correspondingProperty = $this->ontology->getProperty($correspondingPropertyName);
            if(!$this->correspondingProperty)
            {
                throw new \Exception(get_class($this)." mapping defines an unknown corresponding property: ".$correspondingPropertyName);
            }
        }
        if ($nodeUriPropertyNode)
        {
            $this->nodeUri = $nodeUriPropertyNode->getText();
        }
        if ($mappingsPropertyNode)
        {
            $mappings = $mappingsPropertyNode->getChildren();
            foreach ($mappings as $mapping)
            {
                if ($mapping instanceof \dbpedia\wikiparser\TemplateNode)
                {
                    if ($mapping->getTitle()->decoded() == SimplePropertyMapping::TEMPLATE_NAME)
                    {
                        try
                        {
                            $simplePropertyMapping = SimplePropertyMapping::load($mapping, $this->ontology, $context);
                            $this->propertiesMap[] = $simplePropertyMapping;
                        }
                        catch (\Exception $e)
                        {
                            // TODO better message
                            $this->logger->warn($e->getMessage(), $e);
                        }
                    }
                }
            }

            if(empty($this->propertiesMap))
            {
                throw new \Exception("IntermediateNodeMapping in '" . $this->node->getRoot()->getTitle() . "'does not define any valid property mapping.");
            }
        }
    }

    public function extract($node, $subjectUri, $pageContext)
    {
        $affectedTemplateProperties = array();
        foreach($this->propertiesMap as $propertyMapping)
        {
            if ($propertyMapping->getTemplatePropertyName())
            {
                $affectedTemplateProperties[] = $propertyMapping->getTemplatePropertyName();
            }
        }

        $affectedTemplateProperties = array_unique($affectedTemplateProperties);
        if (sizeof($affectedTemplateProperties) == 1)
        {
            foreach ($affectedTemplateProperties as $affectedTemplateProperty)
            {
                $propertyNode = $node->getProperty($affectedTemplateProperty);
                if ($propertyNode && (sizeof($propertyNode->getChildren()) > 0))
                {
                    $oldPropertyNode = $propertyNode;
                    $newPropertyNodes = $propertyNode->split("/<br\s*\/?>/");
                    if (sizeof($newPropertyNodes) > 1)
                    {
                        foreach ($newPropertyNodes as $newPropertyNode)
                        {
                            $instanceUri = $pageContext->generateUri($subjectUri, $newPropertyNode);

                            $node->addProperty($newPropertyNode);

                            $this->createInstance($node, $instanceUri, $subjectUri, $pageContext);
                        }
                        $node->addProperty($oldPropertyNode);
                    }
                    else
                    {
                        $instanceUri = $pageContext->generateUri($subjectUri, $propertyNode);

                        $this->createInstance($node, $instanceUri, $subjectUri, $pageContext);
                    }
                }
            }
        }
        else
        {
            $this->logger->warn("Implement IntermediaNodeMapping for more than one affected template property!");
        }

        return true;
    }

    private function createInstance($node, $instanceUri, $originalSubjectUri, $pageContext)
    {
        // extract quads
        $result = false;
        foreach($this->propertiesMap as $propertyMapping)
        {
            $result |= $propertyMapping->extract($node, $instanceUri, $pageContext);
        }

        // write types
        if($result)
        {
            try
            {
                for($class = $this->nodeClass; $class != null; $class = $class->getSubClassOf())
                {
                    $quad = new RdfQuad($instanceUri, $this->ontology->getProperty("rdf:type"), $class->getUri(), $node->getSourceUri());
                    $this->destination->addQuad($quad);
                }
            }
            catch (\InvalidArgumentException $e)
            {
                $this->logger->warn($e->getMessage());
            }

            try
            {
                $quad = new RdfQuad($originalSubjectUri, $this->correspondingProperty, $instanceUri, $node->getSourceUri());
                $this->destination->addQuad($quad);
            }
            catch (\InvalidArgumentException $e)
            {
                $this->logger->warn($e->getMessage());
            }
        }
    }

    public function __toString()
    {
        $str = '';
        $str .= "  Mapping".PHP_EOL;
        $str .= "  -------".PHP_EOL;
        $str .= "  Class: '".$this->name."'".PHP_EOL;
        return $str;
    }
}
}
