<?php
/**
 * Provides classes to handle data types.
 */
namespace dbpedia\ontology\dataTypes
{
/**
 * Base class of all data types.
 */
class DataType
{
    private /* final */ $name;

    private /* final */ $uri;

    public function __construct( $name, $uri )
    {
        if (! is_string($name)) throw new \Exception('name must be a string');
        if (! is_string($uri)) throw new \Exception('uri must be a string');
        $this->name = $name;
        $this->uri = $uri;
    }

    public function getName()
    {
        return $this->name;
    }
    
    public function getUri()
    {
        return $this->uri;
    }
    
    /**
     */
    public function __toString()
    {
        return 'data type ' . $this->uri;
    }
    
}
}
