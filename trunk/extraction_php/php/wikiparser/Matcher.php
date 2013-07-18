<?php

namespace dbpedia\wikiparser
{
class Matcher
{
    private $stdTags;
    private $userTags;
    private $firstChars;

    private $tagIndex;
    private $tagLength;
    private $isStdTag;

    public function  __construct($userTags, $matchStdTags = false)
    {
        $this->userTags = $userTags;

        $this->stdTags = array();
        if($matchStdTags)
        {
            $this->stdTags[] = '[[';
            $this->stdTags[] = '[';
            $this->stdTags[] = '{{';
            $this->stdTags[] = '{|';
            $this->stdTags[] = "\n=";
            $this->stdTags[] = '<!--';
            $this->stdTags[] = '<ref';
            $this->stdTags[] = '<math';
        }

        //Build a string of all initial letters
        $firstCharsArray = array();
        foreach($this->stdTags as $tag)
        {
            $firstCharsArray[] = $tag[0];
        }
        foreach($this->userTags as $tag)
        {
            $firstCharsArray[] = $tag[0];
        }
        $firstCharsArray = array_unique($firstCharsArray);
        $this->firstChars = implode($firstCharsArray);

        $this->tagIndex = -1;
        $this->tagLength = 0;
        $this->isStdTag = false;
    }

    public function getTagIndex()
    {
        return $this->tagIndex;
    }

    public function getTagLength()
    {
        return $this->tagLength;
    }

    public function isStdTag()
    {
        return $this->isStdTag;
    }

    public function match($source, &$pos)
    {
        $sourceLength = strlen($source);

        for (; $pos < $sourceLength; $pos++)
        {
            $pos += strcspn($source, $this->firstChars, $pos);
            if($pos == $sourceLength)
            {
                return false;
            }

            foreach ($this->stdTags as $tagIndex => $tag)
            {
                $this->tagLength = strlen($tag);
                if($pos + $this->tagLength <= $sourceLength && substr_compare($source, $tag, $pos, $this->tagLength) === 0)
                {
                    $pos += $this->tagLength;
                    $this->tagIndex = $tagIndex;
                    $this->isStdTag = true;
                    return true;
                }
            }

            foreach ($this->userTags as $tagIndex => $tag)
            {
                $this->tagLength = strlen($tag);
                if($pos + $this->tagLength <= $sourceLength && substr_compare($source, $tag, $pos, $this->tagLength) === 0)
                {
                    $pos += $this->tagLength;
                    $this->tagIndex = $tagIndex;
                    $this->isStdTag = false;
                    return true;
                }
            }
        }

        return false;
    }
}
}
