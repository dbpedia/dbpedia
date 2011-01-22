<?php
/**
 * Provides classes to handle ontologies.
 */
namespace dbpedia\ontology
{
/**
 * Represents an ontology.
 */
class Ontology
{
    /** Array mapping names to their corresponding ontology class */
    private $classes = array();

    /** Array mapping names to their corresponding ontology property */
    private $properties = array();
    
    /**
     * Map from canonical data type names ('xsd:int', 'Length', 'metre') to DataType objects.
     */
    private $dataTypes = array();

    /**
     * Retrieves an iterator, which can be used to iterate through all available ontology classes.
     *
     * @return Iterator over OntologyClass instances
     */
    public function getClassIterator()
    {
        return new \ArrayObject($this->classes);
    }

    /**
     * Retrieves a specific ontology class by its name.
     *
     * @param $name The name of the wanted ontology class.
     * @return OntologyClass The requested ontology class or null if no class with the given name has been found.
     */
    public function getClass( $name )
    {
        if(! isset($this->classes[$name])) throw new \InvalidArgumentException('no class with name ' . $name);
        return $this->classes[$name];
    }

    /**
     * Adds a new ontology class.
     *
     * @param $ontologyClass
     */
    public function addClass( OntologyClass $ontologyClass )
    {
        $name = $ontologyClass->getName();
        if (isset($this->classes[$name])) throw new \InvalidArgumentException('class with name ' . $name . ' already exists');
        $this->classes[$name] = $ontologyClass;
    }

    /**
     * Removes an ontology class.
     *
     * @param $ontologyClass
     */
    public function removeClass( OntologyClass $ontologyClass )
    {
        unset($this->classes[$ontologyClass->getName()]);
    }

    /**
     * Retrieves an iterator, which can be used to iterate through all available ontology properties.
     */
    public function getPropertyIterator()
    {
        return new \ArrayObject($this->properties);
    }

    /**
     * Retrieves a specific ontology property by its name.
     *
     * @param $name The name of the wanted ontology property.
     * @return OntologyProperty The requested ontology property or null if no property with the given name has been found.
     */
    public function getProperty( $name )
    {
        if(! isset($this->properties[$name])) throw new \InvalidArgumentException('no property with name ' . $name);
        return $this->properties[$name];
    }

    /**
     * Adds a new ontology property.
     *
     * @param $ontologyProperty
     */
    public function addProperty( OntologyProperty $ontologyProperty )
    {
        $name = $ontologyProperty->getName();
        if (isset($this->properties[$name])) throw new \InvalidArgumentException('property with name ' . $name . ' already exists');
        $this->properties[$name] = $ontologyProperty;
    }

    /**
     * Removes an ontology property.
     *
     * @param $ontologyProperty
     */
    public function removeProperty( OntologyProperty $ontologyProperty )
    {
        unset($this->properties[$ontologyProperty->getName()]);
    }
    
    /**
     * @param $dataType must be a DataType
     * @throws InvalidArgumentException if $dataType is not a DataType
     * @throws InvalidArgumentException if a data type with the name of the given object is already defined
     */
    public function addDataType( dataTypes\DataType $dataType )
    {
        $name = $dataType->getName();
        if (isset($this->dataTypes[$name])) throw new \InvalidArgumentException('data type with name ' . $name . ' already exists');
        $this->dataTypes[$name] = $dataType;
    }
    
    /**
     * @return a DataType, never null
     * @throws InvalidArgumentException if $name is not a string
     * @throws InvalidArgumentException if no data type with the given name is defined
     */
    public function getDataType( $name )
    {
        if (! is_string($name)) throw new \InvalidArgumentException('name must be a string');
        if (! isset($this->dataTypes[$name])) throw new \InvalidArgumentException('no data type with name ' . $name);
        return $this->dataTypes[$name];
    }

    /**
     * Returns a String representation of the contents of this ontology.
     * 
     * TODO: for many uses, this is too much information
     *
     * @return String
     */
    public function __toString()
    {
        $str = '';

        foreach($this->classes as $ontClass)
        {
            $str .= $ontClass.PHP_EOL;
        }
        foreach($this->properties as $ontProperty)
        {
            $str .= $ontProperty.PHP_EOL;
        }

        return $str;
    }
}
}
