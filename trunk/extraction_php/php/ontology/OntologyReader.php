<?php
namespace dbpedia\ontology
{

use dbpedia\wikiparser\PageNode;
use dbpedia\util\PhpUtil;
use dbpedia\util\StringUtil;

/**
 * Loads an ontology from configuration files using the DBpedia mapping language.
 */
class OntologyReader
{
    private $logger;

    public function __construct()
    {
        $this->logger = \dbpedia\core\DBpediaLogger::getLogger(__CLASS__);
    }

    /**
     * Loads an ontology from configuration files using the DBpedia mapping language.
     *
     * @param $pages Array of PageNode instances containing the configuration files
     * @return Ontology The ontology
     */
    public function read(array $pages)
    {
        $ontology = new Ontology();
        
        $ontology->addClass(new OntologyClass("owl:Thing"));
        
        OntologyDataTypes::addDataTypes($ontology);

        // TODO: range should be rdfs:Class
        $ontology->addProperty(new OntologyProperty("rdf:type", $ontology->getClass("owl:Thing")));
        
        foreach($pages as $pageNode)
        {
            OntologyReader::createClasses($ontology, $pageNode);
        }
        
        foreach($pages as $pageNode)
        {
            OntologyReader::linkClasses($ontology, $pageNode);
        }

        return $ontology;
    }

    /**
     * Loads all classes and properties but does not link them.
     *
     * @param $ontology The ontology instance
     * @param $pageNode The page node of the configuration page
     */
    private function createClasses( $ontology, $pageNode )
    {
        // PHP keeps going if param type is wrong, but we want an exception
        PhpUtil::assertType($ontology, 'dbpedia\ontology\Ontology', 'ontology');
        PhpUtil::assertType($pageNode, 'dbpedia\wikiparser\PageNode', 'page node');
        
        $name = self::getPageName($pageNode);

        foreach($pageNode->getChildren('TemplateNode') as $node)
        {
            $templateName = $node->getTitle()->encoded();
            if($templateName == OntologyClass::TEMPLATE_NAME)
            {
                $ontClass = new OntologyClass($name);
                $labelProperty = $node->getProperty("rdfs:label");
                if($labelProperty)
                {
                    $ontClass->setLabel($labelProperty->getText());
                }
                else
                {
                    $this->logger->warn("Class ".$ontClass->getUri()." does not define any label.");
                }
                $ontology->addClass($ontClass);
            }
            else if($templateName == OntologyObjectProperty::TEMPLATE_NAME || $templateName == OntologyDataTypeProperty::TEMPLATE_NAME)
            {
                if($templateName == OntologyObjectProperty::TEMPLATE_NAME)
                {
                    $ontProperty = new OntologyObjectProperty($name);
                }
                else
                {
                    $ontProperty = new OntologyDataTypeProperty($name);
                }

                $labelProperty = $node->getProperty("rdfs:label");
                if($labelProperty)
                {
                    $ontProperty->setLabel($labelProperty->getText());
                }
                else
                {
                    $this->logger->warn("Property without any label found on page ".$pageName);
                }

                $typeProperty = $node->getProperty("rdf:type");
                if($typeProperty)
                {
                    if($typeProperty->getText() == 'owl:FunctionalProperty')
                    {
                        $ontProperty->setFunctional(true);
                    }
                    else
                    {
                        $this->logger->warn("Property with an invalid type found on page ".$pageName);
                    }
                }

                $ontology->addProperty($ontProperty);
            }
        }
    }

    /**
     * Links all classes and properties according to the configuration.
     * 
     * TODO: It would be cleaner to use a two-stage process: Copy the data (class and property
     * names etc.) from the page nodes to intermediary data structures (probably simple maps
     * or simple data objects), then build the final ontology objects from these data
     * structures. No need to go through the page nodes twice, no need to modify or remove 
     * classes after they were added to the ontology. Would also enable checks for cyclic
     * dependencies etc.
     *
     * @param $ontology The ontology instance
     * @param $pageNode The page node of the configuration page
     */
    private function linkClasses(Ontology $ontology, PageNode $pageNode)
    {
        $name = self::getPageName($pageNode);

        foreach($pageNode->getChildren('TemplateNode') as $node)
        {
            //Class
            $templateName = $node->getTitle()->encoded();
            if($templateName == OntologyClass::TEMPLATE_NAME)
            {
                $ontologyClass = $ontology->getClass($name);

                //Sub class
                $subClassNode = $node->getProperty("rdfs:subClassOf");
                if($subClassNode)
                {
                    try
                    {
                        $parentClass = $ontology->getClass($subClassNode->getText());
                        $ontologyClass->setSubClassOf($parentClass);
                    }
                    catch(\InvalidArgumentException $e)
                    {
                        $this->logger->warn("subClassOf ".$name." (".$subClassNode->getText().") not found");
                    }
                }

                //Equivalent Class
                $equivalentClassNode = $node->getProperty("owl:equivalentClass");
                if($equivalentClassNode)
                {
                    try
                    {
                        $equivalentClass = $ontology->getClass($equivalentClassNode->getText());
                        $ontologyClass.setEquivalentClass($equivalentClass);
                    }
                    catch(\InvalidArgumentException $e)
                    {
                        $this->logger->warn("equivalentClass ".$name." (".$equivalentClassNode->getText().") not found");
                    }
                }
            }
            //Property
            else if($templateName == OntologyObjectProperty::TEMPLATE_NAME || $templateName == OntologyDataTypeProperty::TEMPLATE_NAME)
            {
                $ontologyProperty = $ontology->getProperty($name);

                //Domain
                $domainUriNode = $node->getProperty("rdfs:domain");
                if($domainUriNode)
                {
                    try
                    {
                        $domainClass = $ontology->getClass($domainUriNode->getText());
                    }
                    catch(\InvalidArgumentException $e)
                    {
                        $this->logger->warn("Domain of ".$name." (".$domainUriNode->getText().") not found. Assuming owl:Thing");
                        $domainClass = $ontology->getClass("owl:Thing");
                    }

                    $ontologyProperty->setDomain($domainClass);
                }
                else
                {
                    $ontologyProperty->setDomain($ontology->getClass("owl:Thing"));
                }

                //Range
                $rangeUriNode = $node->getProperty("rdfs:range");
                if($rangeUriNode)
                {
                    if($ontologyProperty instanceof OntologyObjectProperty)
                    {
                        try
                        {
                            $rangeClass = $ontology->getClass($rangeUriNode->getText());
                        }
                        catch(\InvalidArgumentException $e)
                        {
                            $this->logger->warn("Range of ".$name." (".$rangeUriNode->getText().") not found. Assuming owl:Thing");
                            $rangeClass = $ontology->getClass("owl:Thing");
                        }

                        $ontologyProperty->setRange($rangeClass);
                    }
                    else
                    {
                        try
                        {
                            $dataType = $ontology->getDataType($rangeUriNode->getText());
                            $ontologyProperty->setRange($dataType);
                        }
                        catch(\InvalidArgumentException $e)
                        {
                            $this->logger->warn("Range of ".$name." (".$rangeUriNode->getText().") not found. Removing property.");
                            $ontology->removeProperty($ontologyProperty);
                        }
                    }
                }
                else
                {
                    if($ontologyProperty instanceof OntologyObjectProperty)
                    {
                        $ontologyProperty->setRange($ontology->getClass("owl:Thing"));
                    }
                    else
                    {
                        $this->logger->warn("Range of ".$name." not defined. Removing property.");
                        $ontology->removeProperty($ontologyProperty);
                    }
                }
            }
        }
    }

    private static function getPageName( $pageNode )
    {
        // Hack: WikiTitle changed the first char to upper case.
        return self::getName(StringUtil::mb_lcfirst($pageNode->getTitle()->encoded()));
    }
    
    /**
     * Hack that cuts off 'dbpedia/' from the start of the string 
     * or replaces the first '/' by a ':', so 'dbpedia/Person' becomes 'Person'
     * and 'foaf/name' becomes 'foaf:name'.
     * @param $name wiki-encoded page title
     * @return $name without 'dbpedia/' prefix or with first '/' replaced by a ':'.
     * @throws InvalidArgumentException if $name does not include a '/'
     */
    public static function getName( $name )
    {
        if(StringUtil::startsWith($name, 'dbpedia/'))
        {
            return substr($name, 8);
        }
        else
        {
            $slash = strpos($name, '/');
            if ($slash === false)
            {
                throw new \InvalidArgumentException('missing namespace in page title ' . $name);
            }
            else
            {
                $name[$slash] = ':';
                return $name;
            }
        }
    }
}
}
