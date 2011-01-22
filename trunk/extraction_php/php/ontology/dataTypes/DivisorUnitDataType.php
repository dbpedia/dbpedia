<?php
namespace dbpedia\ontology\dataTypes
{
/**
 * Represents a unit that can be converted to the standard unit
 * for its dimension by division by a certain divisor.
 */
class DivisorUnitDataType extends UnitDataType
{
    /**
     * @var float toStandardUnit() divides its value by this divisor
     */
    private /* final */ $divisor;
    
    /**
     * @param $name string, unit name used in ontology and mapping configuration
     * @param $labels list of strings, unit labels used in template property values
     * @param $divisor toStandardUnit() divides its value by this divisor
     */
    public function __construct( $name, $labels, $divisor )
    {
        parent::__construct($name, $labels);
        if (! is_float($divisor)) throw new \Exception('divisor must be a float');
        $this->divisor = $divisor;
    }
    
    /**
     * converts a value from this unit to the standard unit for the dimension.
     */
    public function toStandardUnit( $value )
    {
        if (! is_float($value)) throw new \Exception('value must be a float');
        return $value / $this->divisor;
    }
    
    /**
     * converts a value from the standard unit for the dimension to this unit.
     */
    public function fromStandardUnit( $value )
    {
        if (! is_float($value)) throw new \Exception('value must be a float');
        return $value * $this->divisor;
    }
}
}
