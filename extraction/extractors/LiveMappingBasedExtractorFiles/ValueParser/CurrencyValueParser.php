<?php

class CurrencyValueParser
    implements IValueParser
{
    private $language;

    public function __construct($language)
    {
        $this->language = $language;
    }

    public function parse($value)
    {
        return UnitValueParser::parseValue(
            $value,
            $this->language,
            array("Currency"));
    }
}
