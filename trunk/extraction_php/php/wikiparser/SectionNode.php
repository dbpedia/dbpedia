<?php
namespace dbpedia\wikiparser
{
/**
 * Represents a section.
 */
class SectionNode extends Node
{
    private $name;
    private $level;

    /**
     * Constructor.
     *
     * @param $line The line number of this node.
     */
    public function __construct($line)
    {
        parent::__construct($line);
    }

    /**
     * Retrieves the name of this section.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the name of this section.
     *
     * @param $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * The nesting level of this section.
     * 1 is the top-most level.
     *
     * @return int
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * Sets the nesting level of this section.
     *
     * @param $level
     */
    public function setLevel($level)
    {
        $this->level = $level;
    }

    public function __toString()
    {
        return $this->toString('');
    }

    protected function toString($indentStr)
    {
        $str  = $indentStr.'Section'.PHP_EOL;
        $str .= $indentStr.'{'.PHP_EOL;
        $str .= $indentStr.'  Line: '.$this->getLine().PHP_EOL;
        $str .= $indentStr.'  Name: \''.$this->name.'\''.PHP_EOL;
        $str .= $indentStr.'  Level: \''.$this->level.'\''.PHP_EOL;
        $str .= $indentStr.'}';

        return $str.PHP_EOL;
    }
}
}
