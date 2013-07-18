<?php
namespace dbpedia\ontology\dataTypes
{
    
use dbpedia\util\PhpUtil;
use dbpedia\ontology\OntologyNamespaces;

/**
 * TODO
 */
class DimensionDataType extends DataType
{
    /**
     * @var map from unit labels used in template property values to unit objects
     */
    private /* final array */ $units = array();

    /**
     * @param $name string, dimension name used in ontology and mapping configuration
     */
    public function __construct( $name )
    {
        parent::__construct($name, OntologyNamespaces::getUri($name, OntologyNamespaces::DBPEDIA_ONTOLOGY_NAMESPACE));
    }

    /**
     */
    public function __toString()
    {
        return 'dimension ' . $this->getName();
    }
    
    public function addUnit( $unit )
    {
        PhpUtil::assertType($unit, 'dbpedia\ontology\dataTypes\UnitDataType', 'unit');
        
        $unit->setDimension($this);
        
        foreach ($unit->getLabels() as $label)
        {
            if (isset($this->units[$label])) throw new \InvalidArgumentException($this . ' already has unit label ' . $label);
            $this->units[$label] = $unit;
        }
    }
    
    /**
     * @return UnitDataType object.
     * @throws InvalidArgumentException if label is null
     * @throws InvalidArgumentException if there is no unit for the given label 
     */
    public function getUnit( $label )
    {
        if (! is_string($label)) throw new \InvalidArgumentException('label must be a string');
        if (! isset($this->units[$label])) throw new \InvalidArgumentException($this . ' does not have unit label ' . $label);
        return $this->units[$label];
    }

    /**
     * @return array of strings, unit labels used in template property values 
     */
    public function getUnitLabels()
    {
        return array_keys($this->units);
    }
}
}
