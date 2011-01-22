<?php

/**
 * This file must be included, in order to import the DBpedia
 * extraction framework (imports every interface).
 *
 *
 */

// autoloader
function __autoload($class_name) {
   if(preg_match('~^.*ExtractorTest.*$~',$class_name)) require_once ('test/tests/ExtractorTests/'.$class_name.'.php');
   else
   if(preg_match('~^.*Extractor.*$~',$class_name)) require_once ('extractors/'.$class_name.'.php');
   else
   if(preg_match('~^.*Destination.*$~',$class_name)) require_once ('destinations/'.$class_name.'.php');
   else
   if(preg_match('~^.*Collection.*$~',$class_name)) require_once ('collections/'.$class_name.'.php');
   else
   if(preg_match('~^.*Iterator.*$~',$class_name)) require_once ('iterators/'.$class_name.'.php');
   else
   if(preg_match('~^.*Resource.*$~',$class_name)) require_once ('collections/'.$class_name.'.php');
   else
   if(preg_match('~^.*ParserTest.*$~',$class_name)) require_once ('test/tests/ParserTests/'.$class_name.'.php');
   else
   if(preg_match('~^.*Parser.*$~',$class_name)) require_once ('parsers/'.$class_name.'.php');
   else
   if(preg_match('~^.*Test.*$~',$class_name)) require_once ('test/tests/OtherTests/'.$class_name.'.php');
   else
   if(preg_match('~^.*ParseAttribute.*$~',$class_name)) require_once ('extractors/infobox/'.$class_name.'.php');
   else require_once 'core/'.$class_name . '.php';
}

// mb_strtoupper() and mb_strtolower() seem to need UTF-8.
// TODO: we should use mb_strtoupper() instead of strtoupper()
// and mb_strtolower() instead of strtolower() EVERYWHERE.
mb_internal_encoding('UTF-8');


/*
 * Command line args
 * **/


$usage ='
* USAGE:
* even number of parameters required
* either no options or
';

$unaryAllowed = array(
	'-dry'=>"set dry run, i.e. don't execute queries, but print them",
	'-noglob'=>"deactivate article queue, needs a long time to count, when full",
	'-showconfig'=>"shows the configuration and waits 5 seconds"

);
$unaryGiven = array();
$binaryAllowed = array(
	'-c'=> "config/dbpedia.ini  for custom ini file",
	'-id'=> " 1234 to set the process id ",
	'-clear'=> " VALUE  whereas VALUE can be: \n  'hashtable' to delete table dbpedia_hash",
	'-strategy'=>" primary : set strategy to 'primary' or 'secondary'",
	'-logdest' => " stderr : set log to stderr ",
	'-debug'=> " l1ti:  set debug options:\n  l fordebug_loop, 1 for debug_die_after_one, t for debug_run_tests, i for debug_turn_off_insert"
	);
$binaryGiven = array();
$clearhashtable = false;
$defaultini = 'config/dbpedia_default.ini';
$customini = 'config/dbpedia.ini';
$helparray = array('-help', '-?', 'help', '-h');

//transform
if(!empty($unaryAllowed)){
	$usage .= "unary options***********\n";
	foreach($unaryAllowed as $key => $value){
		$usage .="* $key : $value \n";
		}
	$unaryAllowed = array_keys($unaryAllowed);
}
if(!empty($binaryAllowed)){
	$usage .= "binary options***********\n";
	foreach($binaryAllowed as $key => $value){
		$usage .="* $key $value \n";
		}
	$binaryAllowed = array_keys($binaryAllowed);
}


for($x = 1; $x < count($argv);$x++){
		$current = trim(strtolower($argv[$x]));
		if(in_array($current, $helparray)){
			die($usage);
		}else if(in_array($current, $unaryAllowed)){
			$unaryGiven[] = $current;
		}else if(in_array($current, $binaryAllowed)) {
			//special handling of -c
			if($current == '-c'){
				$customini = $current;
                $x++;
			}else if(isset($argv[$x+1])){
				$binaryGiven[$current] = trim(strtolower($argv[++$x]));
			}else{
				die	("binary option needs second parameter:  $current ".$usage);
				}
		}else {
			//die	("unknown option $current ".$usage);
			file_put_contents('php://stderr', "Warning: Ignoring unknown option '$current'\n");
		}

	}

/*
INIT LOGGING
*/
Logger::configureLogger('config/logger_default.ini');
if(file_exists('config/logger.ini')) {
	Logger::configureLogger('config/logger.ini');
}
/*
 * Override default ini
 * */
Options::configureOptions($defaultini);
if(file_exists($customini)) {
	Options::configureOptions($customini);
}
/*
 * Unary options
 * */
if(!empty($unaryGiven)){
	foreach ($unaryGiven as $opt){
		switch ($opt){
			case '-dry' : {
				Options::setOption('dryRun', true);
				break;
				}
			case '-noglob' : {
				Options::setOption('noglob', true);
				break;
				}
			case '-showconfig' : {
				Options::setOption('showconfig', true);
				break;
				}
			default:{
				die('unknown option: '+$opt.$usage);
				}

		}
	}
}
/*
 * Binary options
 * */
if(!empty($binaryGiven)){
	foreach ($binaryGiven as $opt=>$val){

		switch ($opt){
			case '-id' : {
				Options::setProcessID($val);
				break;
				}
			case '-debug' : {
				if($val[0] == '-'){die('- encountered should be -debug l1ti'.$usage);}
				else{
						Options::setOption('debug_loop', false !== strpos($val, 'l'));
						Options::setOption('debug_die_after_one', false !==  strpos($val, '1'));
						Options::setOption('debug_run_tests',false !==  strpos($val, 't'));
						Options::setOption('debug_turn_off_insert', false !==  strpos($val, 'i'));
					}
				break;
				}
			case '-strategy' : {
				if($val == 'primary' or $val =='secondary' ){
					Options::setOption('LiveUpdateDestination.strategy', $val);
					Options::setOption('LiveUpdateDestination.useHashForOptimization', false);
				}
				else{
					die('either primary or secondary'.$usage);
					}
				break;
				}
			case '-logdest' : {
                    Logger::setDestination($val);
				break;
				}
			case '-clear' : {
				if($val == 'hashtable'){
					$clearhashtable = true;
					Logger::info('deleting hashtable');
					}
				break;
				}
		default:{
				die('unknown option: '+$opt.$usage);
				}
		}//switch
	}//for
}//if

date_default_timezone_set(Options::getOption('timezone'));
Timer::init();

/*
 * SEMAPHORE IDS
 *
 * */
define("OAIRECORDFILES", '4444');
define("STRINGIDFILE", '55555');


/*
 * A bunch of fixed strings
 * */

define("ACTIVE", 'active');
define("KEEP", 'keep');
define("PURGE", 'purge');

define("ARTICLE", 'article');
define("CATEGORY", 'category');
define("REDIRECT", 'redirect');

define("EXTRACTOR", 'Extractor');
define("STARTSWITH", 'startswith');
define("EXACT", 'exactmatch');
define("IFEXISTSDONOTDELETE", 'ifexistsdonotdelete');
define("POSTPROCESSING", 'postprocessing');
define("PRODUCES", 'produces');
define("EXTRACTORID", 'extractorID');
define("NOTICE", 'notice');
define("IGNOREVALIDATION", 'ignorevalidation');

define("STATUS", 'status');
define("OWLAXIOMDEFAULT", 'owlaxiomdefault');

// Parser constants
define("PAGEID", 'pageID');
define("PROPERTYNAME", 'propertyName');
define("UNITTYPE", 'unitType');
define("UNITEXACTTYPE", 'unitExactType');
define("TARGETUNIT", 'targetUnit');
define("IGNOREUNIT", 'ignoreUnit');
define("USE_PERCENT_ENCODING", Options::getOption('language.use_percent_encoding'));

/**
 * COMPONENTS
 */
define("CMP_CORE", 'core');
define("CMP_DESTINATION", 'destination');
define("CMP_ITERATOR", 'iterator');
define("CMP_EXTRACTOR", 'extractor');
/*
define("CMP_EXTRACTOR", 'iterator');
*/

/*
 * WIKIMEDIA NAMESPACES
 * */
define("MW_CATEGORY_NAMESPACE", 'Category');
define("MW_FILE_NAMESPACE", 'File');
define("MW_FILEALTERNATIVE_NAMESPACE", 'FileAlt');
define("MW_TEMPLATE_NAMESPACE", 'Template');



/*
 * Namespaces
 * */
//define("EXTRACTORNS", 'http://dbpedia.org/extractors/');
define("DBPEDIA_NS", Options::getOption('dbpedia_ns'));
define("DB_META_NS", Options::getOption('db_meta_ns'));
define("DB_RESOURCE_NS", DBPEDIA_NS.'resource/');
define("DB_PROPERTY_NS", DBPEDIA_NS.'property/');
define("DB_ONTOLOGY_NS", DBPEDIA_NS.'ontology/');
define("DB_COMMUNITY_NS", DBPEDIA_NS.'ontology/');
define("DB_YAGO_NS",  DBPEDIA_NS.'class/yago/');

define("GEONAMES_NS", 'http://www.geonames.org/ontology#');
define("UMBEL_NS", 'http://umbel.org/umbel/');
define("OPENCYC_NS", 'http://sw.opencyc.org/');
//define("DB_CATEGORY_NS", DB_RESOURCE_NS.WIKIMEDIA_CATEGORY.':');
define("DBM_TEMPLATE_NS", DB_META_NS . 'Template:');
define("DB_TEMPLATE_NS", DB_RESOURCE_NS . 'Template:');

define("VIRTUOSO", 'virtuoso');

/*
 GENERAL Vocabulary
*/
define("RDF_TYPE", 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type');
define("RDF_PROPERTY", 'http://www.w3.org/1999/02/22-rdf-syntax-ns#Property');

define("RDFS_LABEL", 'http://www.w3.org/2000/01/rdf-schema#label');
define("RDFS_COMMENT", 'http://www.w3.org/2000/01/rdf-schema#comment');

define("OWL_SAMEAS", 'http://www.w3.org/2002/07/owl#sameAs');
define("OWL_THING", 'http://www.w3.org/2002/07/owl#Thing');

define("DC_MODIFIED", 'http://purl.org/dc/terms/modified');
define("DC_DESCRIPTION", 'http://purl.org/dc/elements/1.1/description');
define("DC_RIGHTS", 'http://purl.org/dc/terms/rights');

define("FOAF_PAGE", 'http://xmlns.com/foaf/0.1/page');
define("FOAF_NAME", 'http://xmlns.com/foaf/0.1/name');
define("FOAF_GIVENNAME", 'http://xmlns.com/foaf/0.1/givenname');
define("FOAF_SURNAME", 'http://xmlns.com/foaf/0.1/surname');
define("FOAF_PERSON", 'http://xmlns.com/foaf/0.1/Person');
define("FOAF_DEPICTION", 'http://xmlns.com/foaf/0.1/depiction');
define("FOAF_THUMBNAIL", 'http://xmlns.com/foaf/0.1/thumbnail');
define("FOAF_IMG", 'http://xmlns.com/foaf/0.1/img');
define("FOAF_HOMEPAGE", 'http://xmlns.com/foaf/0.1/homepage');

define("SKOS_SUBJECT", 'http://www.w3.org/2004/02/skos/core#subject');
define("SKOS_PREFLABEL", 'http://www.w3.org/2004/02/skos/core#prefLabel');
define("SKOS_CONCEPT", 'http://www.w3.org/2004/02/skos/core#Concept');
define("SKOS_BROADER", 'http://www.w3.org/2004/02/skos/core#broader');

define("GEORSS_POINT" , 'http://www.georss.org/georss/point');
define("GEORSS_RADIUS" , 'http://www.georss.org/georss/radius');

define("WGS_LAT" , 'http://www.w3.org/2003/01/geo/wgs84_pos#lat');
define("WGS_LONG" , 'http://www.w3.org/2003/01/geo/wgs84_pos#long');

define("GEO_FEATURECLASS" , 'http://www.geonames.org/ontology#featureClass');
define("GEO_FEATURECODE" , 'http://www.geonames.org/ontology#featureCode');
define("GEO_POPULATION" , 'http://www.geonames.org/ontology#population');

//should not use macro for now
define("YAGO_LANDMARK" , DB_YAGO_NS.'Landmark108624891');


/*
 * These seem to be defined already
*/
define("XS_DATETIME", 'http://www.w3.org/2001/XMLSchema#dateTime');
define("XS_DATE", 'http://www.w3.org/2001/XMLSchema#date');
define("XS_FLOAT", 'http://www.w3.org/2001/XMLSchema#float');
define("XS_INTEGER", 'http://www.w3.org/2001/XMLSchema#integer');
define("XS_DECIMAL", 'http://www.w3.org/2001/XMLSchema#decimal');

/*
 * DBpedia Vocabulary
 * */
define("DB_REDIRECT", DB_PROPERTY_NS.'redirect');
define("DB_ABSTRACT", DB_ONTOLOGY_NS.'abstract');
define("DB_DISAMBIGUATES", DB_PROPERTY_NS.'disambiguates');
define("DB_WIKILINK", DB_PROPERTY_NS.'wikilink');
define("DB_WORDNET_TYPE", DB_PROPERTY_NS.'wordnet_type');
define("DB_CHARACTERCOUNT", DB_PROPERTY_NS.'characterCount');
define("DB_HASPHOTOCOLLECTION", DB_PROPERTY_NS.'hasPhotoCollection');
define("DB_MY_CHEM_PROPERTY", DB_PROPERTY_NS.'my_chem_property');
define("DB_REFERENCE", DB_PROPERTY_NS.'reference');
define("DB_WIKIPAGEUSESTEMPLATE", DB_PROPERTY_NS.'wikiPageUsesTemplate');
define("DB_BIRTH", DB_PROPERTY_NS.'birth');
define("DB_BIRTHPLACE", DB_PROPERTY_NS.'birthPlace');
define("DB_DEATH", DB_PROPERTY_NS.'death');
define("DB_DEATHPLACE", DB_PROPERTY_NS.'deathPlace');
define("DB_CLASS_BOOK", DBPEDIA_NS.'class/Book');
define("DB_WIKIPAGE_EN", DB_PROPERTY_NS.'wikipage-en');

define("DBCOMM_ABSTRACT", DB_PROPERTY_NS.'abstract_live');
define("DBCOMM_COMMENT", DB_PROPERTY_NS.'comment_live');

/*
 * Ontology Vocabulary
 * */
define("DBO_INDIVIDUALISED_PND", DB_ONTOLOGY_NS.'Person/individualisedPnd');
define("DBO_NON_INDIVIDUALISED_PND", DB_ONTOLOGY_NS.'Person/nonIndividualisedPnd');
define("DBO_THUMBNAIL", DB_ONTOLOGY_NS.'thumbnail');
/*
 * ANNOTATION VOCABULARY:
 *
 * */

define("AXIOM_PREFIX", DB_META_NS.'axiom');
define("OWL_AXIOM", 'http://www.w3.org/2002/07/owl#Axiom');
define("OWL_SUBJECT", 'http://www.w3.org/2002/07/owl#annotatedSource');
define("OWL_PREDICATE", 'http://www.w3.org/2002/07/owl#annotatedProperty');
define("OWL_OBJECT", 'http://www.w3.org/2002/07/owl#annotatedTarget');



/*
 * Meta Vocabulary
 * */
define("DBM_EXTRACTEDFROMTEMPLATE", DB_META_NS.'extractedfromtemplate');
define("DBM_ONDELETECASCADE", DB_META_NS.'sourcepage');
define("DBM_ORIGIN", DB_META_NS.'origin');
define("DBM_SOURCEPAGE", DB_META_NS.'sourcepage');
define("DBM_REVISION", DB_META_NS.'revision');
//was oaiidentifier
define("DBM_OAIIDENTIFIER", DB_META_NS.'pageid');
define("DBM_EDITLINK", DB_META_NS.'editlink');



/*
For Statistics
*/
define("CREATEDTRIPLES", 'created_Triples');
define("STAT_TOTAL", 'Total');

define("ERROR", 'error');
define("WARN", 'warn');
define("INFO", 'info');
define("DEBUG", 'debug');
define("TRACE", 'trace');

/*
 * For Util::isRedirect and Util::isDisambiguation
 * creates the $MEDIAWIKI_REDIRECTS  &  $MEDIAWIKI_DISAMBIGUATIONS array
 */
require_once('core/language_disambigs.php');
require_once('core/language_redirects.php');
require_once('core/language_namespaces.php');

if(false === function_exists('lcfirst')) {
    /**
     * Make a string's first character lowercase
     *
     * @param string $str
     * @return string the resulting string.
     */
    function lcfirst( $str ) {
        $str[0] = strtolower($str[0]);
        return (string)$str;
    }
}
