<?php

/**
 * This file keeps the configuration settings for DBpedia data extraction:
 * - Database setup
 * - DBpedia base URI
 * - Templates names, which are excluded from extraction
 * - image extension (.jpg, ...)
 * - Units to parse (Units, currencies, dates)
 * - predicates which are known to be link lists (key people, products,...) 
 */


 // database configuration
 $host = 'localhost';
 $user = 'root';
 $password = 'softwiki';
// TODO: setting the DB won't work
 $db = "wikiCompany";
 
 
// configure extraction
// $rdftypeProperty='http://www.w3.org/1999/02/22-rdf-syntax-ns#type';
$rdftypeProperty = 'http://dbpedia.org/property/wikiPageUsesTemplate';
$skosSubject = "http://www.w3.org/2004/02/skos/core#subject";


$W2RCFG=array(
	//Searched Template Types, leave empty for all types of Templates
	'templates' => array(),
	//Tags that will stay in Wikipedia-Text, eg: "'<sup>','<span>'"
	'allowedtags' => "'<sup>'" ,
	//Ignored Template types
	'ignoreTemplates' => array('redirect','seealso','main','citation', 'cquote', 'Chess diagram', 'IPA'),
	//Ignored Template types matching wildcard pattern
	'ignoreTemplatesPattern' => array('cite*','assessment*','zh-*','citation','cquote'),
	//Wikipedia Base URI
	'wikipediaBase' => 'http://www4.wiwiss.fu-berlin.de/wikicompany/resource/',
	//Base URI for Properties
	'propertyBase' => 'http://dbpedia.org/property/',
	//Base URI for newly generated instances
	'instanceBase' => 'http://www4.wiwiss.fu-berlin.de/wikicompany/instances/',
	//Base URI for non-specified Datatypes
	'w2ruri' => 'http://dbpedia.org/units/',
	// Property used to link pages to categories
	'categoryProperty' => $skosSubject, #'http://dbpedia.org/category'
	// Property used to link pages to templates
	'templateProperty' => $rdftypeProperty, #'http://dbpedia.org/template'
	//Object for Explicit Typing, Classes
	'classBase' =>'http://www.w3.org/2002/07/owl#Class',
	//Object for datatype Properties
	'datatypePropertyBase' =>'http://www.w3.org/2002/07/owl#DatatypeProperty',
	//Object for object Properties
	'objectPropertyBase' =>'http://www.w3.org/2002/07/owl#ObjectProperty',
	// Property used to link categories to categories
	'subCategoryProperty' =>$rdftypeProperty, #'http://www.w3.org/1999/02/22-rdf-syntax-ns#type'
	// Property used to label pages
	'labelProperty' =>'http://www.w3.org/2000/01/rdf-schema#label',
	//Minimal Count of Pipes | in a found Wikipedia-Template
	'minAttributeCount' => 2,
	// Smaller outputfiles will be deleted
    'minFileSize' => 1000,
    //printed Categories, leave empty for all
    'categories' => array(),
    //printed Categories matching wildcard Pattern, leave empty for all
    'categoriesPattern' => array()
);


// Output format: nt and csv supported
$outputFormat='nt';
// Powl database to load triples into if output format is csv
$powl_db='false';#powl; #'powl_wikipedia';
// id of the model to load the triples into
$modelID='3';
//directory where to generate the output
$outputDir='./extraction_results/';
//filename of complete extraction
$filename='wikipedia.'.$outputFormat;
//extraction will be saved to one file per extraction, or one file per Template
$onefile=true;	
// keyword for categorizing
$categoryLabel='Category'; #'Kategorie';
// keyword for templates
$templateLabel='Template';
// prefixed properties with template name
$prefixPropertiesWithTemplateName=false;
// collect statistics about templates and write as array to templateStatistics.inc.php
$templateStatistics=false;
//write Explicit Type Triples - Extraction will take up to 5 times longer
$addExplicitTypeTriples=false;		
//correct Property Types
$correctPropertyType=false;
//File prefix for splitted type files eg.:types_wikipedia.csv -false for no rdf:type, true for rdf:type in main-output-file
$typefilename=false; #'types_'
//File prefix for splitted label files eg.:label_wikipedia.csv -false for no rdf:label, true for rdf:type in main-output-file
$labelfilename=false; #'labels_'

//Filename Prefixes to be recognized as picture
$pictureFilenames=array('png','jpeg','gif','bmp','svg','jpg');
//recognized Month for Date extraction
$month=array('January'=>'01','February'=>'02','March'=>'03','April'=>'04','May'=>'05','June'=>'06','July'=>'07','August'=>'08',
	'September'=>'09','October'=>'10','November'=>'11','December'=>'12');
//recognized Units
$units=array('g'=>'Gramm','kg'=>'Kilogramm','mm'=>'Millimeter','cm'=>'Centimeter','km'=>'Kilometer','m'=>'Meter','MB'=>'Megabyte','GB'=>'GigaByte', 'K' => 'Kelvin', 'in' => 'Inches', 'ft' => 'feet');
//recognized scales
$scale=array('thousand'=>'1000','million'=>'1000000','billion'=>'1000000000','trillion'=>'1000000000000',
	'quadrillion'=>'1000000000000000');
//Currencies and its Shortcuts
$currency=array('$'=>'Dollar','€'=>'Euro','£'=>'Pound','¥'=>'Yen','US$'=>'Dollar');
//Predicates that contains Linklists
$linklistpredicates=array('competitors','producer','director','writer', 'keyPeople', 'products', 'industry',
'customers', 'partners', 'aff', 'parents', 'sub');


// If this is set true, the Templatename will be added in front of the propertyname
$GLOBALS['prefixPropertiesWithTemplateName'] = false;

