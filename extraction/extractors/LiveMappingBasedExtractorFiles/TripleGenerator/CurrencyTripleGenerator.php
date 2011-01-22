<?php
class CurrencyTripleGenerator
    implements ITripleGenerator
{
    private $language;
    private $parser;

    private $breadCrumbTransformer;

    public function __construct($language)
    {
        $this->language = $language;
        $this->parser = new CurrencyValueParser($language);
        $this->breadCrumbTransformer = new DefaultBreadCrumbTransformer();
    }

    public function generate($subjectName, $propertyName, $value)
    {
        $result = array();

        $value = Util::removeHtmlTags($value);
        $value = Util::removeHtmlComments($value);

        $parseResultArray = $this->parser->parse($value);

        if(isset($parseResultArray)) {
            foreach ($parseResultArray as $parseResults) {
                $parsedDataType = $parseResults[1];
                if($parsedDataType == "")
                    $parsedDataType = null;

                if($parseResults[0] != "") {
                    $result[] = new RDFtriple(
                            RDFtriple::page($subjectName),
                            RDFtriple::URI(DB_ONTOLOGY_NS.$propertyName),
                            RDFtriple::Literal((string)$parseResults[0], $parsedDataType, null));
                }
            }
        }
        else
        {
            $result[] = new RDFtriple(
                RDFtriple::page($subjectName),
                RDFtriple::URI(DB_ONTOLOGY_NS . $propertyName),
                RDFtriple::Literal($value));
        }
        return $result;
    }
}
