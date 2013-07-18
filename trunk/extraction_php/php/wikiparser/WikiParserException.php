<?php
namespace dbpedia\wikiparser
{
/**
 * This exception will be thrown if an error occurs during parsing.
 */
class WikiParserException extends \Exception
{
    private $wikiLineNumber;
    private $wikiLine;

    public function __construct($message, $wikiLineNumber = 0, $wikiLine = 'Unknown')
    {
        assert(is_int($wikiLineNumber));
        assert(is_string($wikiLine));

        parent::__construct($message.' Line '.$wikiLineNumber.': \''.$wikiLine.'\'');

        $this->wikiLineNumber = $wikiLineNumber;
        $this->wikiLine = $wikiLine;
    }

    public function getWikiLineNumber()
    {
        return $this->wikiLineNumber;
    }

    public function getWikiLine()
    {
        return $this->wikiLine;
    }
}
}
