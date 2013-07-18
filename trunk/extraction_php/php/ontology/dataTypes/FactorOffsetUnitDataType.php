<?php
namespace dbpedia\ontology\dataTypes
{
/**
 * Represents a unit that can be converted to the standard unit
 * of its dimension by multiplying by a factor and adding an offset.
 * Conversion from the standard unit is the inverse: subtract the
 * offset, divide by the factor.
 */
class FactorOffsetUnitDataType extends UnitDataType
{
    /**
     * @var float 
     */
    private /* final */ $factor;
    
    /**
     * @var float 
     */
    private /* final */ $offset;
    
    /**
     * @param $name string, unit name used in ontology and mapping configuration
     * @param $labels list of strings, unit labels used in template property values
     * @param $factor
     * @param $offset
     */
    public function __construct( $name, $labels, $factor, $offset )
    {
        parent::__construct($name, $labels);
        if (! is_float($factor)) throw new \Exception('factor must be a float');
        $this->factor = $factor;
        if (! is_float($offset)) throw new \Exception('offset must be a float');
        $this->offset = $offset;
    }
    
    /**
     * converts a value from this unit to the standard unit for the dimension.
     */
    public function toStandardUnit( $value )
    {
        if (! is_float($value)) throw new \Exception('value must be a float');
        return $value * $this->factor + $this->offset;
    }
    
    /**
     * converts a value from the standard unit for the dimension to this unit.
     */
    public function fromStandardUnit( $value )
    {
        if (! is_float($value)) throw new \Exception('value must be a float');
        return ($value - $this->offset) / $this->factor;
    }
}
}
