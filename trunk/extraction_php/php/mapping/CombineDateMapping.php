<?php
namespace dbpedia\mapping
{
use \dbpedia\core\RdfQuad;

class DateProperty
{
    public $templateProperty;
    public $parser;
}

class CombineDateMapping
{
    const TEMPLATE_NAME = "DBpediaCombineDateMapping";

    const DESTINATION_ID = "DBpediaCombineDateMapping.destination";

    const TEMPLATE_PROPERTY = "templateProperty";
    const UNIT_PROPERTY = "unit";
    const ONTOLOGY_PROPERTY = "ontologyProperty";

    private $destination = null;

    protected $dayParser;
    protected $monthParser;
    protected $yearParser;
    protected $monthDayParser;
    protected $yearMonthParser;

    protected $dayProperty;
    protected $monthProperty;
    protected $yearProperty;
    protected $monthDayProperty;
    protected $yearMonthProperty;

    protected $ontologyProperty;

    protected $parser;

    public static function load($node, $ontology, $context)
    {
        $mapping = new CombineDateMapping();
        $mapping->destination = $context->getDestinations()->getDestination(self::DESTINATION_ID);

        $mapping->dayParser = new \dbpedia\dataparser\DateTimeParser($ontology->getDataType('xsd:gDay'));
        $mapping->monthParser = new \dbpedia\dataparser\DateTimeParser($ontology->getDataType('xsd:gMonth'));
        $mapping->yearParser = new \dbpedia\dataparser\DateTimeParser($ontology->getDataType('xsd:gYear'));
        $mapping->monthDayParser = new \dbpedia\dataparser\DateTimeParser($ontology->getDataType('xsd:gMonthDay'));
        $mapping->yearMonthParser = new \dbpedia\dataparser\DateTimeParser($ontology->getDataType('xsd:gYearMonth'));

        for($i = 1; $i <= 3; $i++)
        {
            $templatePropertyNode = $node->getProperty(self::TEMPLATE_PROPERTY . $i);
            $unitPropertyNode = $node->getProperty(self::UNIT_PROPERTY . $i);
            if(!$templatePropertyNode || !$unitPropertyNode)
            {
                break;
            }

            $templatePropertyName = $templatePropertyNode->getText();
            if(!$templatePropertyName)
            {
                //TODO
            }

            $unitName = $unitPropertyNode->getText();
            if(!$unitName)
            {
                //TODO
            }

            switch($unitName)
            {
                case 'xsd:gDay':
                    $mapping->dayProperty = $templatePropertyName;
                    break;
                case 'xsd:gMonth':
                    $mapping->monthProperty = $templatePropertyName;
                    break;
                case 'xsd:gYear':
                    $mapping->yearProperty = $templatePropertyName;
                    break;
                case 'xsd:gMonthDay':
                    $mapping->monthDayProperty = $templatePropertyName;
                    break;
                case 'xsd:gYearMonth':
                    $mapping->yearMonthParser = $templatePropertyName;
                    break;
                default:
                    throw new \Exception('CombineDateMapping in ' . $node->getRoot()->getTitle() . " uses an unsupported unit '" . $unitName . "'");

            }
        }

        $mapping->ontologyPropertyNode = $node->getProperty(self::ONTOLOGY_PROPERTY);
        if(!$mapping->ontologyPropertyNode)
        {
            throw new \Exception('No ' . self::ONTOLOGY_PROPERTY . ' property found in mapping defined in ' . $node->getRoot()->getTitle());
        }
        $ontologyPropertyName = $mapping->ontologyPropertyNode->getText();
        if(empty($ontologyPropertyName))
        {
            throw new \Exception(self::ONTOLOGY_PROPERTY .' in mapping defined in ' . $node->getRoot()->getTitle() . " is empty.");
        }

        $mapping->ontologyProperty = $ontology->getProperty($ontologyPropertyName);
        if($mapping->ontologyProperty->getRange()->getName() != 'xsd:date')
        {
            throw new \Exception(self::ONTOLOGY_PROPERTY .' in mapping defined in ' . $node->getRoot()->getTitle() . " has unsupported range:'" . $ontologyPropertyName . "'.");
        }

        return $mapping;
    }

    private function __construct()
    {
        $this->logger = \dbpedia\core\DBpediaLogger::getLogger(__CLASS__);
    }

    public function extract($node, $subjectUri, $pageContext)
    {
        $day = $month = $year = null;

        if($this->dayProperty)
        {
            $propertyValue = $node->getProperty($this->dayProperty);
            if($propertyValue)
            {
                $result = $this->dayParser->parse($propertyValue);
                $this->extractParts($result, $day, $month, $year);
            }
        }

        if($this->monthProperty)
        {
            $propertyValue = $node->getProperty($this->monthProperty);
            if($propertyValue)
            {
                $result = $this->monthParser->parse($propertyValue);
                $this->extractParts($result, $day, $month, $year);
            }
        }

        if($this->yearProperty)
        {
            $propertyValue = $node->getProperty($this->yearProperty);
            if($propertyValue)
            {
                $result = $this->yearParser->parse($propertyValue);
                $this->extractParts($result, $day, $month, $year);
            }
        }

        if($this->monthDayProperty)
        {
            $propertyValue = $node->getProperty($this->monthDayProperty);
            if($propertyValue)
            {
                $result = $this->monthDayParser->parse($propertyValue);
                var_dump($result);
                $this->extractParts($result, $day, $month, $year);
            }
        }

        if($this->yearMonthProperty)
        {
            $propertyValue = $node->getProperty($this->yearMonthProperty);
            if($propertyValue)
            {
                $result = $this->yearMonthParser->parse($propertyValue);
                $this->extractParts($result, $day, $month, $year);
            }
        }

        if($day && $month && $year)
        {
            $date = '-' . $year . '-' . $month . '-' . $day;

            //Write quad
            try
            {
                $this->destination->addQuad(new RdfQuad($subjectUri, $this->ontologyProperty, $date, $node->getSourceUri()));
                return true;
            }
            catch (\InvalidArgumentException $e)
            {
                $this->logger->warn($e->getMessage().' (Page: '.$node->getRoot()->getTitle().')');
            }
        }
        else
        {
            //TODO log error
        }

        return false;
    }

    public function extractParts($input, &$day, &$month, &$year)
    {
        //Year may be prefixed with a minus
        $yearPrefix = '';
        if($input[0] == '-')
        {
            $yearPrefix = '-';
            $input = substr($input, 1);
        }

        $parts = explode('-', $input);

        if(!empty($parts[0]))
        {
            $year = $yearPrefix . $parts[0];
        }

        if(!empty($parts[1]))
        {
            $month = $parts[1];
        }

        if(!empty($parts[2]))
        {
            $day = $parts[2];
        }
    }

        public function __toString()
        {
        $str = '';
        $str .= "  Mapping".PHP_EOL;
        $str .= "  -------".PHP_EOL;
        $str .= "  Class: '".get_class($this)."'".PHP_EOL;
        $str .= "  Ontology Property: ".$this->ontologyProperty.PHP_EOL;
//        $str .= "  Operator         : ".$this->operator.PHP_EOL;
//        $str .= "  Value            : ".$this->value.PHP_EOL;
        return $str;
    }
}
}
