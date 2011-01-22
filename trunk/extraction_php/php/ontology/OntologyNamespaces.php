<?php
namespace dbpedia\ontology
{
/**
 * Central place to organize all supported namespaces and their corresponding prefixes.
 */
class OntologyNamespaces
{
    const DBPEDIA_CLASS_NAMESPACE = 'http://ultrapedia/wiki/category#';
    const DBPEDIA_PROPERTY_NAMESPACE = 'http://ultrapedia/wiki/property#';
    const DBPEDIA_INSTANCE_NAMESPACE = 'http://ultrapedia/wiki/a#';
    // TODO: change to ultrapedia?
    const DBPEDIA_ONTOLOGY_NAMESPACE = 'http://dbpedia.org/ontology/';

    
    const OWL_PREFIX = 'owl';
    const RDF_PREFIX = 'rdf';
    const RDFS_PREFIX = 'rdfs';
    const FOAF_PREFIX = 'foaf';
    const GEO_PREFIX = 'geo';
    const GEORSS_PREFIX = 'georss';
    const GML_PREFIX = 'gml';

    const OWL_NAMESPACE = 'http://www.w3.org/2002/07/owl#';
    const RDF_NAMESPACE = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';
    const RDFS_NAMESPACE = 'http://www.w3.org/2000/01/rdf-schema#'; 
    const FOAF_NAMESPACE = 'http://xmlns.com/foaf/0.1/#';//TODO remove trailing hash as soon as Ontroprise removed the limitation of requiring it
    const GEO_NAMESPACE = 'http://www.w3.org/2003/01/geo/wgs84_pos#';
    const GEORSS_NAMESPACE = 'http://www.georss.org/georss/#';//TODO remove trailing hash as soon as Ontroprise removed the limitation of requiring it
    const GML_NAMESPACE = 'http://www.opengis.net/gml/#';//TODO remove trailing hash as soon as Ontroprise removed the limitation of requiring it

    /** 
     * Map containing all supported URI prefixes 
     * TODO: make these configurable. 
     */
    private static $prefixMap = array (
        self::OWL_PREFIX => self::OWL_NAMESPACE,
        self::RDF_PREFIX => self::RDF_NAMESPACE,
        self::RDFS_PREFIX => self::RDFS_NAMESPACE, 
        self::FOAF_PREFIX => self::FOAF_NAMESPACE,
        self::GEO_PREFIX => self::GEO_NAMESPACE,
        self::GEORSS_PREFIX => self::GEORSS_NAMESPACE,
        self::GML_PREFIX => self::GML_NAMESPACE
    );

    /**
     * Determines the full URI of a name.
     * e.g. foaf:name will be mapped to http://xmlns.com/foaf/0.1/name
     *
     * @param The name must be URI-encoded
     * @param $baseUri The base URI which will be used if no prefix (e.g. foaf:) has been found in the given name
     * @return string The URI
     */
    public static function getUri( $name, $baseUri )
    {
        $parts = explode(':', $name, 2);

        if (count($parts) === 2)
        {
            $prefix = $parts[0];
            $suffix = $parts[1];
            if (! isset(self::$prefixMap[$prefix]))
            {
                return self::appendUri($baseUri, $name);
                //throw new \InvalidArgumentException('unknown prefix ' . $prefix . ' in name ' . $name);
            }

            return self::appendUri(self::$prefixMap[$prefix], $suffix);
        }
        else
        {
            return self::appendUri($baseUri, $name);
        }
    }

    public static function appendUri( $baseUri, $suffix )
    {
        if (strpos($baseUri, '#') === false)
        {
            return $baseUri . $suffix;
        }
        else
        {
            // fragments must not contain ':' or '/', according to our Validate class
            return $baseUri . str_replace(':', '%3A', str_replace('/', '%2F', $suffix));
        }
    }

}
}
