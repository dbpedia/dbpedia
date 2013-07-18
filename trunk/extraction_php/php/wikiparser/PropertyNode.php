<?php
namespace dbpedia\wikiparser
{
/**
 * Represents a template property.
 */
class PropertyNode extends Node
{
    private $key = '';

    /**
     * Constructor.
     *
     * @param $line The line number of this node.
     */
    public function __construct($line = 0)
    {
        parent::__construct($line);
    }

    /**
     * Returns the key of this property.
     *
     * @return The key of this property
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Sets the key of this property.
     *
     * @param The key of this property
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * Splits this PropertyNode around matches of the given regular expression.
     *
     * @param $regex The delimiting regular expression
     * @return The array of property nodes computed by splitting this node around matches of the given regular expression
     */
    public function split($regex)
    {
        $children = $this->getChildren();

        $newPropertyNodes[] = new PropertyNode($this->getLine());
        $newPropertyNodes[sizeof($newPropertyNodes)-1]->setKey($this->getKey());

        foreach ($children as $childNode)
        {
            if ($childNode instanceof TextNode)
            {
                $texts = preg_split($regex, $childNode->getText());
                foreach ($texts as $id => $text)
                {
                    $textNode = new TextNode($text, $childNode->getLine());
                    if ($id > 0)
                    {
                        $newPropertyNodes[] = new PropertyNode($this->getLine());
                        $newPropertyNodes[sizeof($newPropertyNodes)-1]->setKey($this->getKey());
                    }
                    $newPropertyNodes[sizeof($newPropertyNodes)-1]->addChild($textNode);
                }
            }
            else
            {
                $newPropertyNodes[sizeof($newPropertyNodes)-1]->addChild($childNode);
            }
        }

        return $newPropertyNodes;
    }

    public function __toString()
    {
        return $this->toString('');
    }

    protected function toString($indentStr)
    {
        $str  = $indentStr.get_class().PHP_EOL;
        $str .= $indentStr.'{'.PHP_EOL;
        $str .= $indentStr.'  Line: '.$this->getLine().PHP_EOL;
        $str .= $indentStr.'  Key: \''.$this->key.'\''.PHP_EOL;
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
