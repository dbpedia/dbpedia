<!-- WebDebug Interface, needs RAP - Rdf API for PHP to work -->

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN">

<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title>DBPedia Debug Interface
		<?PHP if ( isset($_GET["resource"]) ) echo ": ".$_GET["resource"]; ?></title>
	<meta name="generator" content="TextMate http://macromates.com/">
	<meta name="author" content="Piet Hensel">
	<!-- Date: 2007-07-20 -->
	
<style type="text/css" media="screen">
/* <![CDATA[ */
	
	body { 
			font-size:12px; 
			font-family:Arial, Helvetica, sans-serif;
		}
	table {
			padding: 0px;
	}
	
/* ]]> */
</style>
</head>
<body>

<h2> DBPedia Debug Interface </h2>

<p>
This interface demonstrates the latest <a href="http://dbpedia.org/">DBPedia</a> data extraction algorithms.
It is only intended for debugging purposes. Remaning bugs can be reported at 
<a href = "http://sourceforge.net/projects/dbpedia/">http://sourceforge.net/projects/dbpedia/</a>
.
<br /><span style="font-weight:bold; color:red;">Notice:</span> Internal DBpedia Links point to the official DBpedia version.
Bugs already fixed here might still be present in the last release version. 
</p>

<p>Enter a DBPedia resource:</p>

<?PHP
error_reporting(4);

$sent = $_GET["sent"];
$resource = $_GET["resource"];
if ( isset($_GET["lang"]) ) $lang = $_GET["lang"];
else $lang = "en";

echo "<form name=\"getResource\" action=\"index.php\" method=\"GET\">
		Resource: <input type=\"text\" name=\"resource\" value=\"$resource\" /><br />
		Language: <input type=\"text\" name=\"lang\" value=\"$lang\" /> <br />
		<input type=\"submit\" name=\"sent\" value=\"Search
		\" />
	  </form>";

if ( isset($sent) ) {
	
	require_once 'dbpedia.php';
	require_once 'extraction/extractTemplates.php';
	require_once 'en-arc_ntriples_serializer.php';
	
	define("RDFAPI_INCLUDE_DIR", "api/"); 
	include(RDFAPI_INCLUDE_DIR . "RdfAPI.php");
	// require_once 'RDFapi/RdfAPI.php'; // RAP

	function __autoload($class_name) {
	    require_once $class_name . '.php';
	}


	$pageTitles = array($resource);//, "London", "Paris");
	$job = new ExtractionJob(
	        new LiveWikipedia($lang),
	        new ArrayObject($pageTitles));
	$destination = new WebDebugDestination();
	$group = new ExtractionGroup($destination);

	$group->addExtractor(new LabelExtractor());
	$group->addExtractor(new WikipageExtractor());
	// TODO: does AbstractExtractor work?
	$group->addExtractor(new AbstractExtractor($destination, $destination));
	$group->addExtractor(new ImageExtractor());
	$group->addExtractor(new InfoboxExtractor());
	$group->addExtractor(new PersondataExtractor());
	

	$job->addExtractionGroup($group);

	$manager = new ExtractionManager();
	$manager->execute($job);

	
	
}







?>


</body>
</html>
