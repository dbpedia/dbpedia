<?php
namespace dbpedia\ontology
{
use \dbpedia\ontology\dataTypes\UnitDataType;
use \dbpedia\ontology\dataTypes\DimensionDataType;

/**
 * Writes an ontology using the RDF/XML format.
 */
class OWLOntologyWriter
{
    const VERSION = "3.4 2009-10-05";

    public function toOWL(Ontology $ontology)
    {
        $output="<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $output.="<rdf:RDF\n";
        $output.="  xmlns = \"http://dbpedia.org/ontology/\"\n";
        $output.="  xml:base=\"http://dbpedia.org/ontology/\"\n";
        $output.="  xmlns:owl=\"http://www.w3.org/2002/07/owl#\"\n";
        $output.="  xmlns:xsd=\"http://www.w3.org/2001/XMLSchema#\"\n";
        $output.="  xmlns:rdf=\"http://www.w3.org/1999/02/22-rdf-syntax-ns#\"\n";
        $output.="  xmlns:rdfs=\"http://www.w3.org/2000/01/rdf-schema#\">\n";

        $output.="\t<owl:Ontology rdf:about=\"\">\n";
        $output.="\t\t<owl:versionInfo xml:lang=\"en\">Version ".OWLOntologyWriter::VERSION."</owl:versionInfo>\n";
        $output.="\t</owl:Ontology>\n\n";

        foreach($ontology->getClassIterator() as $class)
        {
            //Only write classes from the default namespace (Don't write owl, rdf and rdfs built-in classes etc.)
            if(strpos($class->getName(), ':') === false)
            {
                $this->writeClass($class, $output);
            }
        }

        foreach($ontology->getPropertyIterator() as $property)
        {
            //Only write properties from the default namespace (Don't write owl, rdf and rdfs built-in properties etc.)
            if(strpos($property->getName(), ':') === false)
            {
                $this->writeProperty($property, $output);
            }
        }

        $output.="</rdf:RDF>\n";

        return $output;
    }

    private function writeClass(OntologyClass $class, &$output)
    {
        $output.="\t<owl:Class rdf:about=\"".$class->getUri()."\">\n";

        if ($class->getLabel())
        {
            $output.="\t\t<rdfs:label xml:lang=\"en\">".$class->getLabel()."</rdfs:label>\n";
        }

        if ($class->getSubClassOf())
        {
            $output.="\t\t<rdfs:subClassOf rdf:resource=\"".$class->getSubClassOf()->getUri()."\"/>\n" ;
        }

        if ($class->getEquivalentClass())
        {
            $output.="\t\t<owl:equivalentClass rdf:resource=\"".$class->getEquivalentClass()->getUri()."\"/>\n" ;
        }

        $output.="\t</owl:Class>\n\n";
    }

    private function writeProperty(OntologyProperty $property, &$output)
    {
        if($property instanceof OntologyObjectProperty)
        {
            $output .= "\t<owl:ObjectProperty rdf:about=\"".$property->getUri()."\">\n";
        }
        else
        {
            $output .= "\t<owl:DatatypeProperty rdf:about=\"".$property->getUri()."\">\n";
        }

        if ($property->isFunctional())
        {
             $output .= "\t\t<rdf:type rdf:resource=\"http://www.w3.org/2002/07/owl#FunctionalProperty\" />\n";
        }

        if ($property->getLabel())
        {
            $output .= "\t\t<rdfs:label xml:lang=\"en\">".$property->getLabel()."</rdfs:label>\n";
        }

        if ($property->getDomain())
        {
            $output.="\t\t<rdfs:domain rdf:resource=\"".$property->getDomain()->getUri()."\"/>\n" ;
        }

        if ($property->getRange())
        {
            if ($property->getRange() instanceof UnitDataType || $property->getRange() instanceof DimensionDataType)
            {
                //Ontoprise TripleStore cannot handle custom datatypes
                $output.="\t\t<rdfs:range rdf:resource=\"http://www.w3.org/2001/XMLSchema#double\"/>\n";
            }
            else
            {
                $output.="\t\t<rdfs:range rdf:resource=\"".$property->getRange()->getUri()."\"/>\n";
            }
        }

        if($property instanceof OntologyObjectProperty)
        {
            $output .= "\t</owl:ObjectProperty>\n\n";
        }
        else
        {
            $output .= "\t</owl:DatatypeProperty>\n\n";
        }
    }
}

}
