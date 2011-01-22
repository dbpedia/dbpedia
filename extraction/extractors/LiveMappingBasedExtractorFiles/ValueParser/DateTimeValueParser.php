<?php


class DateTimeValueParser
    implements IValueParser
{
    private $language;

    public function __construct($language)
    {
        $this->language = $language;
    }

    public function parse($value)
    {
        return DateTimeParser::parseValue($value, $this->language, null);
    }
}
