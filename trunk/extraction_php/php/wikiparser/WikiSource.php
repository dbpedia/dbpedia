<?php
namespace dbpedia\wikiparser
{
/**
 * Internal class which is used by the WikiParser to represent the source and keep track of the current position of the parser.
 */
class WikiSource
{
    private $source;
    private $sourceLength;
    private $pos;
    private $line;

    /**
     * Constructor.
     *
     * @param $source The source the page
     */
    public function __construct(&$source)
    {
        $this->source = $source;
        $this->sourceLength = strlen($source);
        $this->pos = 0;
        $this->line = 1;
    }

    public function getPos()
    {
        return $this->pos;
    }

    public function setPos($pos)
    {
        $this->pos = $pos;
    }

    public function getLine()
    {
        return $this->line;
    }

    public function setLine($line)
    {
        $this->line = $line;
    }

    public function getLength()
    {
        return $this->sourceLength;
    }

    public function seek($count)
    {
        if($this->pos + $count <= $this->sourceLength)
        {
            $this->line += substr_count($this->source, "\n", $this->pos, $count);
            $this->pos += $count;

            return true;
        }
        else
        {
            $this->pos = $this->sourceLength - 1;

            return false;
        }
    }

    /**
     * Advances the current position to the next match of a given matcher.
     *
     * @param $matcher The matcher
     * @return The index of the tag which has been found, or false if the end of the souce has been reached
     */
    public function find(Matcher $matcher)
    {
        $oldPos = $this->pos;

        $result = $matcher->match($this->source, $this->pos);

        if($this->pos !== $oldPos)
        {
            $this->line += substr_count($this->source, "\n", $oldPos, $this->pos - $oldPos);
        }

        return $result;
    }
    
    public function nextTag($tag)
    {
        return $this->pos + strlen($tag) <= $this->sourceLength && substr_compare($this->source, $tag, $this->pos, strlen($tag)) === 0;
    }

    public function lastTag($tag)
    {
        return $this->pos >= strlen($tag) && substr_compare($this->source, $tag, $this->pos - strlen($tag), strlen($tag)) === 0;
    }

    /**
     *  Retrieves a section of the source text.
     *
     * @param $startPos - the beginning position, inclusive.
     * @param $endPos - the ending position, exclusive or null if the current position should denote the end of the section.
     * @return A string containing the specified section.
     */
    public function getString($startPos, $endPos = null)
    {
        if($endPos === null)
        {
            return substr($this->source, $startPos, $this->pos - $startPos);
        }
        else
        {
            return substr($this->source, $startPos, $endPos - $startPos);
        }
    }

    /**
     * Finds a specific line in the source.
     *
     * @param $line The line number
     * @return String The line
     */
    public function findLine($lineNumber)
    {
        //Find line beginning
        for($begin = 0; $begin < $this->sourceLength - 1 && $lineNumber > 1; $begin++)
        {
            if($this->source[$begin] == "\n")
            {
                $lineNumber--;
            }
        }

        //Find line ending
        $end = $begin;
        while($end < $this->sourceLength - 1 && $this->source[$end + 1] != "\n")
        {
            $end++;
        }

        $lineStr = substr($this->source, $begin, $end - $begin + 1);
        return $lineStr;
    }
}
}
