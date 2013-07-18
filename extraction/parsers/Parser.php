<?php

/**
 * Defines the interface Parser.
 * 
 * author: Georgi Kobilarov (FU-Berlin)
 */
 
 interface Parser {

    public static function getParserID();
	
	public static function parseValue($input, $language, $restrictions);
}

