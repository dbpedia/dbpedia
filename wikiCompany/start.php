<?php

/**
 * This file manages DBpedia data extraction
 * 
 * 
 * 
 * I. Getting started
 * 
 * To get started just execute the file and the NTriples for the 
 * selected pages (in $pageTitles) and extractors will be print out.
 * 
 * To produce NTriple dump files, just change the destinations for
 * each extraction group to NTripleDumpDestination.
 * Note: NTripleDumpDestination's constructor requires the dumpfile
 * filename as argument.
 * 
 * If you want to test your setup, you can also choose the WebDebug
 * interface. See webStart.php for further information.
 * 
 * Also see wiki.dbedia.org/Documentation for more Information
 * 
 * II. Setting up your own jobs
 * 
 * If you want to setup your own jobs please read the following first.
 * 
 * An ExtractionJob consists of one or more extraction groups.
 * An ExtractionGroup consists of a destination and one or more extractors. 
 * Possible Destinations include your console, NTriple files and a webinterface.
 * Of course you are free to write your own destination (e.g. a database). 
 * 
 * Extractors are designed for specific purposes. E.g. the InfoboxExtractor 
 * reads out information from Wikipedia Infoboxes. The ShortAbstractExtractor
 * gets the first paragraph from an article and so on.
 * 
 * If you want to store extraction results from all extractors in just one
 * file, one extraction group will suit your needs. Just create a new
 * ExtractionGroup with a destination and add the extractors you need. 
 * 
 * If you intend to produce seperate output files for each extractor, you will
 * need an own ExtractionGroup for each extractor (as is done in the standard
 * settings).
 * 
 * Finally you will need to run your ExtractionJob through an instance of an 
 * ExtractionManager. 
 * 
 * 
 * 
 * III. Writing your own extractors
 * 
 * DBpedia delivers extractors for many purposes already (part IV). Still you
 * might intend to write your own extractors for your special needs. Any
 * extractor must implement the interface Extractor.
 * 
 * The most important methods are start($language), which initializes the language
 * and extractPage($pageID, $pageTitle, $pageSource) which implements the actual
 * extraction process. ExtractPage must return an instance of ExtractionResult.
 * 
 * 
 * 
 * IV. Included extractors
 * 
 * - ArticleCategoriesExtractor
 * 	 Extracts the Wikipedia categories for each article.
 * - CharacterCountExtractor
 * 	 Counts the charactes for each article
 * - ChemboxExtractor
 * 	 Not working yet, will extract Wikipedia Chemboxes in the future 
 * - ExteralLinksExtractor
 * 	 Extracts Links from the "External Links" section of a Wikipedia article
 * - ImageExtractor
 * 	 Extracts the first image from an article and sets links to a thumbnail and
 * 	 to the fullsize version of this image
 * - InfoBoxExtractor
 * 	 Extracts the information from Wikipedia Infoboxes
 * - LabelExtractor
 * 	 Extracts the pagelabel of an article
 * - LongAbstractExtractor
 * 	 Extracts the first paragraph of an article and cleans it from MediaWiki-markup
 * - PersondataExtractor
 * 	 Extracts data about persons, e.g. date and place of birth / death.
 * - ShortAbstractExtractor
 * 	 Same as LongAbstractExtractor, cuts the abstract after 500 characters.
 * - SkosCategoriesExtractor
 * 	 Describes Wikipedia categories (skos:subject)
 * - WikipageExtractor
 *   Generates foaf:page links from DBpedia reources to the corresponding
 * 	 Wikipedia article 	 
 * 
 * 
 */


require_once 'extraction/extractTemplates.php';
include ("extraction/config.inc.php");
// Load interfaces
require_once 'dbpedia.php';	
		
function __autoload($class_name) {
    require_once $class_name . '.php';
}

// Enter the resources you want to extract. 
// [To extract all Wikipedia articles from an SQL-Dump use AllArticlesSqlIterator (see extract.php)]	
// when using a MySqlIterator, don't use "new ArrayObject" at Job creation	
$pageTitles = array("Michael_Foot","Millard_Fillmore");  //, "Michael_Jordan", "Google");

// Setup the language version of Wikipedia
$language = "en";

// Instantiate a new ExtractionJob
$job = new ExtractionJob(
       new LiveWikipedia($language),
       new ArrayObject($pageTitles));
		
		
// Create ExtractionGroups for each Extractors
$groupInfoboxes = new ExtractionGroup(new SimpleDumpDestination());
$groupInfoboxes->addExtractor(new InfoboxExtractor());
$groupImages = new ExtractionGroup(new SimpleDumpDestination());
$groupImages->addExtractor(new ImageExtractor());
$groupShortAbstracts = new ExtractionGroup(new SimpleDumpDestination());
$groupShortAbstracts->addExtractor(new ShortAbstractExtractor());
$groupLabels = new ExtractionGroup(new SimpleDumpDestination());
$groupLabels->addExtractor(new LabelExtractor());


// Add the ExtractionGroups to the ExtractionJob 
$job->addExtractionGroup($groupInfoboxes);
$job->addExtractionGroup($groupImages);
$job->addExtractionGroup($groupShortAbstracts);
$job->addExtractionGroup($groupLabels);


// Execute the ExtractionJob
$manager = new ExtractionManager();
$manager->execute($job);



