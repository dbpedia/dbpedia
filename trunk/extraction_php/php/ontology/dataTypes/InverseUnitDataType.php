<?php
namespace dbpedia\ontology\dataTypes
{
/**
 * Represents a unit that can be converted to / from the standard unit
 * of its dimension by multiplying the inverse by a certain factor.
 */
class InverseUnitDataType extends UnitDataType
{
    /**
     * @var float toStandardUnit() divides its value by this factor,
     * fromStandardUnit() multiplies its value by this factor.
     */
    private /* final */ $factor;
    
    /**
     * @param $name string, unit name used in ontology and mapping configuration
     * @param $labels list of strings, unit labels used in template property values
     * @param $factor must be a float. 
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
        return convert($value);
    }
    
    /**
     * converts a value from the standard unit for the dimension to this unit.
     */
    public function fromStandardUnit( $value )
    {
        return convert($value);
    }
    
    /**
     * converts a value between the standard unit and this unit.
     */
    private function convert( $value )
    {
        if (! is_float($value)) throw new \Exception('value must be a float');
        return $this->factor / $value;
    }
}
}
