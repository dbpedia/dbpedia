<?php
namespace dbpedia\wikiparser
{
/**
 * Represents a Link.
 * The children of this node represent the label of the link.
 * If the source does not define a label explicitly, a TextNode containing the link destination will be the only child.
 */
class LinkNode extends Node
{
    private $destination;
    private $externalLink;

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
     * Returns the destination of this link.
     * @return URI, if this is an external link.
     *         WikiTitle, if this is an internal link.
     */
    public function getDestination()
    {
        return $this->destination;
    }

    /**
     * True, if this is an external link. False, if this is an internal link.
     */
    public function isExternalLink()
    {
        return $this->externalLink;
    }

    /**
     * Sets the destination of this link.
     */
    public function setDestination($destination, $isExternal)
    {
        assert(is_string($destination));
        assert(is_bool($isExternal));

        if($isExternal)
        {
            $this->destination = $destination;
        }
        else
        {
            try
            {
                $this->destination = WikiTitle::parse($destination);
            }
            catch (WikiParserException $e)
            {
                // FIXME: this is a hack. We currently cannot deal with links like [[:de:Foo]],
                // so we just say that these links are external (which is correct), but we don't
                // convert them to a correct URL yet - we just keep ":de:Foo" as the destination.
                $this->destination = $destination;
                $isExternal = true;
            }
        }

        $this->externalLink = $isExternal;
    }

    public function __toString()
    {
        return $this->toString('');
    }

    protected function toString($indentStr)
    {
        $str  = $indentStr.'Link'.PHP_EOL;
        $str .= $indentStr.'{'.PHP_EOL;
        $str .= $indentStr.'  Line: '.$this->getLine().PHP_EOL;
        //$str .= $indentStr.'  Label: '.$this->label.PHP_EOL;
        $str .= $indentStr.'  Destination: '.$this->destination.PHP_EOL;
        $str .= $indentStr.'  External: '.($this->externalLink ? 'true' : 'false').PHP_EOL;
        $str .= $indentStr.'}';

        return $str.PHP_EOL;
    }
}
}
