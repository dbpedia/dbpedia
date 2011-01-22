<?php


/**
 * Not used anymore
 *
 */
class DirectTripleGenerator
    implements ITripleGenerator
{
    private $language;
    private $parser;

    public function __construct($language, $parser)
    {
        $this->language = $language;
        $this->parser = $parser;//new StringValueParser($language);
    }

    public function generate($subjectName, $propertyName, $value)
    {
        $result = array();

        $parseResults = $this->parser->parse($value);
        foreach($parseResults as $mystring) {
            $result[] = new RDFtriple(
                RDFtriple::page($subjectName),
                RDFtriple::URI(DB_ONTOLOGY_NS.$propertyName),
                RDFtriple::Literal($mystring));
        }

        return $result;
    }
}