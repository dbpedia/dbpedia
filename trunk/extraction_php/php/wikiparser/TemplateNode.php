<?php
namespace dbpedia\wikiparser
{
/**
 * Represents a template.
 */
class TemplateNode extends Node
{
    private $title = null;

    private $propertyMap = array();
    private $curIndex = 1;

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
     * @return The WikiTitle of the template.
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Sets the name of this template.
     *
     * @param The name of this template
     */
    public function setTitle(WikiTitle $title)
    {
        $this->title = $title;
    }

    /**
     * Retrieves a property by its key.
     *
     * @param $key The key of the wanted property.
     * @return PropertyNode The propertyNode or null if no property with a given key has been found.
     */
    public function getProperty($key)
    {
        if(!isset($this->propertyMap[$key]))
        {
            return null;
        }
        return $this->propertyMap[$key];
    }

    /**
     * Adds a new property to this template.
     *
     * @param $node The property node
     */
    public function addChild(Node $node)
    {
        $this->addProperty($node);
    }

    /**
     * Adds a new property to this template.
     *
     * @param $node The property node
     */
    public function addProperty(PropertyNode $node)
    {
        if($node->getKey() == null)
        {
            $this->propertyMap[$this->curIndex] = $node;
            $node->setKey($this->curIndex);
            $this->curIndex++;
        }
        else
        {
            //If there is already a property with the same key, remove it from the children list
            if(isset($this->propertyMap[$node->getKey()]))
            {
                $this->removeChild($this->propertyMap[$node->getKey()]);
            }

            $this->propertyMap[$node->getKey()] = $node;
        }

        parent::addChild($node);
    }

    public function __toString()
    {
        return $this->toString('');
    }

    protected function toString($indentStr)
    {
        $str  = $indentStr.'Template'.PHP_EOL;
        $str .= $indentStr.'{'.PHP_EOL;
        $str .= $indentStr.'  Line: '.$this->getLine().PHP_EOL;
        $str .= $indentStr."  Name: '".$this->title."'".PHP_EOL;
        $str .= $indentStr.'  Properties:'.PHP_EOL;
        foreach($this->getChildren() as $child)
        {
            $str .= $child->toString($indentStr.'    ');
        }
        $str .= $indentStr.'}';

        return $str.PHP_EOL;
    }
}
}
