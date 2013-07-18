<?php

include('dbpedia.php');
error_reporting(E_ALL);

Timer::start("main::Runtime");

// Setup the language version of Wikipedia
$language = Options::getOption('language');

$pageTitles = new AllTemplatesSqlIterator($language);

$job = new ExtractionJob(
       new DatabaseWikipediaCollection($language),
       $pageTitles);
		

$groupInfoboxes = new ExtractionGroup(new NullDestination());
$groupInfoboxes->addExtractor(new TemplateRedirectExtractor());

// Add the ExtractionGroups to the ExtractionJob 
$job->addExtractionGroup($groupInfoboxes);

//Execute the Extraction Job
$manager = new ExtractionManager();
$manager->execute($job);

Timer::stop("main::Runtime");
Timer::printTime();

