<?php
namespace dbpedia\dataparser
{
/**
 * Thrown whenever a parsing error occurs.
 */
class DataParserException extends \Exception
{
    public function __construct($message)
    {
        parent::__construct($message);
    }
}
}
