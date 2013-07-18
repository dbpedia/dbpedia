<?php
class TextTripleGenerator
    implements ITripleGenerator
{
    private $language;
    private $breadCrumbTransformer;

    public function __construct($language)
    {
        $this->language = $language;
        $this->breadCrumbTransformer = new DefaultBreadCrumbTransformer();
    }

    public function generate($subjectName, $propertyName, $value)
    {
        $result = array();

        $image = "image";
        $parsedText = ActiveAbstractExtractor::stripMarkup($value, $image);
        $parsedText = trim($parsedText);

        if($parsedText != "") {
            $result[] = new RDFtriple(
                RDFtriple::page($subjectName),
                RDFtriple::URI(DB_ONTOLOGY_NS.$propertyName),
                RDFtriple::Literal($parsedText, null, $this->language));
        }

        return $result;
    }
}