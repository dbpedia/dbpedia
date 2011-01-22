<?php
namespace dbpedia\mapping
{
use \dbpedia\core\RdfQuad;
use \dbpedia\dataparser\StringParser;

class TemplateMapping implements Mapping
{
    private $name = "TemplateMapping";

    private $logger;

    const TEMPLATE_NAME = "DBpediaTemplateMapping";

    const DESTINATION_ID = "TemplateMapping.destination";
    
    const MAP_TO_CLASS = "mapToClass";
    const MAPPINGS = "mappings";

    const CORRESPONDING_CLASS = "correspondingClass";
    const CORRESPONDING_PROPERTY = "correspondingProperty";

    const TEMPLATE_PROPERTY = "templateProperty";
    const ONTOLOGY_PROPERTY = "ontologyProperty";

    const CLASS_ANNOTATION = "TemplateMapping.class";
    const INSTANCE_URI_ANNOTATION = "TemplateMapping.uri";

    protected $node;
    protected $ontology;

    private $mapToClass;

    private $correspondingClass;
    private $correspondingProperty;

    private $destination = null;

    protected $propertiesMap = array();

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
        $mapping = new TemplateMapping($node, $ontology, $context);
        return $mapping;
    }

    private function buildMappings($context)
    {
        //Load mapToClass property
        $mapToClassName = $this->loadProperty(TemplateMapping::MAP_TO_CLASS);
        $this->mapToClass = $this->ontology->getClass($mapToClassName);
        if(!$this->mapToClass)
        {
            throw new \Exception('Class '.$mapToClassName.' defined by property '.TemplateMapping::MAP_TO_CLASS.' not found.');
        }

        //Load correspondingProperty property
        $correspondingClassName = $this->loadProperty(TemplateMapping::CORRESPONDING_CLASS, true);
        if($correspondingClassName)
        {
            $this->correspondingClass =  $this->ontology->getClass($correspondingClassName);
            if(!$this->correspondingClass)
            {
                throw new \Exception('Class '.$correspondingClassName.' defined by property '.TemplateMapping::CORRESPONDING_CLASS.' not found.');
            }
        }

        //Load correspondingClass property
        $correspondingPropertyName = $this->loadProperty(TemplateMapping::CORRESPONDING_PROPERTY, true);
        if($correspondingPropertyName)
        {
            $this->correspondingProperty =  $this->ontology->getProperty($correspondingPropertyName);
            if(!$this->correspondingProperty)
            {
                throw new \Exception('Property '.$correspondingPropertyName.' defined by property '.TemplateMapping::CORRESPONDING_PROPERTY.' not found.');
            }
        }

        //Load mappings property
        $mappingsProperty = $this->node->getProperty(TemplateMapping::MAPPINGS);
        if($mappingsProperty)
        {
            foreach($mappingsProperty->getChildren('TemplateNode') as $mapping)
            {
                try
                {
                    $propertyMapping = PropertyMapping::load($mapping, $this->ontology, $context);
                    $this->propertiesMap[] = $propertyMapping;
                }
                catch(\Exception $e)
                {
                    $this->logger->warn("[".$this->node->getRoot()->getTitle()."] Couldn't load property mapping: ".$e->getMessage());
                }
            }
        }
    }

    private function loadProperty($key, $optional = false)
    {
        $propertyNode = $this->node->getProperty($key);
        if(!$propertyNode)
        {
            if(!$optional)
            {
                throw new \Exception('No '.$key.' found in template mapping defined in '.$this->node->getRoot()->getTitle());
            }
            else
            {
                return null;
            }
        }
        $text = $propertyNode->getText();
        if(empty($text))
        {
            if(!$optional)
            {
                throw new \Exception($key.' in template mapping defined in '.$this->node->getRoot()->getTitle().' is empty.');
            }
            else
            {
                return null;
            }
        }
        return $text;
    }

    public function getPropertyMappings()
    {
        return $this->propertiesMap;
    }

    public function addPropertyMapping(PropertyMapping $propertyMapping)
    {
        $this->propertiesMap[] = $propertyMapping;
    }

    public function removeProperyMappings()
    {
        $this->propertiesMap = array();
    }

    public function extract($node, $subjectUri, $pageContext)
    {
        $pageNode = $node->getRoot();

        try
        {
            $pageClasses = $pageNode->getAnnotation(self::CLASS_ANNOTATION);
            if (!$pageClasses)
            {
                //So far, no template has been mapped on this page

                //Add ontology instance
                $this->createInstance($subjectUri, $node);

                //Extract properties
                foreach($this->propertiesMap as $propertyMapping)
                {
                    $propertyMapping->extract($node, $subjectUri, $pageContext);
                }
            }
            else
            {
                //This page already has a root template.

                //Create a new instance URI
                $instanceUri = $this->generateUri($subjectUri, $node, $pageContext);

                //Add ontology instance
                $this->createInstance($instanceUri, $node);

                //Check if the root template has been mapped to the corresponding Class of this template
                if ($this->correspondingClass && $this->correspondingProperty)
                {
                    $found = false;
                    foreach($pageClasses as $pageClass)
                    {
                        if($this->correspondingClass->getName() == $pageClass->getName())
                        {
                            $found = true;
                        }
                    }

                    if($found)
                    {
                        //Connect new instance to the instance created from the root template
                        $quad = new RdfQuad($instanceUri, $this->correspondingProperty, $subjectUri, $node->getSourceUri());
                        $this->destination->addQuad($quad);
                    }
                }

                //Extract properties
                foreach($this->propertiesMap as $propertyMapping)
                {
                    $propertyMapping->extract($node, $instanceUri, $pageContext);
                }
            }
        }
        catch (\InvalidArgumentException $e)
        {
            $this->logger->warn($e->getMessage());
            return false;
        }

        return true;
    }

    private function generateUri($subjectUri, $templateNode, $pageContext)
    {
        $properties = $templateNode->getChildren();
        if(count($properties) == 0)
        {
            throw new \Exception("Cannot generate URI for empty Template");
        }

        //Try to find a property which contains 'name'
        $nameProperty = null;
        foreach($properties as $property)
        {
            if(stripos($property->getKey(), 'name') !== false)
            {
                $nameProperty = $property;
                break;
            }
        }

        //If no name property has been found -> Use the first property of the template
        if($nameProperty == null)
        {
            $nameProperty = $properties[0];
        }

        return $pageContext->generateUri($subjectUri, $nameProperty);
    }

    private function createInstance($uri, $node)
    {
        $classes = array();
        for($class = $this->mapToClass; $class != null; $class = $class->getSubClassOf())
        {
            $quad = new RdfQuad($uri, $this->ontology->getProperty("rdf:type"), $class->getUri(), $node->getSourceUri());
            $this->destination->addQuad($quad);
            $classes[] = $class;
        }

        $node->setAnnotation(self::CLASS_ANNOTATION, $classes);
        $node->setAnnotation(self::INSTANCE_URI_ANNOTATION, $uri);

        if(!$node->getRoot()->getAnnotation(self::CLASS_ANNOTATION))
        {
            $node->getRoot()->setAnnotation(self::CLASS_ANNOTATION, $classes);
        }
    }

    public function __toString()
    {
        $str = '';
        $str .= "  Mapping".PHP_EOL;
        $str .= "  -------".PHP_EOL;
        $str .= "  Class: '".$this->name."'".PHP_EOL;
        foreach($this->propertiesMap as $mapping)
        {
            $str .= $mapping.PHP_EOL;
        }
        return $str;
    }
}
}
