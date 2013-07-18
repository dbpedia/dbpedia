<?php

namespace dbpedia\util
{

class StringUtil
{
    public static function startsWith( $string, $prefix )
    {
        $len = strlen($prefix);
        if ($len > strlen($string)) return false;
        return substr_compare($string, $prefix, 0, $len) === 0;
    }
    
    public static function endsWith( $string, $suffix ) 
    {
        $len = strlen($suffix);
        if ($len > strlen($string)) return false;
        return substr_compare($string, $suffix, -$len, $len) === 0;
    }
    
    
    /**
     * Convert all HTML entities to their applicable characters. 
     * Also replace nbsp (Unicode 160) by a normal space (Unicode 32).
     * 
     * @param $text text that does contain HTML character entities
     * @return text The text without any HTML entity
     */
    public static function htmlDecode( $text )
    {
        // apos is not an HTML character entity, but to be safe...
        $text = str_replace('&apos;', '\'', $text);

        // decode all character entities (including quotes), leave invalid entities untouched
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');

        // replace nbsp by normal space. F***ing PHP needs the UTF-8 code. 
        $text = str_replace("\xC2\xA0", ' ', $text);

        return $text;
    }
    
	/**
     *
     */
    public static function mb_ucfirst( $string )
    {
        return mb_strtoupper(mb_substr($string, 0, 1)) . mb_substr($string, 1);
    }

    /**
     *
     */
    public static function mb_lcfirst( $string )
    {
        return mb_strtolower(mb_substr($string, 0, 1)) . mb_substr($string, 1);
    }

}

}
