<?php

class FloatValueParser
    implements IValueParser
{
    private $language;

    public function __construct($language)
    {
        $this->language = $language;
    }

    public function parse($value)
    {
        return NumberParser::parseValue(
            $value,
            $this->language,
            array("float"));
    }
}
