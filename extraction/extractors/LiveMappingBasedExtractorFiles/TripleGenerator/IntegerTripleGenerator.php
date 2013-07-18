<?php
class IntegerTripleGenerator
    implements ITripleGenerator
{
    private $language;
    private $parser;

    public function __construct($language)
    {
        $this->language = $language;
        $this->parser = new FloatValueParser($language);
    }

    public function generate($subjectName, $propertyName, $value)
    {
        $result = array();

        if (strpos($value, "{{") !== false)
            $value = Util::replaceTemplates($prop);

        $value = Util::removeHtmlTags($value);
        $value = Util::removeHtmlComments($value);
        $value = Util::removeWikiEmphasis($value);

        $parseResults = $this->parser->parse($value);

        if(isset($parseResults))
            $result[] = new RDFtriple(
                RDFtriple::page($subjectName),
                RDFtriple::URI(DB_ONTOLOGY_NS .$propertyName),
                RDFtriple::Literal($parseResults));
        else //TODO: ADD DEGUB LOGFILE FOR UN-PARSED TRIPLES
            $result[] = new RDFtriple(
                RDFtriple::page($subjectName),
                RDFtriple::URI(DB_ONTOLOGY_NS .$propertyName),
                RDFtriple::Literal($value));

        return $result;
    }
}
