<?php
/**
 * @mainpage DBpedia
 *
 * The %DBpedia framework is partinioned into a number of modules:
 *
 * @li The wikiparser module provides a parser that builds an Abstract Syntax Tree from WikiText.
 * @li The ontology module provides classes to handle ontologies.
 * @li The mapping module contains a %mapping tree which extracts data from a page.
 * @li The dataparser contains various parsers.
 * @li The sources module abstracts a collection of Wikipedia pages.
 * @li The destinations module abstracts a sink of linked data.
 */

/**
 * Root namespace of the %DBpedia framework.
 */
namespace dbpedia
{
/**
 * This file must be included, in order to import the DBpedia
 * extraction framework (imports every interface).
 */

ini_set("memory_limit", "500M");

require_once ('log4php/Logger.php');
\Logger::configure('logging.properties');

if (!defined('DBPEDIA_DIR')) define('DBPEDIA_DIR', dirname(__FILE__));

spl_autoload_register(array('dbpedia\DBpedia', 'autoload'));

// mb_strtoupper() and mb_strtolower() seem to need UTF-8.
// TODO: we should use mb_strtoupper() instead of strtoupper()
// and mb_strtolower() instead of strtolower() EVERYWHERE.
mb_internal_encoding('UTF-8');

set_error_handler(function($errno, $errstr, $errfile, $errline)
{
    die("Fatal error on line $errline in file $errfile: Details: $errstr");
}, E_RECOVERABLE_ERROR);

class DBpedia
{
    // autoloader
    static function autoload( $className )
    {
        if(strpos($className, "dbpedia\\") === 0)
        {
             $path = DBPEDIA_DIR . '/' . substr(str_replace("\\", '/', $className), 8) . '.php';
             if (! file_exists($path)) echo new \Exception('file not found: ' . $path);
             require_once($path);
             // TODO: log stacktrace when loading fails. the following does not seem to work:
             // $success = require_once($path);
             // if ($success === false) debug_print_backtrace();
        }
    }
}
}
