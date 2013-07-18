<?php

/**
 *
 *
 *  author: Georgi Kobilarov (FU Berlin)
 */

class LiveObjectTypeParser implements Parser
{
    const parserID = "http://dbpedia.org/parsers/ObjectTypeParser";

    public static function getParserID() {
        return self::parserID;
    }

    public static function parseValue($input, $language, $restrictions)
    {
        $results = array();
        $filteredresults = array();

        preg_match_all("/\[\[([^:\]]*)\]\]/", $input, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            if (strlen($match[1]) > 255)
                continue;

            $results[] = self::getLinkForLabeledLink($match[1]);
        }

        return $results;
    }

    private static function getLinkForLabeledLink($text2) {
        return preg_replace("/\|.*/", "", $text2) ;
    }

    static function encodeLocalName($string) {
        $string = urlencode(str_replace(" ","_",trim($string)));
        // Decode slash "/", colon ":", as wikimedia does not encode these
        $string = str_replace("%2F","/",$string);
        $string = str_replace("%3A",":",$string);

        return $string;
    }


}


