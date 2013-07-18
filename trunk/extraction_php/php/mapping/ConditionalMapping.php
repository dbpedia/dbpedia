<?php
namespace dbpedia\mapping
{
use \dbpedia\core\RdfQuad;

class Condition
{
    const TEMPLATE_PROPERTY = 'templateProperty';
    const OPERATOR = 'operator';
    const VALUE = 'value';

    protected $templateProperty;
    protected $operator;
    protected $value;

    public static function load($node)
    {
        $condition = new Condition();

        $otherwiseNode = $node->getProperty(1);
        if($otherwiseNode)
        {
            $condition->operator = 'otherwise';
        }
        else
        {
            $condition->operator = self::loadProperty($node, self::OPERATOR);
            switch($condition->operator)
            {
                case 'equals':
                case 'contains':
                    $condition->value = self::loadProperty($node, self::VALUE);
                case 'isSet':
                    $condition->templateProperty = self::loadProperty($node, self::TEMPLATE_PROPERTY);
                    break;
                default:
                    throw \Exception("Invalid operator '" . $condition->operator . "' defined in ". $node->getRoot()->getTitle());
            }
        }

        return $condition;
    }

    private static function loadProperty($node, $key)
    {
        $propertyNode = $node->getProperty($key);
        if(!$propertyNode)
        {
            throw new \Exception("No property '" . $key ."' found in mapping defined in " . $node->getRoot()->getTitle());
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

    public function getOperator()
    {
        return $this->operator;
    }

    public function evaluate($node)
    {
        $templatePropertyNode = $node->getProperty($this->templateProperty);
        if(!$templatePropertyNode)
        {
            return false;
        }

        $templatePropertyText = $templatePropertyNode->getText();

        switch($this->operator)
        {
            case 'isSet':
                return true;
                break;
            case 'equals':
                return $templatePropertyText && strcasecmp($templatePropertyText, $this->value);
                break;
            case 'contains':
                return $templatePropertyText && stripos($templatePropertyText, $this->value) !== false;
                break;
        }

        throw \Exception('Error evaluating condition');
    }
}

class ConditionalMapping
{
    const TEMPLATE_NAME = "DBpediaConditionalMapping";

    const DESTINATION_ID = "DBpediaConditionalMapping.destination";

    const CASES = 'cases';
    const DEFAULT_MAPPINGS = 'defaultMappings';
    
    private $destination = null;

    protected $cases;
    protected $otherwise;

    public static function load($node, $ontology, $context)
    {
        $mapping = new ConditionalMapping();

        $mapping->destination = $context->getDestinations()->getDestination(self::DESTINATION_ID);

        //Load default mappings
        $defaultMappings = array();
        $defaultMappingsNode = $node->getProperty(self::DEFAULT_MAPPINGS);
        foreach($defaultMappingsNode->getChildren('TemplateNode') as $defaultMappingNode)
        {
            $defaultMappings[] = PropertyMapping::load($defaultMappingNode,  $ontology, $context);
        }

        //Load cases
        $casesNode = $node->getProperty(self::CASES);
        if(!$casesNode)
        {
            throw new \Exception('No '.self::CASES.' property found in conditional mapping defined in '.$node->getRoot()->getTitle());
        }

        $mapping->cases = array();

        $caseNodes = $casesNode->getChildren('TemplateNode');
        foreach($caseNodes as $caseNode)
        {
            $condition = Condition::load($caseNode);

            $mappingNodes = $caseNode->getProperty('mapping')->getChildren('TemplateNode');

            if(!isset($mappingNodes[0]))
                continue;
            
            $templateMapping = TemplateMapping::load($mappingNodes[0], $ontology, $context);
            
            //If the template mapping does not define any property mapping -> add default mappings
            if(count($templateMapping->getPropertyMappings()) == 0)
            {
                foreach($defaultMappings as $defaultMapping)
                {
                    $templateMapping->addPropertyMapping($defaultMapping);
                }
            }

            if($condition->getOperator() === 'otherwise')
            {
                if($mapping->otherwise)
                {
                    throw \Exception('Cannot define multiple default mappings in ' . $node->getRoot()->getTitle());
                }

                $mapping->otherwise = $templateMapping;
            }
            else
            {
                $mapping->cases[] = array($condition, $templateMapping);
            }
        }

        return $mapping;
    }

    private function __construct()
    {
        $this->logger = \dbpedia\core\DBpediaLogger::getLogger(__CLASS__);
    }

    public function extract($node, $subjectUri, $pageContext)
    {
        foreach($this->cases as $case)
        {
            if($case[0]->evaluate($node))
            {
                return $case[1]->extract($node, $subjectUri, $pageContext);
            }
        }

        if(isset($this->otherwise))
            return $this->otherwise->extract($node, $subjectUri, $pageContext);
        else
           return false;
    }

    public function __toString()
    {
        $str = '';
        $str .= "  Mapping".PHP_EOL;
        $str .= "  -------".PHP_EOL;
        $str .= "  Class: '".ConditionalMapping::TEMPLATE_NAME."'".PHP_EOL;
//        $str .= "  Template Property: ".$this->templateProperty.PHP_EOL;
//        $str .= "  Operator         : ".$this->operator.PHP_EOL;
//        $str .= "  Value            : ".$this->value.PHP_EOL;
        return $str;
    }

}
}
