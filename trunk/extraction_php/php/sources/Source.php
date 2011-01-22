<?php

/**
 * Contains classes to read Wikipedia pages.
 */
namespace dbpedia\sources
{

use dbpedia\core\DBpediaLogger;
use dbpedia\wikiparser\WikiParser;
use dbpedia\wikiparser\WikiParserException;

/**
 * Represents a source of Wikipedia pages.
 * TODO: find a better name. Source is too generic.
 */
abstract class Source
{
    /**
     * @var DBpediaLogger
     */
    private $logger;
    
    /**
     * @var WikiParser
     */
    private $parser;

    /**
     * <b>ATTENTION</b>: Sub-classes that define their own constructor <b>MUST</b>
     * call this constructor using parent::__construct(). Initializes logger and parser. 
     */
    public function __construct()
    {
        $this->logger = DBpediaLogger::getLogger(__CLASS__);
        $this->parser = new WikiParser();
    }
    
    /**
     * @param $processor callback function to process a single PageNode. 
     * Must have one parameter of type PageNode.
     */
    public function processPages( $processor )
    {
        $logger = $this->logger;
        $parser = $this->parser;
        
        $callback =
        function ($title, $source) use ($processor, $parser, $logger)
        {
            try
            {
                $page = $parser->parse($title, $source);
            }
            catch (WikiParserException $e)
            {
                $logger->warn('Exception parsing ' . $title . ': ' . $e->getMessage() . PHP_EOL . $e->getTraceAsString());
                return;
            }
            
            call_user_func($processor, $page);
        };
        
        $this->processSources($callback);
    }
    
    /**
     * @param $processor callback function to process a single wiki page. 
     * Must have two parameters of types WikiTitle and string (page source).
     */
    public abstract function processSources( $processor );
}

}
