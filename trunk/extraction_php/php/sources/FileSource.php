<?php

namespace dbpedia\sources
{

use dbpedia\util\StringUtil;
use dbpedia\util\FileProcessor;
use dbpedia\wikiparser\WikiTitle;

/**
 * Reads all Wikipedia pages inside a base directory.
 */
class FileSource
extends Source
{
    private $logger;
    
    /**
     * @var FileProcessor
     */
    private $processor;
    
    /**
     * @param $baseDir must end with a directory separator (slash or backslash)
     * @param $skipNames names (not paths) of files and directories to skip, e.g. '.svn'. 
     * If not given, all files and directories will be included.
     * @param $paths array of strings, paths of files to use, relative to base dir,
     * using forward slashes. If not given, all files and directories will be included.
     */
    public function __construct( $baseDir, $skipNames = null, $paths = null )
    {
        parent::__construct();
        $this->logger = \dbpedia\core\DBpediaLogger::getLogger(__CLASS__);
        $this->processor = new FileProcessor($baseDir, $skipNames, $paths);
    }
    
    /**
     * @return array of PageNode objects
     */
    public function loadPages()
    {
        $pages = array();
        $collector = function ($page) use (&$pages) { $pages[] = $page; };
        $this->processPages($collector);
        return $pages;
    }

    /**
     * @param $processor callback function to process a single PageNode.
     * Must have two parameters: WikiTitle and page source.
     */
    public function processSources( $processor )
    {
        $logger = $this->logger;

        $callback =
        function ($path, $source) use (&$processor, $logger)
        {
            // cut off '#1.txt' or '.txt' if necessary
            
            $sep = strrpos($path, '#');
            if ($sep === false)
            { 
                $sep = strrpos($path, '.');
                if ($sep === false) $sep = -1;
            }
            
            $slash = strrpos($path, '/');
            if ($slash === false) $slash = -1;
            
            $pageName = $sep > $slash ? substr($path, 0, $sep) : $path;
            
            try
            {
                $title = WikiTitle::parse($pageName);
            }
            catch (\Exception $e)
            {
                $logger->warn('failed to get wiki title for path  ' . $path . ' / page name ' . $pageName . ': ' . $e->getMessage() . PHP_EOL . $e->getTraceAsString());
                return;
            }
                
            call_user_func($processor, $title, $source);
        };
        
        $this->processor->processFiles($callback);
    }

}

}
