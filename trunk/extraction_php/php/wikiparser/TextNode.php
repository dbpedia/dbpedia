<?php
namespace dbpedia\wikiparser
{
/**
 * Represents text.
 */
class TextNode extends Node
{
    private $str;

    /**
     * Constructor.
     *
     * @param $str The text
     * @param $line The line number of this node
     */
    public function __construct($str, $line)
    {
        parent::__construct($line);
        $this->str= $str;
    }

    public function getText()
    {
        return $this->str;
    }

    public function setText($str)
    {
        $this->str = $str;
    }

    public function __toString()
    {
        return $this->toString('');
    }

    protected function toString($indentStr)
    {
        $str  = $indentStr.'Text'.PHP_EOL;
        $str .= $indentStr.'{'.PHP_EOL;
        $str .= $indentStr.'  Line: '.$this->getLine().PHP_EOL;
        $str .= $indentStr.'  Contents: \''.$this->str.'\''.PHP_EOL;
        $str .= $indentStr.'}';

        return $str.PHP_EOL;
    }
}
}
