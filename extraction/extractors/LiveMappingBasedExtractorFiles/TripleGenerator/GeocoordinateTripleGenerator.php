<?php
/* We don't have the parser yet (since we no longer have these merging rules)
 * Once we have it, we can update this class.
class GeoCoordinateTripleGenerator
    implements ITripleGenerator
{
    private $language;
    private $parser;

    public function __construct($language)
    {
        $this->language = $language;
        $this->parser = new ObjectValueParser($language);
    }

    public function generate($pageId, $propertyName, $value)
    {
        $result = array();

        //TODO: Predicate URIs entweder nur in DB oder nur hardcoden?
        $value = Util::removeHtmlTags($value);
        $value = Util::removeHtmlComments($value);
        $parseResultArray = GeoParser::parseValue($value, $this->language, null);
        if(!is_null($parseResultArray)) {

                // http://www.georss.org/georss/point:(NULL) 52.5166666667 13.4166666667
                // geo:lat 52.516666 (xsd:float)
                // geo:long 13.416667 (xsd:float)

                // $output = array('georss'=>$georss,'lat'=>$lat,'long'=>$long);

                $georss = $parseResultArray["georss"];
                $lat = $parseResultArray["lat"];
                $long = $parseResultArray["long"];

                if($georss != null){
                    $result->addTriple(
                    RDFtriple::page($pageId),
                    RDFtriple::URI("http://www.georss.org/georss/point"),
                    RDFtriple::Literal($georss));
                }
                if($lat != null){
                    $result->addTriple(
                    RDFtriple::page($pageId),
                    RDFtriple::URI("http://www.w3.org/2003/01/geo/wgs84_pos#lat"),
                    RDFtriple::Literal($lat, "http://www.w3.org/2001/XMLSchema#float",NULL));
                }
                if($long != null){
                    $result->addTriple(
                    RDFtriple::page($pageId),
                    RDFtriple::URI("http://www.w3.org/2003/01/geo/wgs84_pos#long"),
                    RDFtriple::Literal($long, "http://www.w3.org/2001/XMLSchema#float",NULL));
            }
        }
        else
        {
            //TODO: DEBUG LOGFILE MIT UN-PARSED VALUES

            $result->addTriple(
            RDFtriple::page($pageId),
            RDFtriple::URI("http://dbpe dia.org/ontology/" . $property_name),
            RDFtriple::Literal($propvalue));

        }

        return $result;
    }
}
*/