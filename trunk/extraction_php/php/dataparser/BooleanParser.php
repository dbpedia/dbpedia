<?php
namespace dbpedia\dataparser
{
use dbpedia\wikiparser\Node;
class BooleanParser implements DataParser
{
    private $name = "BooleanParser";

    public function __construct()
    {
    }

    public function parse(Node $node)
    {
        $children = $node->getChildren();
        foreach ($children as $child)
        {
            if ($child instanceof TextNode)
            {
                $text = $child->getText();
                if ((preg_match("~no~", $text)) || (preg_match("~false~", $text)))
                {
                    return "false";
                }
                else if ((preg_match("~yes~", $text)) || (preg_match("~true~", $text)))
                {
                    return "true";
                }
            }
        }
        return null;
    }

    public function __toString()
    {
        $str = '';
        $str .= "Parser".PHP_EOL;
        $str .= "-------".PHP_EOL;
        $str .= "Name:          '".$this->name."'".PHP_EOL;
        return $str;
    }
}
}
