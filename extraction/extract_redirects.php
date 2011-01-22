<?php


include('dbpedia.php');

error_reporting(E_ALL);
Timer::start("main::Runtime");

$language = Options::getOption('language');

$pageTitles = new AllArticlesSqlIterator($language);

$job = new ExtractionJob(
       new DatabaseWikipediaCollection($language),
       //new LiveWikipediaCollection($language),
       $pageTitles);
		
$destination = new NTripleDumpDestination("c:/dbpedia34/en/redirects_".$language.".nt");
$extractor = new RedirectExtractor($language);

$groupInfoboxes = new ExtractionGroup($destination);
$groupInfoboxes->addExtractor($extractor);
$job->addExtractionGroup($groupInfoboxes);

$manager = new ExtractionManager();
$manager->execute($job);

Timer::stop("main::Runtime");
Timer::printTime();



