<?php
namespace dbpedia\dataparser
{
use dbpedia\wikiparser\Node;
use dbpedia\util\WikiUtil;
use dbpedia\util\StringUtil;
use dbpedia\wikiparser\TextNode;
use dbpedia\wikiparser\LinkNode;

/**
 * Description of StringParser
 *
 * @author Paul Kreis
 */
class StringParser implements DataParser
{
    private $name = "StringParser";

    public function __construct()
    {

    }

    public function parse(Node $node)
    {
        $result = self::nodeToString($node);
        
        //Clean text
        $result = WikiUtil::removeWikiEmphasis($result);
        $result = StringUtil::htmlDecode($result);

        if (strlen($result)> 0)
        {
           return $result;
        }
    }

    private static function nodeToString($node)
    {
        $str = '';
        foreach($node->getChildren() as $child)
        {
            if ($child instanceof TextNode)
            {
                $str .= $child->getText();
            }
            else if ($child instanceof LinkNode)
            {
                $str .= self::nodeToString($child);
            }
        }
        return strip_tags($str);
    }

    public function __toString()
    {
        return "Parser '".$this->name."'".PHP_EOL;
    }
}
}
