<?php
namespace dbpedia\core
{

use \dbpedia\ontology\OntologyDataTypeProperty;
use \dbpedia\ontology\OntologyProperty;
use \dbpedia\ontology\OntologyObjectProperty;
use \dbpedia\ontology\dataTypes\UnitDataType;
use \dbpedia\ontology\dataTypes\DimensionDataType;
use \InvalidArgumentException;
use \Exception;

class RdfQuad
{
    private $subject;
    private $predicate;
    private $object;
    private $sourceUri;
    private $type;

    private $language = "en";
    
    private $validationErrors = array();

    // TODO: why the extra type parameter? we should use the range of the predicate.
    // Or are there cases when we want to use a different type? More specific type?
    // Should we check that the given type is compatible with the range?
    function __construct($subject, $predicate, $object, $sourceUri, $type = null)
    {
        if($subject == null) throw new InvalidArgumentException('Given subject is null');
        if($predicate == null) throw new InvalidArgumentException('Given predicate is null');
        if($object == null) throw new InvalidArgumentException('Given object is null');
        if($sourceUri == null) throw new InvalidArgumentException('Given sourceUri is null');

        $this->subject = $subject;
        $this->predicate = $predicate;
        $this->object = $object;
        $this->sourceUri = $sourceUri;
        $this->type = $type ? $type : $predicate->getRange();
        if (!$this->isValid())
        {
            throw new InvalidArgumentException("RDF Quad is invalid:\n".join("\n", $this->validationErrors));
        }
    }

    private function isValid()
    {
        $valid = true;

        if (!$this->isValidUri($this->subject))
        {
            $this->validationErrors[] = "- Subject URI is invalid: '".$this->subject.",'";
            $valid = false;
        }

        if(!($this->predicate instanceof OntologyProperty))
        {
            $this->validationErrors[] = "- predicate has invalid type";
            $valid = false;
        }

        if (($this->predicate instanceof OntologyObjectProperty) && (!$this->isValidUri($this->object)))
        {
            $this->validationErrors[] = "- Object URI is invalid: '".$this->object."'";
            $valid = false;
        }

        if ($this->predicate instanceof OntologyDataTypeProperty)
        {
            // TODO: why check this special case here, but not others? We should
            // check that the given type is a sub type of the predicate range.
            if (($this->type instanceof DimensionDataType))
            {
                $this->validationErrors[] = "- Ontology Property Range is not a Unit: '".$this->type->getName()."'";
                $valid = false;
            }
        }

        if (! $this->isValidUri($this->sourceUri))
        {
            $this->validationErrors[] = "- source URI is invalid: '".$this->sourceUri."',";
            $valid = false;
        }

        return $valid;
    }

    private function isValidUri($uri)
    {
        require_once("Validate.php");
        $validate = new Validate();
        $valid = $validate->uri($uri);

        if (! $valid) echo new Exception('invalid URI [' . $uri . ']');
        
        return $valid;
    }

    public function toNTriple()
    {
        return $this->render(false);
    }
    
    public function toNQuad()
    {
        return $this->render(true);
    }
    
    private function render( $quad )
    {
        $result = '';
        
        $result .= '<'.$this->subject.'> ';
        
        $result .= '<'.$this->predicate->getUri().'> ';
        
        if ($this->predicate instanceof OntologyDataTypeProperty)
        {
            $escapedObject = self::escape($this->object);
            if ($this->type->getName() == "xsd:string")
            {
                // ultrapedia quad store doesn't like the language tag. remove it for now.
                $result .= '"'.$escapedObject.'" ';
                // $result .= '"'.$escapedObject.'"@en ';
            }
            else if ($this->type instanceof UnitDataType || $this->type instanceof DimensionDataType)
            {
                //Ontoprise TripleStore cannot handle custom datatypes
                $result .= '"'.$escapedObject.'"^^<http://www.w3.org/2001/XMLSchema#double> ';
            }
            else
            {
                $result .= '"'.$escapedObject.'"^^<'.$this->type->getUri().'> ';
            }
        }
        else
        {
            $result .= '<'.$this->object.'> ';
        }
        
        if ($quad)
        {
            $result .= '<'.$this->sourceUri.'> ';
        }
        
        $result .= '.';
        
        return $result;
    }

    private static function escape( $str ) 
    {
        // ultrapedia quad store doesn't need / like SPARQL encoding, 
        // so we just escape backslash, quote, \n and \r.
        return addcslashes($str, "\\\"\n\r");
    }

    public function __toString()
    {
        return $this->toNQuad();
    }
}
}
