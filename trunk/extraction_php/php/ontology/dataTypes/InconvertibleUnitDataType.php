<?php
namespace dbpedia\ontology\dataTypes
{
/**
 * Represents a unit that cannot be converted to any other unit.
 */
class InconvertibleUnitDataType extends UnitDataType
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
     * TODO: better exception class
     * @throws InvalidArgumentException always 
     */
    public function toStandardUnit( $value )
    {
        throw new \InvalidArgumentException($this . ' cannot be converted');
    }
    
    /**
     * TODO: better exception class
     * @throws InvalidArgumentException always
     */
    public function fromStandardUnit( $value )
    {
        throw new \InvalidArgumentException($this . ' cannot be converted');
    }
}
}
