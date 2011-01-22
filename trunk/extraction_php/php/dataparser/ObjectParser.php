<?php
namespace dbpedia\dataparser
{
use dbpedia\wikiparser\PropertyNode;
use dbpedia\wikiparser\Node;
use dbpedia\ontology\OntologyNamespaces;

/**
 * Description of ObjectParser
 *
 * @author Paul Kreis
 */
class ObjectParser implements DataParser
{
    private $name = "ObjectParser";
    private $range;
    
    public function __construct($range)
    {
        $this->range = $range;
    }

    public function parse(Node $node)
    {
        if ($node instanceof PropertyNode)
        {
            $children = $node->getChildren('LinkNode');
            foreach ($children as $child)
            {
                if (!$child->isExternalLink())
                {
                    $link = $child->getDestination();
                    if ($link)
                    {
                        return OntologyNamespaces::getUri($link->encoded(), OntologyNamespaces::DBPEDIA_INSTANCE_NAMESPACE);
                    }
                }
            }
        }
        return null;
    }

    public function __toString()
    {
        return "Parser '".$this->name."'".PHP_EOL;
        /*
        $str = '';
        $str .= "     Parser".PHP_EOL;
        $str .= "     ------".PHP_EOL;
        $str .= "     Class: '".$this->name."'".PHP_EOL;
        $str .= "     Range: '".$this->range->getName()."'".PHP_EOL;
        return $str;
        */
    }
}
}
