<?php


/**
 * This triple generator uses the old infobox-extractor code
 * not used
 * /
class DefaultTripleGenerator
    implements ITripleGenerator
{
    private $language;
    //private $parser;

    public function __construct($language)
    {
        $this->language = $language;
        //$this->parser = new StringValueParser($language);
    }

    public function generate($pageId, $propertyName, $value)
    {
        $result = array();

        // An item consists of ($object,$object_is,$dtype,$lang)
        // object_is: 'r' if object is a reference, 'l' if object is a literalm 'b' if object is a blanknode
        $itemList = parseAttributeValue(
            $value, $pageId, $propertyName, $this->language);

        foreach($itemList as $item) {
            $itemValue    = $item[0];
            $objectType   = $item[1];
            $dataType     = $item[2];
            $itemLanguage = $item[4];

            //echo "Got object type '$objectType'\n";
            if($objectType == "r")
                $object = RDFtriple::URI($itemValue);
            else if($objectType == "l")
                $object = RDFtriple::Literal($itemValue, $dataType, $itemLanguage);
            else
                Logger::warn("Shouldn't happen - found a blank node where none expected");

            $result[] = new RDFtriple(
                RDFtriple::page($pageId),
                RDFtriple::URI(DB_ONTOLOGY_NS.$propertyName),
                $object);
        }

        return $result;
    }
}
*/