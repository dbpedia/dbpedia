<?php
namespace dbpedia\ontology\dataTypes
{

use dbpedia\ontology\OntologyNamespaces;

/**
 * Represents an enumeration of literals.
 */
class EnumerationDataType extends DataType
{
    private $literals;

    /**
     * @param $name name used in ontology and mapping configuration
     * @param $literals list of literals which are contained in this enumeration
     */
    public function __construct($name)
    {
        parent::__construct($name, OntologyNamespaces::getUri($name, OntologyNamespaces::DBPEDIA_ONTOLOGY_NAMESPACE));
    }

    public function addLiteral($name, $keywords = array())
    {
        $this->literals[] = array_merge(array($name), $keywords);
    }

    public function parse($text)
    {
        foreach($this->literals as $literal)
        {
           foreach($literal as $keyword)
           {
               if(preg_match('/\b'.$keyword.'\b/i', $text) === 1)
               {
                   return $literal[0];
               }
           }
        }

        return null;
    }
}
}
