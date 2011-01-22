<?php
namespace dbpedia\mapping
{
use dbpedia\core\RdfQuad;
use dbpedia\wikiparser\TemplateNode;
use dbpedia\wikiparser\PropertyNode;
use dbpedia\wikiparser\TextNode;
use dbpedia\wikiparser\LinkNode;

class ColumnMatching
{
    private $propertyName;
    private $columnIndex;
    private $startIndex;
    private $endIndex;

    public function getPropertyName()
    {
        return $this->propertyName;
    }

    public function getColumnIndex()
    {
        return $this->columnIndex;
    }

    public function __construct($propertyName, $columnIndex, $startIndex, $endIndex)
    {
        $this->propertyName = $propertyName;
        $this->columnIndex = $columnIndex;
        $this->startIndex = $startIndex;
        $this->endIndex = $endIndex;
    }

    public static function compare($a, $b)
    {
        //Prefer matchings with an low start index
        if ($a->startIndex != $b->startIndex)
        {
            return ($a->startIndex < $b->startIndex) ? -1 : 1;
        }

        //Prefer matchings with property names with many conjuctive parts (e.g. 'power&kW' over 'power')
        $countA = substr_count($a->propertyName, '&');
        $countB = substr_count($b->propertyName, '&');
        if($countA != $countB)
        {
            return ($countA > $countB) ? -1 : 1;
        }

        //Prefer short matchings (e.g. 'power kw' (from def: 'power&kW') over  'power kW (PS)' (from def: 'power&PS'))
        if ($a->endIndex != $b->endIndex)
        {
            return ($a->endIndex < $b->endIndex) ? -1 : 1;
        }

        //Give up and consider this two matchings as equal
        return 0;
    }
}

class TableMapping implements Mapping
{
    const TEMPLATE_NAME = "DBpediaTableMapping";
    
    const DESTINATION_ID = "TableMapping.destination";
    
    const MAP_TO_CLASS = "mapToClass";
    const MAPPINGS = "mappings";
    const CORRESPONDING_CLASS = "correspondingClass";
    const CORRESPONDING_PROPERTY = "correspondingProperty";
    const KEYWORDS = 'keywords';
    const HEADER = 'header';

    private $logger;

    protected $templateNode;
    protected $ontology;

    protected $mapToClass;

    protected $correspondingClass;

    protected $correspondingProperty;

    protected $keywords = array();

    protected $headerDefinition = array();

    private $destination = null;

    protected $propertiesMap = array();

    private function __construct($templateNode, $ontology, $context)
    {
        $this->logger = \dbpedia\core\DBpediaLogger::getLogger(__CLASS__);

        $this->templateNode = $templateNode;
        $this->ontology = $ontology;
        $this->destination = $context->getDestinations()->getDestination(self::DESTINATION_ID);
        $this->buildMappings($context);
    }

    public static function load($templateNode, $ontology, $context)
    {
        $mapping = new TableMapping($templateNode, $ontology, $context);
        return $mapping;
    }

    private function buildMappings($context)
    {
        $this->loadMapToClass();
        $this->loadCorrespondingClass();
        $this->loadCorrespondingProperty();
        $this->loadKeywords();
        $this->loadHeaderDefinition();
        $this->loadMappings($context);
    }

    private function loadMapToClass()
    {
        $mapToClassClassProperty = $this->templateNode->getProperty(self::MAP_TO_CLASS);
        if($mapToClassClassProperty)
        {
            $mapToClassName = $mapToClassClassProperty->getText();
            if(empty($mapToClassName))
            {
                throw new \Exception('Table mapping refers to an invalid class');
            }

            $this->mapToClass = $this->ontology->getClass($mapToClassName);
            if(!$this->mapToClass)
            {
                throw new \Exception("Table mapping refers to an unknown class");
            }
        }
        else
        {
            throw new \Exception('Table mapping does not define the '.self::MAP_TO_CLASS.' property');
        }
    }

    private function loadCorrespondingClass()
    {
        $correspondingClassProperty = $this->templateNode->getProperty(self::CORRESPONDING_CLASS);
        if($correspondingClassProperty)
        {
            $correspondingClassName = $correspondingClassProperty->getText();
            if(empty($correspondingClassName))
            {
                throw new \Exception('Table mapping defines a invalid corresponding class');
            }

            $this->correspondingClass = $this->ontology->getClass($correspondingClassName);
            if(!$this->correspondingClass)
            {
                throw new \Exception("Table mapping defines an unknown corresponding class");
            }
        }
        else
        {
            throw new \Exception('Table mapping does no define a corresponding class');
        }
    }

    private function loadCorrespondingProperty()
    {
        $correspondingPropertyProperty = $this->templateNode->getProperty(self::CORRESPONDING_PROPERTY);
        if($correspondingPropertyProperty)
        {
            $correspondingPropertyName = $correspondingPropertyProperty->getText();
            if(empty($correspondingPropertyName))
            {
                throw new \Exception('Table mapping defines a invalid corresponding property');
            }

            $this->correspondingProperty = $this->ontology->getProperty($correspondingPropertyName);
            if(!$this->correspondingProperty)
            {
                throw new \Exception("Table mapping defines an unknown corresponding property");
            }
        }
        else
        {
            throw new \Exception('Table mapping does no define a corresponding property');
        }
    }

    private function loadKeywords()
    {
        $keywordsProperty = $this->templateNode->getProperty(self::KEYWORDS);
        if($keywordsProperty)
        {
            $keywordsStr = $keywordsProperty->getText();
            if(empty($keywordsStr))
            {
                throw new \Exception('Table mapping has an invalid keyword definition');
            }

            $keywordParts = explode(';', $keywordsStr);
            for($i = 0; $i < count($keywordParts); $i++)
            {
                $this->keywords[$i] = array();

                $alternativeParts = explode(',', $keywordParts[$i]);
                for($j = 0; $j < count($alternativeParts); $j++)
                {
                    $keyword = trim($alternativeParts[$j]);
                    if(strlen($keyword) > 0)
                    {
                        $this->keywords[$i][] = $keyword;
                    }
                }
            }
        }
        else
        {
            throw new \Exception('Table mapping does no define any keywords');
        }
    }

    private function loadHeaderDefinition()
    {
        $headerProperty = $this->templateNode->getProperty(self::HEADER);
        if($headerProperty)
        {
            $headerStr = $headerProperty->getText();
            if(empty($headerStr))
            {
                throw new \Exception('Table mapping has an invalid header definition');
            }

            $columnDefinitions = explode(';', $headerStr);
            for($i = 0; $i < count($columnDefinitions); $i++)
            {
                $this->headerDefinition[$i] = array();

                $columnAlternatives = explode(',', $columnDefinitions[$i]);
                for($j = 0; $j < count($columnAlternatives); $j++)
                {
                    $this->headerDefinition[$i][$j] = array();

                    $keywords = explode('&', $columnAlternatives[$j]);
                    for($k = 0; $k < count($keywords); $k++)
                    {
                        $this->headerDefinition[$i][$j][$k] = trim($keywords[$k]);
                    }
                }
            }
        }
    }

    private function loadMappings($context)
    {
        $mappingsProperty = $this->templateNode->getProperty(self::MAPPINGS);
        if($mappingsProperty)
        {
            $mappings = $mappingsProperty->getChildren('TemplateNode');
            foreach ($mappings as $mapping)
            {
                try
                {
                    $propertyMapping = PropertyMapping::load($mapping, $this->ontology, $context);
                    $this->propertiesMap[] = $propertyMapping;
                }
                catch(Exception $e)
                {
                    $this->logger->warn("Couldn't load property mapping" . PHP_EOL . $e->getTraceAsString());
                }
            }
        }
        else
        {
            throw new Exception('Table mapping does no define any mappings');
        }
    }

    public function extract($table, $subjectUri, $pageContext)
    {
        $header = $this->getHeader($table);
        if(!$this->matchHeader($header))
        {
            return;
        }

        $this->preprocessTable($table);

        $rows = $table->getChildren();
        $table->removeChildren();
        foreach($rows as $row)
        {
            //Ignore the header
            if(!isset($headerFlag))
            {
                $headerFlag = true;
            }
            else
            {
                //Create template node from table row
                $templateNode = $this->createTemplateNode($header, $row);
                if($templateNode)
                {
                    $table->addChild($templateNode);

                    //Create a new ontology instance
                    $correspondingInstance = $this->findCorrespondingInstance($templateNode);

                    //Generate instance URI
                    $cells = $row->getChildren();
                    $instanceUri = $pageContext->generateUri($correspondingInstance != null ? $correspondingInstance : $subjectUri, $cells[0]);

                    //Add new ontology instance
                    try
                    {
                        for($class = $this->mapToClass; $class != null; $class = $class->getSubClassOf())
                        {
                            $quad = new RdfQuad($instanceUri, $this->ontology->getProperty("rdf:type"), $class->getUri(), $templateNode->getSourceUri());
                            $this->destination->addQuad($quad);
                        }
                    }
                    catch (\InvalidArgumentException $e)
                    {
                        $this->logger->warn($e->getMessage() . PHP_EOL . $e->getTraceAsString());
                    }

                    //Link new instance to the corresponding Instance
                    if($correspondingInstance)
                    {
                        try
                        {
                            $quad = new RdfQuad($correspondingInstance, $this->correspondingProperty, $instanceUri, $templateNode->getSourceUri());
                            $this->destination->addQuad($quad);
                        }
                        catch (\InvalidArgumentException $e)
                        {
                            $this->logger->warn($e->getMessage() . PHP_EOL . $e->getTraceAsString());
                        }
                    }

                    //Extract properties
                    foreach($this->propertiesMap as $propertyMapping)
                    {
                        $propertyMapping->extract($templateNode, $instanceUri, $pageContext);
                    }
                }
            }
        }

        return true;
    }

    /**
     * Retrieves the header of the table.
     *
     * @param TableNode $table The table node.
     * @return Array The names of the header cells.
     */
    private function getHeader($table)
    {
        $rows = $table->getChildren();
        if(empty($rows))
        {
            return array();
        }

        if(count($rows[0]->getChildren()) > 1)
        {
            $headerRow = $rows[0];
        }
        else if(count($rows) > 1 && count($rows[1]->getChildren()) > 1)
        {
           $headerRow = $rows[1];
        }
        else
        {
            return array();
        }

        $header = array();

        foreach($headerRow->getChildren() as $headerCell)
        {
            $headerStr = $this->nodeToString($headerCell);
            if(empty($headerStr))
            {
                continue;
            }

            $header[] = $headerStr;
        }

        return $header;
    }

    private static function nodeToString($node)
    {
        $str = '';
        foreach($node->getChildren() as $child)
        {
            if ($child instanceof TextNode)
            {
                $str .= $child->getText();
            }
            else if ($child instanceof LinkNode)
            {
                $str .= self::nodeToString($child);
            }
        }
        return strip_tags($str);
    }

    /**
     * Checks if the given table corresponds to the header definition of this mapping.
     *
     * @param Array $header The table header.
     * @return Boolean
     */
    private function matchHeader($header)
    {
        foreach($this->keywords as $keywordAlternatives)
        {
            $found = false;

            foreach($keywordAlternatives as $keyword)
            {
                foreach($header as $headerCell)
                {
                    if(stripos($headerCell, $keyword) !== false)
                    {
                        $found = true;
                        break;
                    }
                }
            }

            if(!$found)
            {
                return false;
            }
        }

        return true;
    }

    private function preprocessTable($table)
    {
        $rows = $table->getChildren();
        $rowCount = count($rows);
        for($rowIndex = 1; $rowIndex < $rowCount; $rowIndex++)
        {
            $previousCells = $rows[$rowIndex - 1]->getChildren();
            $currentCells = $rows[$rowIndex]->getChildren();

            $newCells = array();

            reset($previousCells);
            reset($currentCells);
            while(true)
            {
                if(current($previousCells) !== false && current($previousCells)->getAnnotation('rowspan') > 1)
                {
                    current($previousCells)->setAnnotation('rowspan', current($previousCells)->getAnnotation('rowspan') - 1);
                    $newCells[] = current($previousCells);

                    next($previousCells);
                }
                else if(current($currentCells) !== false)
                {
                    $newCells[] = current($currentCells);

                    next($previousCells);
                    next($currentCells);
                }
                else
                {
                    $rows[$rowIndex]->removeChildren();
                    foreach($newCells as $newCell)
                    {
                        $rows[$rowIndex]->addChild($newCell);
                    }
                    break;
                }
            }
        }
    }

    private function createTemplateNode($tableHeader, $rowNode)
    {
        //Only accept rows which have the same number of cells than the header)
        $cellNodes = $rowNode->getChildren();
        $cellCount = count($cellNodes);
        if($cellCount != count($tableHeader))
        {
            return null;
        }

        //Create a new template node
        $templateNode = new TemplateNode($rowNode->getLine());

        //Iterate throw all column definitions of the header definition
        foreach($this->headerDefinition as $columnDefinition)
        {
            $columnMatchings = array();

            //Iterate throw all columns in the header and collect matchings
            foreach($tableHeader as $columnIndex => $column)
            {
                //Iterate through all alternatives of this columnDefinition
                foreach($columnDefinition as $columnAlternative)
                {
                    //Match this alternativ column definition with the column header
                    $startIndex = null;
                    $endIndex = null;
                    $i = 0;
                    foreach($columnAlternative as $keyword)
                    {
                        $i = stripos($column, $keyword, $i);

                        if($i === false)
                        {
                            break;
                        }

                        if($startIndex === null)
                        {
                            $startIndex = $i;
                        }

                        $endIndex = $i + strlen($keyword);
                    }

                    if($i !== false)
                    {
                        //Found new column matching
                        $propertyName = implode('&', $columnAlternative);
                        $columnMatchings[] = new ColumnMatching($propertyName, $columnIndex, $startIndex, $endIndex);
                    }
                }
            }

            if(!empty($columnMatchings))
            {
                //Sort all column matchings and select first one
                usort($columnMatchings, '\dbpedia\mapping\ColumnMatching::compare');
                $matching = $columnMatchings[0];

                //Create new property node from the best matching and the current row
                $propertyNode = new PropertyNode($rowNode->getLine());

                $propertyNode->setKey($matching->getPropertyName());
                foreach($cellNodes[$matching->getColumnIndex()]->getChildren() as $cellNodeChild)
                {
                    $propertyNode->addChild($cellNodeChild);
                }

                $templateNode->addProperty($propertyNode);
            }
        }

        return $templateNode;
    }

    private function findCorrespondingInstance($templateNode)
    {
        if(!$this->correspondingProperty)
        {
            return null;
        }

        //Find template node which comes just above this table
        $lastPageTemplate = null;
        foreach($templateNode->getRoot()->getChildren('TemplateNode') as $pageTemplate)
        {
            if($pageTemplate->getLine() > $templateNode->getLine())
            {
                break;
            }

            if(!$pageTemplate->getAnnotation(TemplateMapping::CLASS_ANNOTATION))
            {
                continue;
            }

            $lastPageTemplate = $pageTemplate;
        }

        //Check if found template has been mapped to corresponding Class
        $correspondingInstance = null;
        if($lastPageTemplate)
        {
            $templateClasses = $lastPageTemplate->getAnnotation(TemplateMapping::CLASS_ANNOTATION);
            if($templateClasses)
            {
                foreach($templateClasses as $templateClass)
                {
                    if($templateClass->getName() == $this->correspondingClass->getName())
                    {
                        $correspondingInstance = $lastPageTemplate->getAnnotation(TemplateMapping::INSTANCE_URI_ANNOTATION);
                        break;
                    }
                }
            }
        }

        return $correspondingInstance;
    }

    public function __toString()
    {
        $str = '';
        $str .= "  Mapping".PHP_EOL;
        $str .= "  -------".PHP_EOL;

        $str .= "  Keywords: ";
        foreach($this->keywords as $keywordAlternatives)
        {
            if(!isset($flagK)) $flagK = true; else $str .= ';';
            $str .= implode(',', $keywordAlternatives);
        }
        $str .= PHP_EOL;

        $str .= "  Header defintion: ";
        foreach($this->headerDefinition as $columnDefinition)
        {
            if(!isset($flagC)) $flagC = true; else $str .= ' ; ';

            $flagA = false;
            foreach($columnDefinition as $columnAlternative)
            {
                if(!$flagA) $flagA = true; else $str .= ' , ';
                $str .= implode('&', $columnAlternative);
            }
        }
        $str .= PHP_EOL;

        foreach($this->propertiesMap as $mapping)
        {
            $str .= $mapping.PHP_EOL;
        }
        return $str;
    }
}
}