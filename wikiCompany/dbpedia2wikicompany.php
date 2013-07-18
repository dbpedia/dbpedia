<?PHP

/**
* Generates dbpedia to wikicompany links, from wikicompany to dbpedia links (NTriples)
*/

$filename  = "wikicompanyLinks.nt";
$filecontent = file_get_contents('dbpedia_links.nt');
// $filecontent = "<abc> <def> <ghj> .\n<abc> <def> <ghj> .\n";
$lines = preg_split(".\n",$filecontent);

foreach($lines as $line) {
	$triple = explode('>',$line);
	$subject = trim($triple[0]).'>';
	$predicate = trim($triple[1]).'>';
	$object = trim($triple[2]).'>';
	$newTriple = "$object $predicate $subject .\n";
	file_put_contents($filename, $newTriple, FILE_APPEND);	
}


