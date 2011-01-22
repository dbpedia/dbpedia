<?php


/**
 * This file triggers the DBpedia data extraction process and defines the
 * interfaces used for extractors etc.
 * 
 * Documentation of the different interfaces and classes is still
 * a draft and far from being complete.
 * 
 * Also see wiki.dbedia.org/Documentation for more Information
 * 
 * * * * * * * * * * * * * * * * * * * *
 * Interface PageCollection:
 *
 * Returns the page sourcecode for a specific language and pagetitle 
 * (Implementations: LiveWikipedia, DatabaseWikipedia)
 * 
 * 
 * * * * * * * * * * * * * * * * * * * *
 * Interface Extractor
 * 
 * Returns an ExtractionResult.
 * Main methods:
 * 
 * start($language):
 * initalizes language
 * 
 * extractPage($pageID, $pageTitle, $pageSource):
 * Constructs a new ExtractionResult, extracts data from the sourcepage and stores
 * the extracted data in the ExtractionResult.
 * 
 * 
 * 
 * * * * * * * * * * * * * * * * * * * * 
 * Class ExtractionResult
 * 
 * Collects RDFtriples while extraction is in process and returns them after extraction.
 * 
 * constructor params:
 * $pageID: String (English!!) page title
 * $language: String language
 * $extractorID: String extractor ID
 * 
 * Most important methods:
 * addTriples() => Adds new Triples
 * getTriples() => Returns an array of RDFtriples.
 * 
 * 
 * 
 * * * * * * * * * * * * * * * * * * * *
 * Interface Destination
 * 
 * Stores extraction results in files or prints them out 
 * 
 * start => initialization (e.g. new file for NTriplesDumpDestination)
 * accept => requires ExtractionResult (and revisionID) reads out each triple
 * and prints it out / writes it on a file
 * 
 * 
 * 
 * * * * * * * * * * * * * * * * * * * * 
 * Interface RDFnode
 * 
 * Takes care of proper RDF representation of data
 * 
 * URI, RDFliteral and RDFblankNode are implementations of RDFnodes.
 * The most important method is toNTriples(), which returns a string contaning
 * the NTriples representation of a RDFnode.
 * In addition informations such as datatye, language, URI and lexical form of
 * literals can be read out from an RDFnode.
 * 
 * 
 * * * * * * * * * * * * * * * * * * * *
 * Class Extraction job
 * 
 * Combines one or more ExtractionGroups (extractors + destination) 
 * with one PageCollection (data source)
 * 
 * 
 * 
 * * * * * * * * * * * * * * * * * * * * 
 * Class ExtractionGroup
 * 
 * Combines one or more extractors with one destination
 * 
 * 
 * 
 * * * * * * * * * * * * * * * * * * * * 
 * Class ExtractionManager
 * 
 * Executes extraction jobs.
 * For each extraction group, the extraction manager first 
 * initializes the extractors and the destination (calls their start() method).
 * 
 * Next, it iterates over all pages from a PageCollection and passes the
 * page source to the extractor, triggering its extractPage() method.
 *
 * Finally it reads out the ExtractionResults from each extractor and passes it to
 * the respective destination. The finish() methods from extractors and destination
 * are called, in order to close these.
 * 
 */


require_once 'dbpedia.php';
require_once 'extraction/extractTemplates.php';
include ("extraction/config.inc.php");
		
function __autoload($class_name) {
    require_once $class_name . '.php';
}

error_reporting(E_ALL | E_NOTICE);

$manager = new ExtractionManager();

		
$pageTitlesEn = new AllArticlesSqlIterator("en");

$jobEnWiki = new ExtractionJob(
        new  LiveWikipedia("en"),
        $pageTitlesEn);


$groupArticlesLabelEn = new ExtractionGroup(new NTripleDumpDestination("articles_label.nt"));
$groupArticlesLabelEn->addExtractor(new LabelExtractor());
$jobEnWiki->addExtractionGroup($groupArticlesLabelEn);

$groupArticlesShortAbstractEn = new ExtractionGroup(new NTripleDumpDestination("articles_abstract.nt"));
$groupArticlesShortAbstractEn->addExtractor(new ShortAbstractExtractor());
$jobEnWiki->addExtractionGroup($groupArticlesShortAbstractEn);

$groupImages = new ExtractionGroup(new NTripleDumpDestination("articles_image.nt"));
$groupImages->addExtractor(new ImageExtractor());
$jobEnWiki->addExtractionGroup($groupImages);

$groupWikipages = new ExtractionGroup(new NTripleDumpDestination("articles_wikipage.nt"));
$groupWikipages->addExtractor(new WikipageExtractor());
$jobEnWiki->addExtractionGroup($groupWikipages);

$groupInfoboxes = new ExtractionGroup(new NTripleDumpDestination("infoboxes.nt"), new NTripleDumpDestination("infoboxes.properties.nt"));
$groupInfoboxes->addExtractor(new InfoboxExtractor());
$jobEnWiki->addExtractionGroup($groupInfoboxes);

$groupSemantic = new ExtractionGroup(new NTripleDumpDestination("semantic.nt"));
$groupSemantic->addExtractor(new SemanticExtractor());
$jobEnWiki->addExtractionGroup($groupSemantic);

$groupDBpedia = new ExtractionGroup(new NTripleDumpDestination("dbpedia_links.nt"));
$groupDBpedia->addExtractor(new DBpediaLinkExtractor());
$jobEnWiki->addExtractionGroup($groupDBpedia);

$groupGeoCodes = new ExtractionGroup(new NTripleDumpDestination("geocodes.nt"));
$groupGeoCodes->addExtractor(new WcGeoExtractor());
$jobEnWiki->addExtractionGroup($groupGeoCodes);

$manager->execute($jobEnWiki);



