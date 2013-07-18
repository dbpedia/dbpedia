<?php
namespace dbpedia\wikiparser
{
/**
 * Represents a table.
 *
 * The rows are represented as child nodes.
 * Each row itself contains a child node for each of its cells.
 * Cell attributes such as 'rowspan' and 'colspan' are appended as annotations to the cell.
 */
class TableNode extends Node
{
    private $caption = null;

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
     * Retrieves the caption of this table.
     *
     * @return string
     */
    public function getCaption()
    {
        return $this->caption;
    }

    /**
     * Sets the caption of this table.
     *
     * @param $caption The caption of this table
     */
    public function setCaption($caption)
    {
        $this->caption = $caption;
    }

    public function __toString()
    {
        return $this->toString('');
    }

    protected function toString($indentStr)
    {
        $str  = $indentStr.'Table'.PHP_EOL;
        $str .= $indentStr.'{'.PHP_EOL;
        $str .= $indentStr.'  Line: '.$this->getLine().PHP_EOL;
        $str .= $indentStr.'  Caption: '.$this->caption.PHP_EOL;
        $str .= $indentStr.'  Rows:'.PHP_EOL;
        foreach($this->getChildren() as $child)
        {
            $str .= $child->toString($indentStr.'    ');
        }
        $str .= $indentStr.'}';

        return $str.PHP_EOL;
    }
}
}
