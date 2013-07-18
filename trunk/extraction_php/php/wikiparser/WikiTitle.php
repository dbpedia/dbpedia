<?php
namespace dbpedia\wikiparser
{

use dbpedia\util\PhpUtil;
use dbpedia\util\StringUtil;
use dbpedia\util\WikiUtil;

/**
 * Represents a page title.
 */
class WikiTitle
{
    // see http://en.wikipedia.org/wiki/Wikipedia:Namespace
    // and http://svn.wikimedia.org/svnroot/mediawiki/trunk/phase3/includes/Defines.php
    
    // virtual namespaces
    const NS_SPECIAL = -1;
    const NS_MEDIA = -2;
    
    const NS_MAIN = 0;
    const NS_TALK = 1;
    const NS_USER = 2;
    const NS_USER_TALK = 3;
    const NS_PROJECT = 4;
    const NS_PROJECT_TALK = 5;
    const NS_FILE = 6;
    const NS_FILE_TALK = 7;
    const NS_MEDIAWIKI = 8;
    const NS_MEDIAWIKI_TALK = 9;
    const NS_TEMPLATE = 10;
    const NS_TEMPLATE_TALK = 11;
    const NS_HELP = 12;
    const NS_HELP_TALK = 13;
    const NS_CATEGORY = 14;
    const NS_CATEGORY_TALK = 15;
    
    private static $nsNames;
    
    private static $nsCodes;
    
    private static function init()
    {
        if (isset(self::$nsNames)) return;
        
        // TODO: names are language-dependent, load them from configuration files
        self::$nsNames =
        array
        (
            self::NS_SPECIAL => array('Special'),
            self::NS_MEDIA => array('Media'),
            
            self::NS_MAIN => array(''), // main namespace doesn't have a prefix
            self::NS_TALK => array('Talk'),
            self::NS_USER => array('User'),
            self::NS_USER_TALK => array('User talk'),
            self::NS_PROJECT => array('Wikipedia', 'Project', 'WP'),
            self::NS_PROJECT_TALK => array('Wikipedia talk', 'Project talk', 'WT'),
            self::NS_FILE => array('File', 'Image'),
            self::NS_FILE_TALK => array('File talk', 'Image talk'),
            self::NS_MEDIAWIKI => array('MediaWiki'),
            self::NS_MEDIAWIKI_TALK => array('MediaWiki talk'),
            self::NS_TEMPLATE => array('Template'),
            self::NS_TEMPLATE_TALK => array('Template talk'),
            self::NS_HELP => array('Help'),
            self::NS_HELP_TALK => array('Help talk'),
            self::NS_CATEGORY => array('Category'),
            self::NS_CATEGORY_TALK => array('Category talk')
        );
        
        self::$nsCodes = array();
        foreach (self::$nsNames as $code => $names)
        {
            foreach ($names as $name)
            {
                self::$nsCodes[mb_strtolower($name)] = $code;
            }
        }
    }
    
    /**
     * @param $link MediaWiki link target
     */
    public static function parse( $link )
    {
        PhpUtil::assertString($link, 'link');

        self::init();
        
        $link = urldecode($link);

        if(strpos($link, '#') !== false)
        {
            throw new WikiParserException('Invalid title: "'.$link.'" (Contains #)');
        }
        
        $parts = explode(':', $link, 2);
        
        if (count($parts) === 2)
        {
            $prefix = mb_strtolower(WikiUtil::cleanSpace($parts[0]));
            
            // TODO: handle interwiki links like [[:de:Foo]]
            if (strlen($prefix) === 0) throw new WikiParserException('cannot handle link [' . $link . ']');
            
            // TODO: handle special prefixes, e.g. [[q:Foo]] links to WikiQuotes 
            if (isset(self::$nsCodes[$prefix]))
            {
                $code = self::$nsCodes[$prefix];
                $name = StringUtil::mb_ucfirst(WikiUtil::cleanSpace($parts[1]));
                return new WikiTitle($code, $name);
            }
        }
        
        $name = StringUtil::mb_ucfirst(WikiUtil::cleanSpace($link));
        return new WikiTitle(self::NS_MAIN, $name);
    }
    
    /** namespace code */
    private $nsCode;

    /** namespace name */
    private $nsName;

    /** decoded page name */
    private $decoded;
    
    /** encoded page name */
    private $encoded;
    
    /**
     * @param $nsCode namespace code
     * @param $decoded decoded page name. URL-decoded, using normalized spaces (not underscores), first letter uppercase.
     */
    public function __construct( $nsCode, $decoded )
    {
        PhpUtil::assertInteger($nsCode, 'namespace code');
        if (! isset(self::$nsNames[$nsCode])) throw new \InvalidArgumentException('unknown namespace code ' . $nsCode);
        PhpUtil::assertString($decoded, 'page name');
        if (strlen($decoded) === 0) throw new WikiParserException('page name must not be empty');
        
        $this->nsCode = $nsCode;
        $this->nsName = self::$nsNames[$nsCode][0];
        $this->encoded = WikiUtil::wikiEncode($decoded);
        // re-decode to make sure name is normalized
        $this->decoded = WikiUtil::wikiDecode($this->encoded);
    }

    /**
     * Returns the decoded title.
     */
    public function decoded()
    {
        return $this->decoded;
    }

    /**
     * Returns the encoded title.
     */
    public function encoded()
    {
        return $this->encoded;
    }
    
    /**
     * @return the namespace code.
     */
    public function nsCode()
    {
        return $this->nsCode;
    }

    /**
     * @return the namespace name, empty string for main namespace.
     */
    public function nsName()
    {
        return $this->nsName;
    }

    public function __toString()
    {
        $prefix = $this->nsName === '' ? '' : $this->nsName . ':';
        return $prefix . $this->decoded;
    }
}

/*

// a little test code for this class. TODO: use phpunit.

function assertEquals( $expected, $actual )
{
    if ($expected !== $actual) echo 'FAIL! expected: ', $expected, ' actual: ', $actual, PHP_EOL;
}

function test( $input, $code, $decoded, $encoded )
{
    $title = WikiTitle::parse($input);
    assertEquals($code, $title->nsCode());
    assertEquals($decoded, $title->decoded());
    assertEquals($encoded, $title->encoded());
}

test('foo', WikiTitle::NS_MAIN, 'Foo', 'Foo');
test(' Foo ', WikiTitle::NS_MAIN, 'Foo', 'Foo');
test('Émile Zola', WikiTitle::NS_MAIN, 'Émile Zola', '%C3%89mile_Zola');
test(' _ émile  _ Zola _ ', WikiTitle::NS_MAIN, 'Émile Zola', '%C3%89mile_Zola');
test(' _ %C3%A9mile _ _ Zola _  _ ', WikiTitle::NS_MAIN, 'Émile Zola', '%C3%89mile_Zola');
test(' _ %43ategory _ %3A _ %C3%89mile _ Zola _ ', WikiTitle::NS_CATEGORY, 'Émile Zola', '%C3%89mile_Zola');
test('Category:Impossible', WikiTitle::NS_CATEGORY, 'Impossible', 'Impossible');
test('  Category  :  Impossible  ', WikiTitle::NS_CATEGORY, 'Impossible', 'Impossible');
test(' Mission:Impossible ', WikiTitle::NS_MAIN, 'Mission:Impossible', 'Mission:Impossible');
test(' Mission:  Impossible ', WikiTitle::NS_MAIN, 'Mission: Impossible', 'Mission:_Impossible');
test(' Mission :  Impossible ', WikiTitle::NS_MAIN, 'Mission : Impossible', 'Mission_:_Impossible');
test('AC%2FDC', WikiTitle::NS_MAIN, 'AC/DC', 'AC/DC');

*/
}
