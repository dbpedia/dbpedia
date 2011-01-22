<?php

/**
 * 
 * author: Georgi Kobilarov (FU-Berlin)
 */
 
class PropertyMergeParser implements Parser
{
	const parserID = "http://dbpedia.org/parsers/PropertyMergeParser";

	public static function getParserID() {
        return self::parserID;
    }
	
	public static function parseValue($input, $language, $restrictions)
	{
		$results = array();
		
		//TODO: TOLLE SACHEN MACHEN
		
		return $results;
	
	}
		
}


