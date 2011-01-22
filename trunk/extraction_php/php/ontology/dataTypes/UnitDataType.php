<?php
namespace dbpedia\ontology\dataTypes
{

use dbpedia\ontology\OntologyNamespaces;

/**
 * Represents a unit of a certain dimension, converts numerical values
 * to and from equivalent values in the standard unit of that dimension.
 */
abstract class UnitDataType extends DataType
{
    /**
     * @var DimensionDataType
     */
    private $dimension;
    
    /**
     * @var list of labels
     */
    private $labels;
    
    /**
     * @param $name string, unit name used in ontology and mapping configuration
     * @param $labels list of strings, unit labels used in template property values
     */
    public function __construct( $name, $labels )
    {
        parent::__construct($name, OntologyNamespaces::getUri($name, OntologyNamespaces::DBPEDIA_ONTOLOGY_NAMESPACE));
        
        if (! is_array($labels)) throw new \InvalidArgumentException('labels must be an array');
        $this->labels = $labels;
    }
    
    /**
     * @return array of strings, unit labels used in template property values
     */
    public function getLabels()
    {
        return $this->labels;
    }
    
    /**
     */
    public function __toString()
    {
        return 'unit ' . $this->getName();
    }
    
    /**
     * Called by DimensionDataType::addUnit(). Must not be called twice with different values.
     * @param $dimension DimensionDataType
     * @throws InvalidArgumentException if parameter is null or setDimension() has already been called 
     * with a different value.
     */
    public function setDimension( $dimension )
    {
        if ($dimension === null) throw new \InvalidArgumentException('dimension must not be null');
        if ($this->dimension !== null && $this->dimension !== $dimension) throw new \InvalidArgumentException('cannot replace ' . $this->dimension . ' by ' . $dimension . ' on ' . $this);
        $this->dimension = $dimension;
    }
    
    /**
     * @return DimensionDataType
     * @throws Exception if setDimension() has never been called
     */
    public function getDimension()
    {
        if ($this->dimension === null) throw new \Exception('dimension not set');
        return $this->dimension;
    }
    
    /**
     * converts a value from this unit to the standard unit for the dimension.
     * @param $value value in this unit, must be a float.
     * @return equivalent value in standard unit, as a float.
     */
    public abstract function toStandardUnit( $value );
    
    /**
     * converts a value from the standard unit for the dimension to this unit.
     * @param $value value in standard unit, must be a float.
     * @return equivalent value in this unit, as a float.
     */
    public abstract function fromStandardUnit( $value );
}
}
