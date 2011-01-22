<?php

/**
 * This file triggers the DBpedia extraction process for most extractors
 * and most languages.
 * Abstracts are not included since they take too long. See extract_abstracts.php
 *
 * Warning: The script needs several days to complete on an average PC.
 */

// TODO FIXME: fix warnings! show notices!
error_reporting(E_ALL & ~ E_NOTICE & ~ E_WARNING & ~ E_DEPRECATED);

// automatically loads required classes
require('dbpedia.php');

// set $extractionDir and $extractionLanguages
require('extractionconfig.php');

$localExtractors = array();

// configure which extractors have to be executed on which Wikipedia language editions
// structure: language + article iterator + extractor
foreach ($extractionLanguages as $language)
{
	$localExtractors[$language] = array('articles' => array('Label','Wikipage','Infobox','PageLinks','Geo'));
}

// en, de and fr are special
if (isset($localExtractors['en'])) {
	$localExtractors['en'] = array(
		'articles' => array('Label','Image','Wikipage','ArticleCategories','InstanceType',
						'ExternalLinks','Infobox','Homepage','Geo','PageLinks','Disambiguation',
						/*'WordnetLink',*/'Persondata', 'NewStrictMappingBased', 'OldLenientMappingBased'),
		'categories' => array('Label','SkosCategories'),
		'redirects' => array('Redirect')
	);
}

if (isset($localExtractors['de'])) {
	$localExtractors['de'] = array(
		'articles' => array('Label','Wikipage','Infobox','PageLinks',
						'Homepage','Persondata','Geo')
	);
}

if (isset($localExtractors['fr'])) {
	$localExtractors['fr'] = array(
		'articles' => array('Label','Wikipage','Infobox','PageLinks','Homepage','Geo')
	);
}

// which language and extractor number of the above array to start with (useful for 
// resuming the extraction at some defined point in case problems occur)
$startLanguageNr = 0;
$startIteratorNr = 0;
$startExtractorNr = 0;

$manager = new ExtractionManager();
// loop over all languages
for($languageNr=$startLanguageNr; $languageNr<count($localExtractors); $languageNr++) {

	$keys = array_keys($localExtractors);
	$currLanguage = $keys[$languageNr];
	Options::setLanguage($currLanguage);

	// loop over all iterators
	for($iteratorNr=$startIteratorNr; $iteratorNr<count($localExtractors[$currLanguage]); $iteratorNr++) {

		// create correct iterator instance and set up extraction job
		$keys = array_keys($localExtractors[$currLanguage]);
		$iterator = $keys[$iteratorNr];
		if($iterator == 'articles')
			$pageTitles = new ArticlesSqlIterator($currLanguage);
		else if($iterator == 'categories')
			$pageTitles = new CategoriesSqlIterator($currLanguage);
		else if($iterator == 'redirects')
			$pageTitles = new AllArticlesSqlIterator($currLanguage);
		else
			die('Unknown iterator ' . $iterator . '.');
		$job = new ExtractionJob(new DatabaseWikipediaCollection($currLanguage), $pageTitles);
	
		$extractionDirLang = $extractionDir.'/'.$currLanguage.'/';
		if(!is_dir($extractionDirLang))
			mkdir($extractionDirLang);

		// loop over all extractors
		for($extractorNr=$startExtractorNr; $extractorNr<count($localExtractors[$currLanguage][$iterator]); $extractorNr++) {
	
			// create extraction group and add destination
			$extractor = $localExtractors[$currLanguage][$iterator][$extractorNr];
			$filename = strtolower($extractor).'_'.$currLanguage;
			// when extracting labels we prepend the iterator name (because labels are extracted with different iterators)
			if($extractor == 'Label')
				$filename = $iterator.'_'.$filename;

			// infoboxes are a special case, because two data sets are extracted from them
			if($extractor == 'Infobox') {
				$group = new ExtractionGroup(
					new csvNTripleDestination($extractionDirLang.$filename),
					new csvNTripleDestination($extractionDirLang.'infoboxproperties_'.$currLanguage));
			} else {
				$group = new ExtractionGroup(new csvNTripleDestination($extractionDirLang.$filename));
			}
			
			// create an instance of the extractor using the PHP reflection API
			$extractorClassName = $extractor.'Extractor';
			echo $extractorClassName, PHP_EOL;
			$extractorClass = new ReflectionClass($extractorClassName);
			$extractorInstance = $extractorClass->newInstance();
			$group->addExtractor($extractorInstance);
			$job->addExtractionGroup($group);
	
		}
	
		$date = date(DATE_RFC822);
		Logger::info("Starting extraction job for language $currLanguage and iterator $iterator at $date\n");
		$manager->execute($job);
		$date = date(DATE_RFC822);
		Logger::info("Finished extraction job for language $currLanguage and iterator $iterator at $date\n");

	}
}
