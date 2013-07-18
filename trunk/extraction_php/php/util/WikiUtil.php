<?php
namespace dbpedia\util
{

use dbpedia\util\PhpUtil;
use dbpedia\util\StringUtil;

/**
 * Contains several utility functions related to WikiText.
 */
class WikiUtil
{
    /**
     * replace underscores by spaces, normalize duplicate spaces, trim spaces from start and end
     * @param $string string using '_' instead of ' '
     */
    public static function cleanSpace( $string )
    {
        return trim(preg_replace('/ +/', ' ', str_replace('_', ' ', $string)));
    }
    
    /**
     * All of the following names will be encoded to '%C3%89mile_Zola': 
     * 'Émile Zola', 'émile Zola', 'Émile_Zola', ' Émile  Zola ', '  Émile _ Zola  '
     * 
     * TODO: maybe we should expect (require) the name to be normalized, e.g. with uppercase
     * first letter and without duplicate spaces or spaces at start or end? Would make this
     * method much simpler.
     *   
     * @param $name Non-encoded MediaWiki page name, e.g. 'Émile Zola'.
     * Must not include the namespace (e.g. 'Template:').
     */
    public static function wikiEncode( $name )
    {   
        PhpUtil::assertString($name, 'name');
        
        // replace spaces by underscores.
        // Note: MediaWiki apparently replaces only spaces by underscores, not other whitespace. 
        $name = str_replace(' ', '_', $name);
        
        // normalize duplicate underscores
        $name = preg_replace('/_+/', '_', $name);
        
        // trim underscores from start 
        $name = preg_replace('/^_/', '', $name);
        
        // trim underscores from end 
        $name = preg_replace('/_$/', '', $name);
        
        // make first character uppercase
        $name = StringUtil::mb_ucfirst($name);
        
        // URL-encode everything but '/' and ':' - just like MediaWiki
        $name = urlencode($name);
        $name = str_replace('%2F', '/', $name);
        $name = str_replace('%3A', ':', $name);
        
        return $name;
    }
    
    /**
     * All of the following names will be encoded to 'Émile Zola': 
     * '%C3%89mile_Zola', '%C3%A9mile_Zola', ' %C3%A9mile Zola ', ' %C3%A9mile _ Zola ', '  Émile _ Zola  '
     * 
     * TODO: maybe we should expect (require) the name to be normalized, e.g. with uppercase
     * first letter and without duplicate spaces or spaces at start or end? 
     * Would make this method much simpler.
     *   
     * @param $name encoded MediaWiki page name, e.g. '%C3%89mile_Zola'.
     * Must not include the namespace (e.g. 'Template:').
     */
    public static function wikiDecode( $name )
    {   
        PhpUtil::assertString($name, 'name');
        
        // make first character uppercase
        $name = StringUtil::mb_ucfirst(self::cleanSpace(urldecode($name)));
        
        return $name;
    }

    /**
     * Removes Wiki emphasis.
     *
     * @param $text
     * @return The given text without the wiki emphasis
     */
    public static function removeWikiEmphasis($text)
    {
        // note: I was tempted to replace these three by a single regex,
        // but it wouldn't really work.
        
        $text = preg_replace("/'''''(.*?)'''''/s", "$1", $text);
        $text = preg_replace("/'''(.*?)'''/s", "$1", $text);
        $text = preg_replace("/''(.*?)''/s", "$1", $text);
        return $text;
    }
    
}
}
