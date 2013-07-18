<?php

/**
 * This file starts the DBpedia extraction process for abstracts.
 *
 * Warning: The script needs several days to complete on an average PC.
 */

error_reporting(E_ALL);

// automatically loads required classes
require('dbpedia.php');

// set $extractionDir and $extractionLanguages
require('extractionconfig.php');

$manager = new ExtractionManager();

// loop over all languages
foreach($extractionLanguages as $currLanguage) {

	Options::setLanguage($currLanguage);

	$pageTitles = new ArticlesSqlIterator($currLanguage);
	$job = new ExtractionJob(new DatabaseWikipediaCollection($currLanguage), $pageTitles);
	
	$extractionDirLang = $extractionDir.'/'.$currLanguage.'/';
	if(!is_dir($extractionDirLang))
		mkdir($extractionDirLang);
	// AbstractExtractor has references to its two destinations, see below
	$group = new ExtractionGroup(new NullDestination());
			
	$shortDestination = new csvNTripleDestination($extractionDirLang."shortabstract_".$currLanguage);
	$longDestination = new csvNTripleDestination($extractionDirLang."longabstract_".$currLanguage);
	$extractorInstance = new AbstractExtractor();
	$extractorInstance->setDestinations($shortDestination, $longDestination);
	$group->addExtractor($extractorInstance);
	$job->addExtractionGroup($group);
	
	$date = date(DATE_RFC822);
	Logger::info("Starting abstract extraction job for language $currLanguage at $date\n");
	$manager->execute($job);
	$date = date(DATE_RFC822);
	Logger::info("Finished abstract extraction job for language $currLanguage at $date\n");
}
