<?php
namespace dbpedia\dataparser
{
use dbpedia\wikiparser\TextNode;
use dbpedia\wikiparser\Node;
use dbpedia\ontology\dataTypes\EnumerationDataType;

class EnumerationParser implements DataParser
{
    private $enumerationDataType;

    public function __construct($enumerationDataType)
    {
        assert($enumerationDataType instanceof EnumerationDataType);

        $this->enumerationDataType = $enumerationDataType;
    }

    public function parse(Node $node)
    {
        foreach($node->getChildren() as $child)
        {
            if ($child instanceof TextNode)
            {
                $result = $this->enumerationDataType->parse($child->getText());
            }
            else
            {
                $result = $this->parse($child);
            }

            if($result !== null)
            {
                return $result;
            }
        }

        return null;
    }

    public function __toString()
    {
        return get_class();
    }
}
}
