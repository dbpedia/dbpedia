<?PHP

/**
 * This file manages DBpedia data extraction and presentation through
 * a web interface. It is not intended for large extraction jobs. See
 * start.php how to setup advanced extraction jobs for producing your
 * own DBpedia dumps.
 * 
 * 
 * 
 * I. Getting started
 * 
 * First you need to download RAP - Rdf API for PHP from sourceforge
 * (http://sourceforge.net/projects/rdfapi-php/) and include these files
 * properly (set the constant RDFAPI_INCLUDE_DIR at the top of this 
 * files sourceode).
 * 
 * Now just put this file into your webservers docs directory and load
 * this page in your Browser. If everything works fine you should see a
 * small welcome message and HTML-form.
 * 
 * 
 * Just enter any Wikipedia article name into the resource input field
 * and hit the search button. After a few seconds some HTML-tables should
 * appear containing thex extracted data.
 * 
 * You will get some MySQL-warnings. To get rid of these, if you have the
 * Wikipedia MySQL-dumps installed locally, adapt the database settings in
 * extraction/config.inc.php. Otherwise you can set the 
 * 
 * 
 * 
 * 
 * II. Adding new extractors
 *  
 * If you want to set up your own jobs please read section II. in start.php.
 * 
 * If you just want to add new extractors, just run
 * $group->addExtractor(new xxxExtractor()); 
 * 
 * The additional extraction results should appear afterwards on the
 * web interface.
 * 
 * The ExtractionJob is executed through an instance of ExtractionManager. 
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
 * IV. Included extractors
 * 
 * - ArticleCategoriesExtractor
 *   Extracts the Wikipedia categories for each article.
 * - CharacterCountExtractor
 *   Counts the charactes for each article
 * - ChemboxExtractor
 *   Not working yet, will extract Wikipedia Chemboxes in the future 
 * - ExteralLinksExtractor
 *   Extracts Links from the "External Links" section of a Wikipedia article
 * - ImageExtractor
 *   Extracts the first image from an article and sets links to a thumbnail and
 *   to the fullsize version of this image
 * - InfoBoxExtractor
 *   Extracts the information from Wikipedia Infoboxes
 * - LabelExtractor
 *   Extracts the pagelabel of an article
 * - AbstractExtractor
 *   Extracts the introduction of an article, cleans it up, and saves 
 *   a long and a short version of it.
 * - PersondataExtractor
 *   Extracts data about persons, e.g. date and place of birth / death.
 * - SkosCategoriesExtractor
 *   Describes Wikipedia categories (skos:subject)
 * - WikipageExtractor
 *   Generates foaf:page links from DBpedia reources to the corresponding
 *   Wikipedia article
 * 
 * 
 */

require_once 'dbpedia.php';

// Include your copy of RAP - Rdf API for Php here
define(RDFAPI_INCLUDE_DIR, "../../rdfapi-php/api/"); 
include(RDFAPI_INCLUDE_DIR . "RdfAPI.php");

// Change error reporting level
error_reporting(4);


// Begin of HTML rendering
echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01//EN\">
	 <html lang=\"en\">
	 <head>
		<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">
		<title>DBPedia Debug Interface";
		
if ( isset($_GET["resource"]) )
	echo ": ".$_GET["resource"];

echo "
		</title>
		<meta name=\"generator\" content=\"TextMate http://macromates.com/\">
		<meta name=\"author\" content=\"Piet Hensel\">
		<style type=\"text/css\" media=\"screen\">
			body { 
				font-size:12px; 
				font-family:Arial, Helvetica, sans-serif;
				}
			table {
				padding: 0px;
				}
		</style>
	</head>
	<body>
		<h2> DBPedia Debug Interface </h2>
		<p> This interface is designed to test new or improved extractors for DBpedia. </p>
		<p>Enter a DBpedia resource:</p>
";



$sent = $_GET["sent"];

// This variable holds the entered Wikipedia article name
$resource = $_GET["resource"];
// Set language
if ( isset($_GET["lang"]) ) $lang = $_GET["lang"];
else $lang = "en";

// HTML-rendering of input form
echo "
		<form name=\"getResource\" action=\"webStart.php\" method=\"GET\">
		Resource: <input type=\"text\" name=\"resource\" value=\"$resource\" /><br />
		Language: <input type=\"text\" name=\"lang\" value=\"$lang\" /> <br />
		<input type=\"submit\" name=\"sent\" value=\"Search
		\" />
	  </form>";




///////////////////////////////////////////
//
// Start of DBpedia extraction process
//
///////////////////////////////////////////

if ( isset($sent) ) {
	
	require_once 'extraction/extractTemplates.php';
	
	function __autoload($class_name) {
	    require_once $class_name . '.php';
	}


// Resource to extract
$pageTitle = array($resource);

// Instantiate a new ExtractionJob
$job = new ExtractionJob(
       new LiveWikipedia($lang),
       new ArrayObject($pageTitle));
		
		
// Create ExtractionGroups for each Extractors
$destination = new WebDebugDestination();
$group = new ExtractionGroup($destination);
$group->addExtractor(new InfoboxExtractor());
$group->addExtractor(new ImageExtractor());
// TODO: does AbstractExtractor work?
$group->addExtractor(new AbstractExtractor($destination, $destination));
$group->addExtractor(new LabelExtractor());


// Add the ExtractionGroups to the ExtractionJob 
$job->addExtractionGroup($group);


// Execute the ExtractionJob
$manager = new ExtractionManager();
$manager->execute($job);

}



// Close HTML
echo "</body>\n</html>";

