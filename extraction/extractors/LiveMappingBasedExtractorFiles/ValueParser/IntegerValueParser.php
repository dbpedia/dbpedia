<?php

class IntegerValueParser
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
        return NumberParser::parseValue(
            $value,
            $this->language,
            array("integer"));
    }
}
