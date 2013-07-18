<?php

/**
 * Start file for testing an extractor on a specific language.
 * This runs the extractor on the full language database. The
 * main purpose is to produce single data sets in contrast to
 * performing a full extraction (as in extract.php) or testing
 * an extractor on a specific article only (as in start.php).
 *
 * See http://wiki.dbpedia.org/Documentation for an overview of
 * the DBpedia extraction framework.
 *
 * @author Jens Lehmann
 */

// autoloader
include('dbpedia.php');

// configure settings
$language = Options::getOption('language');
$extractor = new HomepageExtractor();
$outputFilePrefix = 'test';

// sets up a job and executes it (usually you do not need to changes this code)
$pageTitles = new ArticlesSqlIterator($language);
$job = new ExtractionJob(new DatabaseWikipediaCollection($language),$pageTitles);
$group = new ExtractionGroup(new csvNTripleDestination($outputFilePrefix));
$group->addExtractor($extractor);
$job->addExtractionGroup($group);
Logger::info( "Job created.\n");

$manager = new ExtractionManager();
$manager->execute($job);
Logger::info("Job finished.\n");

