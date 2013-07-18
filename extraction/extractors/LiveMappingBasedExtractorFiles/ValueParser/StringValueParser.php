<?php

class StringValueParser
    implements IValueParser
{
    private $language;
    //private $restrictions;

    public function __construct($language)
    {
        $this->language = language;
    }

    public function parse($value)
    {
        return StringParser::parseValue($value, $this->language, null);
    }
}
