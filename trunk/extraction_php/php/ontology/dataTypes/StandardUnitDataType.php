<?php
namespace dbpedia\ontology\dataTypes
{
/**
 * Represents the standard unit of a dimension.
 */
class StandardUnitDataType extends UnitDataType
{
    /**
     * Note: we could inherit parent::__construct, but we want to be explicit and Java-like.
     * @param $name string, unit name used in ontology and mapping configuration
     * @param $labels list of strings, unit labels used in template property values
     */
    public function __construct( $name, $labels )
    {
        parent::__construct($name, $labels);
    }
    
    /**
     * returns the given value.
     * @throws InvalidArgumentException if given value is not a float.
     */
    public function toStandardUnit( $value )
    {
        return $this->check($value);
    }
    
    /**
     * returns the given value.
     * @throws InvalidArgumentException if given value is not a float.
     */
    public function fromStandardUnit( $value )
    {
        return $this->check($value);
    }
    
    /**
     * returns the given value.
     * @throws InvalidArgumentException if given value is not a float.
     */
    private function check( $value )
    {
        if (! is_float($value)) throw new \InvalidArgumentException('value must be a float');
        return $value;
    }
}
}
