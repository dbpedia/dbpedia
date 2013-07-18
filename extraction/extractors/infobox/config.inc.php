<?php

/**
 * This file keeps the configuration settings for DBpedia data extraction:
 * - DBpedia base URI
 * - Templates names, which are excluded from extraction
 * - image extension (.jpg, ...)
 * - Units to parse (Units, currencies, dates)
 * - predicates which are known to be link lists (key people, products,...)
 */


 // database configuration
 #$host = 'www4.wiwiss.fu-berlin.de';
 #$user = 'piet';
 #$password = '';
 #$db = "dbpedia_en";

// configure extraction
// $rdftypeProperty='http://www.w3.org/1999/02/22-rdf-syntax-ns#type';
$rdftypeProperty = DB_PROPERTY_NS.'wikiPageUsesTemplate';
$skosSubject = "http://www.w3.org/2004/02/skos/core#subject";


$GLOBALS['W2RCFG']=array(
	//Searched Template Types, leave empty for all types of Templates
	'templates' => array(),
	//Tags that will stay in Wikipedia-Text, eg: "'<sup>','<span>'"
	// new lines are handled separately: in literals they are replaced by \n, in resources they are removed
	'allowedtags' => "'<sup>','<br/>','<br>','<br />'" ,
	//Ignored Template types
	'ignoreTemplates' => array('redirect','seealso','main','citation', 'cquote', 'Chess diagram', 'IPA', 'lang'),
	//Ignored Template types matching wildcard pattern
	'ignoreTemplatesPattern' => array('cite*','assessment*','zh-*','citation','cquote', 'llang*'),
	// ignored properties (image is ignored because it is already extracted by the image extractor)
	'ignoreProperties' => array('image'),
	//Wikipedia Base URI
	'wikipediaBase' => DB_RESOURCE_NS,
	//Base URI for Properties
	'propertyBase' => DB_PROPERTY_NS,
	//Base URI for newly generated instances
	'instanceBase' => DBPEDIA_NS.'instances/',
	//Base URI for non-specified Datatypes
	'w2ruri' => DBPEDIA_NS.'units/',
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
    'categoriesPattern' => array(),
    // maximum property length; if a property is (usually be problematic Wiki code) longer than this number
	// of characters, it is automatically ignored
	'maximumPropertyLength' => 250
);



//////////////////////////////////////////
//
//	Begin legacy code.
//	Should be excluded in the future.
//	Though it has to be tested first, if
//	these variables really are superfluous
//
//////////////////////////////////////////

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

//////////////////////////////////////////
//
// END legacy code
//
//////////////////////////////////////////

// keyword for templates
$GLOBALS['templateLabel']='Template';
//Filename Prefixes to be recognized as picture
$GLOBALS['pictureFilenames']=array('png','jpeg','gif','bmp','svg','jpg');
//recognized Month for Date extraction
$GLOBALS['month']=array('January'=>'01','February'=>'02','March'=>'03','April'=>'04','May'=>'05','June'=>'06','July'=>'07','August'=>'08',
	'September'=>'09','October'=>'10','November'=>'11','December'=>'12');
//recognized Units
$GLOBALS['units']=array('g'=>'Gramm','kg'=>'Kilogramm','mm'=>'Millimeter','cm'=>'Centimeter','km'=>'Kilometer','m'=>'Meter','MB'=>'Megabyte','GB'=>'GigaByte', 'K' => 'Kelvin', 'in' => 'Inches', 'ft' => 'feet');
//recognized scales
$GLOBALS['scale']=array('thousand'=>'1000','million'=>'1000000','billion'=>'1000000000','trillion'=>'1000000000000',
	'quadrillion'=>'1000000000000000');
//Currencies and its Shortcuts
$GLOBALS['currency']=array('$'=>'Dollar','€'=>'Euro','£'=>'Pound','¥'=>'Yen','US$'=>'Dollar','₩'=>'KRW');
//Predicates that contains Linklists
$GLOBALS['linklistpredicates']=array('starring','producer','director','writer', 'keyPeople', 'products', 'industry');

// If this is set true, the Templatename will be added in front of the propertyname
$GLOBALS['prefixPropertiesWithTemplateName'] = false;
