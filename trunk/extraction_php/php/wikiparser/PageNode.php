<?php
namespace dbpedia\wikiparser
{
/**
 * Represents a page.
 */
class PageNode extends Node
{
    private /*WikiTitle*/ $title;

    /**
     * Constructor.
     *
     * @param $title The title of the page.
     */
    public function __construct(WikiTitle $title)
    {
        parent::__construct(1);
        $this->title = $title;
    }

    /**
     * Returns the title of this page.
     *
     * @return WikiTitle An instance of WikiTitle, which represents the title of this page.
     */
    public function getTitle()
    {
        return $this->title;
    }

    public function __toString()
    {
        return $this->toString('');
    }

    protected function toString($indentStr)
    {
        $str  = $indentStr.'Page'.PHP_EOL;
        $str .= $indentStr.'{'.PHP_EOL;
        $str .= $indentStr.'  Title: '.$this->title.PHP_EOL;
        $str .= $indentStr.'  Children:'.PHP_EOL;
        foreach($this->getChildren() as $child)
        {
            $str .= $child->toString($indentStr.'    ');
        }
        $str .= $indentStr.'}';

        return $str.PHP_EOL;
    }
}
}
