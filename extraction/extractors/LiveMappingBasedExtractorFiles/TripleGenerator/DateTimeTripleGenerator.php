<?php


class DateTimeTripleGenerator
    implements ITripleGenerator
{
    private $language;
    private $parser;

    public function __construct($language)
    {
        $this->language = $language;
        $this->parser = new DateTimeValueParser($language);
    }

    public function generate($subjectName, $propertyName, $value)
    {
        $result = array();

        $parseResults = $this->parser->parse($value);
		ob_start();
		$str = "Date parser \n";
		$str .= "value was: $value \n";
		print_r($parseResults);
		$str .= ob_get_contents() ;
		ob_end_clean();
		
		Logger::debug($str);
		
        if(!isset($parseResults))
            return $result;

        $datePattern = "/\d\d\d\d-(0[1-9]|1[012])-(0[1-9]|[12][0-9]|3[01])/";
        /*
        if(preg_match($datePattern, $parseResults) != 1)
            return $result;

        $isValidDate = checkdate(
            substr($parseResults, 5, 2),
            substr($parseResults, 8, 2),
            substr($parseResults, 0, 4));
		*/
        $isValidDate = true;
        if($isValidDate) {
            $result[] = new RDFtriple(
                RDFtriple::page($subjectName),
                RDFtriple::URI(DB_ONTOLOGY_NS . $propertyName),
                RDFtriple::Literal($parseResults[0], $parseResults[1], null));

	        //print_r($result);
        }
        else {
            $result[] = new RDFtriple(
                RDFtriple::page($subjectName),
                RDFtriple::URI(DB_ONTOLOGY_NS .$propertyName),
                RDFtriple::Literal($parseResults, null, null));
        }

        return $result;
    }
}
