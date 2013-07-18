<?php
namespace dbpedia\ontology\dataTypes
{
/**
 * Represents a unit that can be converted to the standard unit
 * for its dimension by multiplication by a certain factor.
 */
class FactorUnitDataType extends UnitDataType
{
    /**
     * @var float toStandardUnit() multiplies its value by this factor.
     */
    private /* final */ $factor;
    
    /**
     * @param $name string, unit name used in ontology and mapping configuration
     * @param $labels list of strings, unit labels used in template property values
     * @param $factor toStandardUnit() multiplies its value by this factor.
     */
    public function __construct( $name, $labels, $factor )
    {
        parent::__construct($name, $labels);
        if (! is_float($factor)) throw new \Exception('factor must be a float');
        $this->factor = $factor;
    }
    
    /**
     * converts a value from this unit to the standard unit for the dimension.
     */
    public function toStandardUnit( $value )
    {
        if (! is_float($value)) throw new \Exception('value must be a float');
        return $value * $this->factor;
    }
    
    /**
     * converts a value from the standard unit for the dimension to this unit.
     */
    public function fromStandardUnit( $value )
    {
        if (! is_float($value)) throw new \Exception('value must be a float');
        return $value / $this->factor;
    }
}
}
