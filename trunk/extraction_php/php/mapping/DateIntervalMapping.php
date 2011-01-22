<?php
namespace dbpedia\mapping
{
use dbpedia\core\RdfQuad;

class DateIntervalMapping extends CustomMapping implements Mapping
{
    private $logger;

    const TEMPLATE_NAME = "DBpediaDateIntervalMapping";
    const DESTINATION_ID = "DateIntervalMapping.destination";

    const TEMPLATE_PROPERTY = "templateProperty";
    const START_DATE_PROPERTY = "startDateOntologyProperty";
    const END_DATE_PROPERTY = "endDateOntologyProperty";

    protected $node;
    protected $ontology;
    private $destination = null;

    private $templatePropertyName;
    private $startDateOntologyProperty;
    private $endDateOntologyProperty;

    private $startDateParser;
    private $endDateParser;

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
       $mapping = new DateIntervalMapping($node, $ontology, $context);
       return $mapping;
    }

    private function buildMappings($context)
    {
        $templatePropertyNode = $this->node->getProperty(self::TEMPLATE_PROPERTY);
        $startDateOntologyPropertyNode = $this->node->getProperty(self::START_DATE_PROPERTY);
        $endDateOntologyPropertyNode = $this->node->getProperty(self::END_DATE_PROPERTY);

        if ($templatePropertyNode)
        {
            $this->templatePropertyName = $templatePropertyNode->getText();
        }
        
        if ($startDateOntologyPropertyNode)
        {
            $startDateOntologyPropertyName = $startDateOntologyPropertyNode->getText();
            $this->startDateOntologyProperty = $this->ontology->getProperty($startDateOntologyPropertyName);
            if(!$this->startDateOntologyProperty)
            {
                throw new \Exception("DateIntervalMapping defines an unknown start date property: ".$startDateOntologyPropertyName);
            }
        }
        
        if ($endDateOntologyPropertyNode)
        {
            $endDateOntologyPropertyName = $endDateOntologyPropertyNode->getText();
            $this->endDateOntologyProperty = $this->ontology->getProperty($endDateOntologyPropertyName);
            if(!$this->endDateOntologyProperty)
            {
                throw new \Exception("DateIntervalMapping defines an unknown end date property: ".$endDateOntologyPropertyName);
            }
        }

        $startDateRange = $this->startDateOntologyProperty->getRange();
        $endDateRange = $this->endDateOntologyProperty->getRange();

        $this->startDateParser = new \dbpedia\dataparser\DateTimeParser($startDateRange);
        $this->endDateParser = new \dbpedia\dataparser\DateTimeParser($endDateRange);

        if (!$this->startDateParser || !$this->endDateParser)
        {
            throw new \Exception("DateIntervalMapping: Parser couldn't be added");
        }
    }

    public function extract($node, $subjectUri, $pageContext)
    {
        $propertyNode = $node->getProperty($this->templatePropertyName);
        if ($propertyNode)
        {
            $oldPropertyNode = $propertyNode;
            $newPropertyNodes = $propertyNode->split("~-~");
            if (sizeof($newPropertyNodes) == 1)
            {
                $newPropertyNodes = $propertyNode->split("~".chr(226).chr(128).chr(147)."~");
                $newPropertyNodes = $propertyNode->split("~".chr(226) . chr(128) . chr(148)."~");
                $newPropertyNodes = $propertyNode->split("~&mdash;~");
                $newPropertyNodes = $propertyNode->split("~&ndash;~");
            }

            if (sizeof($newPropertyNodes) >= 1)
            {
                $node->addProperty($newPropertyNodes[0]);
                $result = $this->startDateParser->parse($newPropertyNodes[0]);
                if ($result && $result > "1880")
                {
                    try
                    {
                        $quad = new RdfQuad($subjectUri, $this->startDateOntologyProperty, $result, $propertyNode->getSourceUri());
                        $this->destination->addQuad($quad);
                    }
                    catch (\InvalidArgumentException $e)
                    {
                        $this->logger->warn($e->getMessage().' (Page: '.$node->getRoot()->getTitle().')');
                    }
                }
            }
            
            if (sizeof($newPropertyNodes) >= 2)
            {
                $startResult = $result;
                $node->addProperty($newPropertyNodes[1]);
                $result = $this->endDateParser->parse($newPropertyNodes[1]);
                if ($result)
                {
                    $endDateOk = true;
                    if ($startResult && ($result < $startResult))
                    {
                        $endDateOk = false;
                    }
                    //TODO: delete for DBpedia
                    else if ($result < "1800")
                    {
                        $endDateOk = false;
                    }
                    if ($endDateOk)
                    {
                        try
                        {
                            $quad = new RdfQuad($subjectUri, $this->endDateOntologyProperty, $result, $propertyNode->getSourceUri());
                            $this->destination->addQuad($quad);
                        }
                        catch (\InvalidArgumentException $e)
                        {
                            $this->logger->warn($e->getMessage().' (Page: '.$node->getRoot()->getTitle().')');
                        }
                    }
                }

                $node->addProperty($oldPropertyNode);
            }
        }

        return true;
    }

    public function __toString()
    {
        $str = "  DateIntervalMapping".PHP_EOL;
        return $str;
    }
}
}
