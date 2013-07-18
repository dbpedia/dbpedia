<?php
namespace dbpedia\mapping
{
use \dbpedia\core\RdfQuad;

class CalculateMapping
{
    const TEMPLATE_NAME = "DBpediaCalculateMapping";

    const DESTINATION_ID = "DBpediaCalculateMapping.destination";

    const TEMPLATE_PROPERTY1 = "templateProperty1";
    const TEMPLATE_PROPERTY2 = "templateProperty2";
    const UNIT1 = "unit1";
    const UNIT2 = "unit2";
    const OPERATION = "operation";
    const ONTOLOGY_PROPERTY = "ontologyProperty";

    private $destination = null;

    protected $templateProperty1;
    protected $templateProperty2;
    protected $unit1;
    protected $unit2;
    protected $operation;
    protected $ontologyProperty;

    protected $parser;

    public static function load($node, $ontology, $context)
    {
        $mapping = new CalculateMapping();

        $mapping->destination = $context->getDestinations()->getDestination(self::DESTINATION_ID);

        $mapping->templateProperty1 = self::loadProperty($node, self::TEMPLATE_PROPERTY1);
        $mapping->templateProperty2 = self::loadProperty($node, self::TEMPLATE_PROPERTY2);
        $mapping->unit1 = $ontology->getDataType(self::loadProperty($node, self::UNIT1));
        $mapping->unit2 = $ontology->getDataType(self::loadProperty($node, self::UNIT2));

        $mapping->operation = self::loadProperty($node, self::OPERATION);
        if($mapping->operation !== 'add')
        {
            throw new \Exception("Invalid operation '" . $this->operation . "' in " . $node->getRoot()->getTitle());
        }

        $mapping->ontologyProperty = $ontology->getProperty(self::loadProperty($node, self::ONTOLOGY_PROPERTY));
        if ($mapping->ontologyProperty instanceof \dbpedia\ontology\OntologyDataTypeProperty)
        {
            if($mapping->ontologyProperty->getRange() instanceof \dbpedia\ontology\dataTypes\UnitDataType ||
               $mapping->ontologyProperty->getRange() instanceof \dbpedia\ontology\dataTypes\DimensionDataType)
            {
                $mapping->parser = new \dbpedia\dataparser\UnitValueParser($mapping->ontologyProperty->getRange());
            }
            else
            {
                switch ($mapping->ontologyProperty->getRange()->getName())
                {
                    case SimplePropertyMapping::XSD_INTEGER:
                        $mapping->parser = new \dbpedia\dataparser\NumberParser($mapping->ontologyProperty->getRange());
                        break;

                    case SimplePropertyMapping::XSD_DOUBLE:
                        $mapping->parser = new \dbpedia\dataparser\NumberParser($mapping->ontologyProperty->getRange());
                        break;

                    case SimplePropertyMapping::XSD_FLOAT:
                        $mapping->parser = new \dbpedia\dataparser\NumberParser($this->ontology->getDataType(SimplePropertyMapping::XSD_DOUBLE));
                        break;

                    default:
                        throw new \Exception("Cannot use a calculate mapping on type: ".$mapping->ontologyProperty->getRange());
                }
            }
        }
        else
        {
            throw new \Exception("Calculate mappings can only be used on datatype properties");
        }
        return $mapping;
    }

    private static function loadProperty($node, $key)
    {
        $propertyNode = $node->getProperty($key);
        if(!$propertyNode)
        {
            throw new \Exception('No '.$key.' property found in mapping defined in '.$node->getRoot()->getTitle());
        }
        $text = $propertyNode->getText();
        if(empty($text))
        {
            throw new \Exception($key.' in mapping defined in '.$node->getRoot()->getTitle().' is empty.');
        }
        return $text;
    }

    private function __construct()
    {
        $this->logger = \dbpedia\core\DBpediaLogger::getLogger(__CLASS__);
    }

    public function extract($node, $subjectUri, $pageContext)
    {
        //Retrieve template properties
        $property1 = $node->getProperty($this->templateProperty1);
        $property2 = $node->getProperty($this->templateProperty2);
        if(!$property1 || !$property2)
        {
            return false;
        }

        //Parse template properties
        $parseResult1 = $this->parser->parse($property1);
        $parseResult2 = $this->parser->parse($property2);
        if(!$parseResult1 || !$parseResult2)
        {
            return false;
        }

        //Convert property values to their standard unit
        $value1 = $parseResult1[1]->toStandardUnit(floatval($parseResult1[0]));
        $value2 = $parseResult2[1]->toStandardUnit(floatval($parseResult2[0]));

        if($this->ontologyProperty->getRange() instanceof \dbpedia\ontology\dataTypes\UnitDataType)
        {
            //Convert values to the destination unit
            $value1 = $this->ontologyProperty->getRange()->fromStandardUnit($value1);
            $value2 = $this->ontologyProperty->getRange()->fromStandardUnit($value2);

            $targetUnit = null;
        }
        else
        {
            //Only dimension provided -> Use standard unit as target unit
            $dimension = $this->ontologyProperty->getRange()->getDimension();
            $unitLabels = $dimension->getUnitLabels();
            $targetUnit = $dimension->getUnit($unitLabels[0]);
        }

        //Add values
        $result = $value1 + $value2;

        //Write quad
        try
        {
            $quad = new RdfQuad($subjectUri, $this->ontologyProperty, $result, $node->getSourceUri(), $targetUnit);
            $this->destination->addQuad($quad);
            return true;
        }
        catch (\InvalidArgumentException $e)
        {
            $this->logger->warn($e->getMessage().' (Page: '.$node->getRoot()->getTitle().')');
        }

        return false;
    }

    public function __toString()
    {
        $str = '';
        $str .= "  Mapping".PHP_EOL;
        $str .= "  -------".PHP_EOL;
        $str .= "  Class: '".CalculateMapping::TEMPLATE_NAME."'".PHP_EOL;
        $str .= "  Template Properties: ".$this->templateProperty1. " ".$this->templateProperty2.PHP_EOL;
        $str .= "  Ontology Property  : ".$this->ontologyProperty.PHP_EOL;
        return $str;
    }
}
}
