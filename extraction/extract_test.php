<?php

/**
 * Start file for testing an extractor on a single article using
 * the LiveWikipedia. The file outputs the generated triples 
 * directly. This is the best way for developers to verify that
 * their extractors are working. Once the extractor is working on
 * an article, developers should use extract_dataset to produce a
 * full data set and see whether it works in a full extraction.
 *
 * See http://wiki.dbpedia.org/Documentation for an overview of
 * the DBpedia extraction framework.
 *
 * @author Jens Lehmann
 */

include('dbpedia.php');

// configure settings
// change the Extractor class to your extractor
//this should be done in config/dbpedia.ini
Options::setLanguage('ko');
$language = Options::getOption('language');


//$extractor = new ActiveAbstractExtractor();
$extractor = new KoInfoboxExtractor();
/*
$extractor = new InfoboxExtractor();
*/
//$extractor = new SkosCategoriesExtractor();

//these are articles for testing
//$article[] = 'London';

//$article[] = 'Category:Pasta';
$t = '이탈리아';
$t = '서울특별시';
/*
$t = 'Berlin';
*/

$article[] = $t;


//normally this should be done in config/dbpedia.ini
$extractor->setGenerateOWLAxiomAnnotations(false);

//logging
Logger::info('extractor: '.$extractor->getExtractorID());

// sets up and runs job (usually you do not need to change anything below)
$pageTitles = new ArrayObject($article);
$job = new ExtractionJob(new LiveWikipediaCollection($language),$pageTitles);
/*
$group = new ExtractionGroup(new SimpleDumpDestination());
*/
$group = new ExtractionGroup(new SimpleDecodeDestination());
$group->addExtractor($extractor);
$job->addExtractionGroup($group);
$manager = new ExtractionManager();
$manager->execute($job);

