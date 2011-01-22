<?php

/**
 *
 *
 *  author: Georgi Kobilarov (FU Berlin)
 */
 
class StringParser implements Parser
{
	const parserID = "http://dbpedia.org/parsers/StringParser";

	public static function getParserID() {
        return self::parserID;
    }
	
	public static function parseValue($input, $language, $restrictions) {
		
		$mystring = $input;
		$mystring = Util::removeWikiEmphasis($mystring);
		$mystring = Util::replaceWikiLinks($mystring);
		$mystring = Util::removeHtmlTags($mystring, "ref");
		$mystring = Util::removeHtmlTags($mystring, "sup");
		$mystring = Util::removeHtmlTags($mystring, "nowiki");
		$mystring = Util::removeHtmlTags($mystring, "small");
		$mystring = Util::removeHtmlTags($mystring, "big");
		$mystring = Util::removeHtmlTags($mystring, "a");
		$mystring = Util::removeHtmlComments($mystring);
		$mystring = Util::removeTemplates($mystring);
		$mystring = Util::replaceExternalLinks($mystring);

                $mystring = preg_replace("~\s{2,}~", " ", $mystring);
                
		$matches = preg_split("/<br \/>/", $mystring, -1, PREG_SPLIT_NO_EMPTY);
	
		foreach($matches as $m) {
			$matches2 = preg_split("/<br\/>/", $m);
			
			foreach($matches2 as $m2) {
				$m2 = trim($m2, ",");
				$m2 = trim($m2, ";");
				if (strlen($m2) > 0) {
					$values[] = trim($m2);
				}
			}
		}
		return $values;
	}
	
}

