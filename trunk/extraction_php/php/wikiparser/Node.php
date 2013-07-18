<?php
namespace dbpedia\wikiparser
{
/**
 * Base class of all nodes in the abstract syntax tree.
 */
class Node
{
    private $line;

    private $parent;

    private $children;

    private $annotations;

    /**
     * Constructor.
     *
     * @param $line The line number of this node.
     */
    public function __construct($line)
    {
        $this->line = $line;
        $this->children = array();
    }
    
    /**
     * Calls destroy() on all child nodes, deletes all references to child and parent nodes 
     * and all annotations from this object. Should help PHP 'garbage collector' that can't
     * really handle circular references. But also see http://php.net/gc_enable .
     */
    public function destroy()
    {
        foreach ($this->children as $child)
        {
            $child->destroy();
        }
        
        unset($this->children);
        unset($this->parent);
        unset($this->line);
        unset($this->annotations);
    }

    /**
     * Retrieves the line number of this node.
     *
     * @return The line number of this node
     */
    public function getLine()
    {
        return $this->line;
    }

    /**
     * Sets the line mumber of this node.
     *
     * @param The line number of this node
     */
    public function setLine($line)
    {
        $this->line = $line;
    }

    /**
     * Retrieves the parent of this node.
     *
     * @return The parent Node
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Retrieves the root node of this AST.
     *
     * @return The root Node
     */
    public function getRoot()
    {
        $node = $this;
        while($node->parent != null)
        {
            $node = $node->parent;
        }
        return $node;
    }

    /**
     * @return URL of source page and line number
     */
    public function getSourceUri()
    {
        $pageNode = $this->getRoot();

        //Get current section
        $section = null;
        foreach($pageNode->children as $node)
        {
            if($node->getLine() > $this->line)
            {
                break;
            }

            if($node instanceof SectionNode)
            {
                $section = $node;
            }
        }

        //Build source URI
        $sourceURI = $this->getSourceUriPrefix();

        if($section)
        {
            $sourceURI .= 'section=' . urlencode($section->getName());
            $sourceURI .= '&relative-line=' . ($this->line - $section->getLine());
            $sourceURI .= '&absolute-line=' . $this->line;
        }
        else if($this->line > 1)
        {
            $sourceURI .= 'section=' . $pageNode->getTitle()->encoded();
            $sourceURI .= '&relative-line=' . $this->line;
            $sourceURI .= 'absolute-line=' . $this->line;
        }
        
        return $sourceURI;
    }

    /**
     * Get first part of source URL. Needed for SPARUL DELETE statement.
     * TODO: It's ugly to have such a special-purpose function here. Is there a better way?
     * @return first part of source URL, containing the page name and the separator character, 
     * but not the line number etc.
     */
    public function getSourceUriPrefix()
    {
        // TODO: make base URI configurable
        return 'http://en.wikipedia.org/wiki/' . $this->getRoot()->getTitle()->encoded() . '#';
    }

    /**
     * Retrieves the children of this node.
     *
     * @param $typeName (Optional) Only retrieve nodes of the given type e.g. 'TemplateNode'
     * @return The children
     */
    public function getChildren($typeName = null)
    {
        if(!isset($this->children))
        {
            return array();
        }
        else if($typeName != null)
        {
            return array_filter($this->children, function ($input) use ($typeName) { return get_class($input) === 'dbpedia\\wikiparser\\'.$typeName; });
        }
        else
        {
            return $this->children;
        }
    }

    /**
     * Adds a new child node to this node.
     *
     * @param $node The child node
     */
    public function addChild(Node $node)
    {
        if(!isset($this->children))
        {
            $this->children = array();
        }
        $this->children[] = $node;
        $node->parent = $this;
    }

    /**
     * Removes a child node from this node.
     *
     * @param $node The node to be removed
     */
    public function removeChild(Node $node)
    {
        for($i = 0; $i < count($this->children); $i++)
        {
            if($node === $this->children[$i])
            {
                array_splice($this->children, $i, 1);
                break;
            }
        }
    }

    /**
     * Removes all children from this node.
     */
    public function removeChildren()
    {
        $this->children = array();
    }

    /**
     * Retrieves the text denoted by this node.
     * Only works on nodes that only contain text.
     * Returns null if this node contains child nodes other than TextNode.
     *
     * Overloaded by TextNode
     */
    public function getText()
    {
        if((count($this->children) == 1 && $this->children[0] instanceof TextNode))
        {
            return $this->children[0]->getText();
        }
        else
        {
            return null;
        }
    }

    /*
     * Returns an annotation.
     *
     * @param key key of the annotation
     * @return The value of the annotation or FALSE if no annotation with the given key exists.
     */
    public function getAnnotation($key)
    {
        if(!isset($this->annotations[$key]))
        {
            return false;
        }
        return $this->annotations[$key];
    }

    /**
     * Sets a user-defined annotation.
     *
     * @param $key The key of the annotation
     * @param $value The value of the annotation
     */
    public function setAnnotation($key, $value)
    {
        if(!isset($this->annotations))
        {
            $this->annotations = array();
        }
        $this->annotations[$key] = $value;
    }

    public function __toString()
    {
        return $this->toString('');
    }

    protected function toString($indentStr)
    {
        $str  = $indentStr.get_class().PHP_EOL;
        $str .= $indentStr.'{'.PHP_EOL;
        $str .= $indentStr.'  Children:'.PHP_EOL;
        foreach($this->children as $child)
        {
            $str .= $child->toString($indentStr.'    ');
        }
        $str .= $indentStr.'}';

        return $str.PHP_EOL;
    }
}
}
