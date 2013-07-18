<?php
namespace dbpedia\mapping
{
use \dbpedia\core\RdfQuad;

class SimplePropertyMapping extends CustomMapping implements Mapping
{
    private $name = "SimplePropertyMapping";

    private $logger;

    const TEMPLATE_NAME = "DBpediaPropertyMapping";
    
    const DESTINATION_ID = "SimplePropertyMapping.destination";
    
    const TEMPLATE_PROPERTY = "templateProperty";
    const ONTOLOGY_PROPERTY = "ontologyProperty";
    const UNIT = "unit";

    const XSD_INTEGER = "xsd:integer";
    const XSD_DOUBLE = "xsd:double";
    const XSD_FLOAT = "xsd:float";
    const XSD_BOOLEAN = "xsd:boolean";
    const XSD_DATE = "xsd:date";
    const XSD_GYEAR = "xsd:gYear";
    const XSD_GYEARMONTH = "xsd:gYearMonth";
    const XSD_STRING = "xsd:string";

    protected $templateProperty;
    protected $templatePropertyName;
    protected $ontologyProperty;
    protected $ontologyPropertyName;
    protected $unit;

    protected $parser;

    private $ontologyPropertyDimension = null;
    private $ontologyPropertyUnit = null;

    private $templatePropertyDimension = null;
    private $templatePropertyUnit = null;
    
    private $destination = null;

    private function __construct($node, $ontology, $context)
    {
        $this->logger = \dbpedia\core\DBpediaLogger::getLogger(__CLASS__);
        $this->destination = $context->getDestinations()->getDestination(self::DESTINATION_ID);
        $this->buildMappings($node, $ontology);
    }

    public static function load($node, $ontology, $context)
    {
       $mapping = new SimplePropertyMapping($node, $ontology, $context);
       return $mapping;
    }

    private function buildMappings( $node, $ontology )
    {
        $this->templatePropertyName = $this->loadProperty($node, self::TEMPLATE_PROPERTY);
        $this->ontologyPropertyName = $this->loadProperty($node, self::ONTOLOGY_PROPERTY);
        $this->ontologyProperty = $ontology->getProperty($this->ontologyPropertyName);
        if (!$this->ontologyProperty)
        {
            throw new \Exception("Ontology property not found: ".$this->ontologyPropertyName. ",  Template: ".$node->getRoot()->getTitle()." Property: ".$this->templatePropertyName);
        }

        $this->unit = $this->loadProperty($node, self::UNIT, true);
        if($this->unit)
        {
            try
            {
                $ontology->getDataType($this->unit);
            }
            catch(\InvalidArgumentException $e)
            {
                throw new \Exception("Unit $this->unit does not exist. Template: ".$node->getRoot()->getTitle()." Property: ".$this->templatePropertyName);
            }
        }

        $ontologyPropertyRange = $this->ontologyProperty->getRange();
        if ($this->ontologyProperty instanceof \dbpedia\ontology\OntologyDataTypeProperty)
        {
            $unitDataType = false;
            if ($ontologyPropertyRange instanceof \dbpedia\ontology\dataTypes\UnitDataType)
            {
                $unitDataType = true;
                $this->ontologyPropertyDimension = $ontologyPropertyRange->getDimension();
                $this->ontologyPropertyUnit = $ontologyPropertyRange;
            }
            else if ($ontologyPropertyRange instanceof \dbpedia\ontology\dataTypes\DimensionDataType)
            {
                $unitDataType = true;
                $this->ontologyPropertyDimension = $ontologyPropertyRange;
            }
            else if($ontologyPropertyRange instanceof \dbpedia\ontology\dataTypes\EnumerationDataType)
            {
                $this->parser = new \dbpedia\dataparser\EnumerationParser($ontologyPropertyRange);
            }
            else if ($ontologyPropertyRange instanceof \dbpedia\ontology\dataTypes\DataType)
            {

                switch ($ontologyPropertyRange->getName())
                {
                    case SimplePropertyMapping::XSD_INTEGER:
                        $this->parser = new \dbpedia\dataparser\NumberParser($ontologyPropertyRange);
                        break;

                    case SimplePropertyMapping::XSD_DOUBLE:
                        $this->parser = new \dbpedia\dataparser\NumberParser($ontologyPropertyRange);
                        break;

                    case SimplePropertyMapping::XSD_FLOAT:
                        $this->parser = new \dbpedia\dataparser\NumberParser($ontology->getDataType(SimplePropertyMapping::XSD_DOUBLE));
                        break;

                    case SimplePropertyMapping::XSD_STRING:
                        $this->parser = new \dbpedia\dataparser\StringParser();
                        break;

                    case SimplePropertyMapping::XSD_DATE:
                    case SimplePropertyMapping::XSD_GYEAR:
                    case SimplePropertyMapping::XSD_GYEARMONTH:
                        $this->parser = new \dbpedia\dataparser\DateTimeParser($ontologyPropertyRange);
                        break;

                    case SimplePropertyMapping::XSD_BOOLEAN:
                        $this->parser = new \dbpedia\dataparser\BooleanParser();
                        break;

                    default:
                        throw new \Exception("Not implemented range: ".$ontologyPropertyRange->getName()." , Property: ".$this->templatePropertyName);
                        break;
                }
            }
            if ($unitDataType)
            {
                if($this->unit)
                {
                    try
                    {
                        $templatePropertyRange = $ontology->getDataType($this->unit);
                    }
                    catch (\InvalidArgumentException $e)
                    {
                        throw new \Exception('Unit '.$this->unit.' defined in property mapping '.$this->templatePropertyName.' in '.$node->getRoot()->getTitle().' not found.');
                    }

                    if ($templatePropertyRange instanceof \dbpedia\ontology\dataTypes\UnitDataType)
                    {
                        $this->templatePropertyDimension = $templatePropertyRange->getDimension();
                        $this->templatePropertyUnit = $templatePropertyRange;
                    }
                    else if ($templatePropertyRange instanceof \dbpedia\ontology\dataTypes\DimensionDataType)
                    {
                        $this->templatePropertyDimension = $templatePropertyRange;
                    }

                    if (($this->templatePropertyDimension != null) && ($this->ontologyPropertyDimension != null))
                    {
                        if ($this->templatePropertyDimension === $this->ontologyPropertyDimension)
                        {
                            if ($this->templatePropertyUnit)
                            {
                                $this->parser = new \dbpedia\dataparser\UnitValueParser($this->templatePropertyUnit);
                            }
                            else if ($this->templatePropertyDimension)
                            {
                                $this->parser = new \dbpedia\dataparser\UnitValueParser($this->templatePropertyDimension);
                            }
                            else
                            {
                                throw new \Exception("No template unit or dimension found");
                            }
                        }
                        else
                        {
                            throw new \Exception("Dimensions mismatch: (template property dimension) ".$this->templatePropertyDimension." != (ontology property dimension) ".$this->ontologyPropertyDimension);
                        }
                    }
                }
                else
                {
                    throw new \Exception('No unit defined in property mapping '.$this->templatePropertyName);
                }
            }
        }
        else if ($this->ontologyProperty instanceof \dbpedia\ontology\OntologyObjectProperty)
        {
            $this->parser = new \dbpedia\dataparser\ObjectParser($ontologyPropertyRange);
        }
        
        
        if ($this->parser === null)
        {
            throw new \Exception("No parser found for template property '".$this->templatePropertyName."' on template '".$node->getRoot()->getTitle()."'");
        }
        
    }

    private function loadProperty($node, $key, $optional = false)
    {
        $propertyNode = $node->getProperty($key);
        if(!$propertyNode)
        {
            if(!$optional)
            {
                throw new \Exception('No '.$key.' found in property mapping defined in '.$node->getRoot()->getTitle());
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
                throw new \Exception($key.' in property mapping defined in '.$node->getRoot()->getTitle().' is empty.');
            }
            else
            {
                return null;
            }
        }
        return $text;
    }
    
    public function extract($node, $subjectUri, $pageContext)
    {
        $results = null;
        $propertyNode = $node->getProperty($this->templatePropertyName);
        if ($propertyNode && (sizeof($propertyNode->getChildren()) > 0))
        {
            if(!$this->ontologyProperty->isFunctional())
            {
                $newPropertyNodes = $propertyNode->split("/<br\s*\/?>/");
            }
            else
            {
                $newPropertyNodes = array($propertyNode);
            }

            if (sizeof($newPropertyNodes) > 1)
            {
                foreach ($newPropertyNodes as $newPropertyNode)
                {
                    $node->addProperty($newPropertyNode);

                    $tempResult = $this->parser->parse($newPropertyNode);
                    if ($tempResult)
                    {
                        $results[] = $tempResult;
                    }
                }

                $node->addProperty($propertyNode);
            }
            else
            {
                $tempResult = $this->parser->parse($propertyNode);
                if ($tempResult)
                {
                    $results[] = $tempResult;
                }
            }

            if ($results)
            {
                foreach($results as $result)
                {
                    if ($this->parser instanceof \dbpedia\dataparser\UnitValueParser)
                    {
                        if ($this->ontologyPropertyUnit)
                        {
                            $targetUnitValue = $result[1]->toStandardUnit(floatval($result[0]));
                            $targetUnitValue = $this->ontologyPropertyUnit->fromStandardUnit($targetUnitValue);
                            $targetUnit = $this->ontologyPropertyUnit;
                        }
                        else
                        {
                            $targetUnitValue = $result[0];
                            $targetUnit = $result[1];
                        }
                        try
                        {
                            $quad = new RdfQuad($subjectUri, $this->ontologyProperty, $targetUnitValue, $propertyNode->getSourceUri(), $targetUnit);
                            $this->destination->addQuad($quad);
                        }
                        catch (\InvalidArgumentException $e)
                        {
                            $this->logger->warn($e->getMessage().' (Page: '.$node->getRoot()->getTitle().')');
                        }
                    }
                    else
                    {
                        try
                        {
                            $quad = new RdfQuad($subjectUri, $this->ontologyProperty, $result, $propertyNode->getSourceUri());
                            $this->destination->addQuad($quad);
                        }
                        catch (\InvalidArgumentException $e)
                        {
                            $this->logger->warn($e->getMessage().' (Page: '.$node->getRoot()->getTitle().')');
                        }
                    }
                }

                return true;
            }
            else
            {
                if (isset($_SERVER['HOMEPATH']) && $_SERVER['HOMEPATH'] == "\User\Anja")
                {
                    echo "\n", $node->getRoot()->getTitle();
                    echo " - $this->templatePropertyName -> $this->ontologyPropertyName, $this->parser".$propertyNode;
                    echo"";
                }
            }
        }

        return false;
    }

    public function getTemplatePropertyName()
    {
        return $this->templatePropertyName;
    }

    public function __toString()
    {
        $str = '';
        $str .= "    Mapping".PHP_EOL;
        $str .= "    -------".PHP_EOL;
        $str .= "    Class                  : '".$this->name."'".PHP_EOL;
        $str .= "    Template Property Name : '".$this->templatePropertyName."'".PHP_EOL;
        if ($this->unit && $this->templatePropertyDimension)
        {
            $str .= "    Template Property Range: '".$this->templatePropertyDimension->getName()."'".PHP_EOL;
        }
        $str .= "    Ontology Property Name : '".$this->ontologyPropertyName."'".PHP_EOL;
        $str .= $this->parser.PHP_EOL;
        return $str;
    }
}
}
