<?php
namespace dbpedia\ontology
{

use dbpedia\ontology\dataTypes\DataType;
use dbpedia\util\PhpUtil;
    
/**
 * Represents a property in the ontology.
 */
class OntologyProperty
{
    private $name;
    private $label;
    private $domain;
    private $range;
    private $functional;

    /**
     * Constructor.
     *
     * @param $name The name of this property e.g. foaf:name
     * @param $domain 
     */
    public function __construct($name, $domain = null, $range = null)
    {
        PhpUtil::assertString($name, 'name');
        $this->name = $name;
        if ($domain !== null) $this->setDomain($domain);
        if ($range !== null) $this->setRange($range);
    }

    /**
     * The name of this property.
     *
     * @return string The name of this property e.g. foaf:name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * The uri of this property.
     *
     * @return string The uri of this property e.g. http://xmlns.com/foaf/0.1/name
     */
    public function getUri()
    {
        return OntologyNamespaces::getUri($this->name, OntologyNamespaces::DBPEDIA_PROPERTY_NAMESPACE);
    }

    /**
     * The label which has been defined for this property.
     *
     * @return string The label of this property or null if no label has been defined for this property.
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Sets the label which has been defined for this property.
     *
     * @param string $label The label of this property or null to remove the current label.
     */
    public function setLabel($label)
    {
        PhpUtil::assertString($label, 'label');
        $this->label = $label;
    }

    /**
     * The domain of this property.
     *
     * @return The domain of this property.
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * Sets the domain of this property.
     *
     * @return string The domain of this property.
     */
    public function setDomain(OntologyClass $domain)
    {
        $this->domain = $domain;
    }

    /**
     * The range of this property.
     *
     * @return string The range of this property.
     */
    public function getRange()
    {
        return $this->range;
    }

    /**
     * Sets the range of this property.
     *
     * @return string The range of this property.
     */
    public function setRange($range)
    {
        if (! ($range instanceof OntologyClass) && ! ($range instanceof DataType)) throw new \InvalidArgumentException('range must be an OntologyClass or a DataType, but has type ' . PhpUtil::typeNameOf($range));
        $this->range = $range;
    }

    /**
     * Indicates if this is an functional property i.e. it can have only one (unique) value for each instance.
     *
     * @return boolean True, if this is a functional property. False, otherwise.
     */
    public function isFunctional()
    {
        return $this->functional;
    }

    /**
     * Sets whether this is a functional property i.e. it can have only one (unique) value for each instance.
     *
     * @param $functional True, if this is a functional property. False, otherwise.
     */
    public function setFunctional($functional)
    {
        $this->functional = $functional;
    }

    public function __toString()
    {
        $str = '';

        $str .= "Property\n";
        $str .= "------------------------------\n";
        $str .= "Name: '".$this->name."'\n";
        $str .= "Label: '".$this->label."'\n";
        $str .= "Domain: '".(isset($this->domain) ? $this->domain->getName() : "owl:Thing")."'\n";
        $str .= "Range: '".(isset($this->range) ? $this->range->getName() : "owl:Thing")."'\n";

        return $str;
    }
}
}
