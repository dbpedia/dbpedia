<?php
namespace dbpedia\ontology
{
/**
 * Represents a class in the ontology.
 */
class OntologyClass
{
    const TEMPLATE_NAME = 'DBpediaClass';

    private $name;
    private $uri;
    private $label;
    private $subClassOf;
    private $equivalentClass;

    /**
     * Constructor.
     *
     * @param $name The name of this class e.g. foaf:Person
     */
    public function __construct($name)
    {
        $this->uri = OntologyNamespaces::getUri($name, OntologyNamespaces::DBPEDIA_CLASS_NAMESPACE);
        $this->name = $name;
    }

    /**
     * The name of this class.
     *
     * @return string The name of this class e.g. foaf:Person
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * The uri of this class.
     *
     * @return string The uri of this class e.g. http://xmlns.com/foaf/0.1/Person
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * The label which has been defined for this class.
     *
     * @return string The label of this class or null if no label has been defined for this class.
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Sets the label which has been defined for this class.
     *
     * @param $label The label of this class or null to remove the current label.
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * The base class of this class.
     *
     * @return OntologyClass The base class of this class or null if no base class has been specified.
     */
    public function getSubClassOf()
    {
        return $this->subClassOf;
    }

    /**
     * Sets the base class of this class.
     *
     * @param $subClassOf The base class of this class or null if this class does not have any base class.
     */
    public function setSubClassOf(OntologyClass $subClassOf)
    {
        $this->subClassOf = $subClassOf;
    }

    /**
     * The equivalent class of this class.
     *
     * @return OntologyClass The equivalent class of this class or null if no equivalent class has been specified.
     */
    //We may want to return a list of equivalent classes instead
    public function getEquivalentClass()
    {
        return $this->equivalentClass;
    }

    /**
     * Sets the equivalent class of this class.
     * 
     * @param $equivalentClass The equivalent class of this class or null if this class does not have any equivalent class.
     */
    public function setEquivalentClass(OntologyClass $equivalentClass)
    {
        $this->equivalentClass = $equivalentClass;
    }

    public function __toString()
    {
        $str = '';

        $str .= "Class\n";
        $str .= "------------------------------\n";
        $str .= "Name: '".$this->name."'\n";
        $str .= "Label: '".$this->label."'\n";
        $str .= "SubClassOf: '".(isset($this->subClassOf) ? $this->subClassOf->getName() : "owl:Thing")."'\n";
        if(isset($this->equivalentClass))
        {
            $str .= "EquivalentClass: '".$this->equivalentClass->getName()."'\n";
        }

        return $str;
    }
}
}
