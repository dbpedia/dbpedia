<?php
class ObjectTripleGenerator
    implements ITripleGenerator
{
    private $language;
    private $parser;

    public function __construct($language)
    {
        $this->language = $language;
        $this->parser = new ObjectValueParser($language);
    }

    public function generate($subjectName, $propertyName, $value)
    {
        $result = array();

        $value = Util::removeWikiEmphasis($value);

        //TODO:ADD LANGUAGE AS PARAM
        $parseResults = $this->parser->parse($value);

        foreach($parseResults as $r)
            $result[] = new RDFtriple(
                RDFtriple::page($subjectName),
                RDFtriple::URI(DB_ONTOLOGY_NS .$propertyName),
                RDFtriple::page($r));

        return $result;
    }
}