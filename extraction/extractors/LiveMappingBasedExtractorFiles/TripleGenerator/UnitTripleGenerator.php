<?php
class UnitTripleGenerator
    implements ITripleGenerator
{
    private $language;
    private $parser;

    public function __construct($language, $unit)
    {
        $this->language = $language;
        $this->parser = new MyUnitValueParser($language, $unit);
    }

    public function generate($subjectName, $propertyName, $value)
    {
        $result = array();

        $value = Util::replaceWikiLinks($value);

        //TODO: WARUM NUR IN DIESEM FALL CITE RAUSNEHMEN?
        preg_match_all("/{{2}cite.*?\}{2}/i", $value, $matches);
        foreach ($matches as $match) {
            if(!array_key_exists(0, $match))
                continue;

            $value = str_replace(
                $match[0],
                Util::replaceTemplates($match[0]),
                $value);
        }

        $value = Util::removeHtmlTags($value);
        $value = Util::removeHtmlComments($value);

        // get unit exact type
        // Some arguments have a fixed type - e.g. weight_lb -> pounds
        $unitExactType = null;

        //UnitValueParser::parseValue($propvalue, $this->language, array($unit_type, $unit_exact_type, $propkey));
        $parseResultArray = $this->parser->parse($value);

        if(isset($parseResultArray)) {
            foreach ($parseResultArray as $parseResults) {
                $parsedDataType = $parseResults[1];
                if($parsedDataType == "") {
                    $parsedDataType = null;
                }
                $result[] = new RDFtriple(
                    RDFtriple::page($subjectName),
                    RDFtriple::URI(DB_ONTOLOGY_NS .$propertyName),
                    RDFtriple::Literal((string)$parseResults[0], $parsedDataType, null));
            }
        }
        else {

            //TODO: GENERATE LOGFILE WITH UNPARSED VALUES
            $result[] = new RDFTriple(
                RDFtriple::page($subjectName),
                RDFtriple::URI(DB_ONTOLOGY_NS .$propertyName),
                RDFtriple::Literal($value));
        }

        return $result;
    }
}
