<?php

/*
Creates a MediaWiki instance for each language. These instances
are needed for the AbstractExtractor.

We simply copy an existing instance (wikipedia_en) and change
a few things in LocalSettings.php. We could save time and space
if we didn't copy sub directories but created symbolic links
instead, but then loading the classes (factor 10) and rendering 
the pages (factor 3) would be much slower in some cases.
*/

// set $instancesPrefix and $extractionLanguages
require('extractionconfig.php');

$src = $instancesPrefix.'en';
	
foreach ($extractionLanguages as $language) {
	
	if ($language === 'en') continue;
	
	$dst = $instancesPrefix.$language;
	
	echo 'creating instance '.$dst.PHP_EOL; 
	
	// TODO: this script should run on all systems, not just Windows.
	
	if (file_exists($dst)) {
		exec ('rmdir "'.$dst.'" /S /Q');
	}
	
	// add a backslash to $dst to let stoopid Windows know that it's a directory.
	exec ('xcopy "'.$src.'" "'.$dst.'\\" /E /Q');
	
	$file_handle = fopen($dst."/LocalSettings.php", "r");
	if (!$file_handle) {
		die ("File not found ".$file);
	}
	$lines = "";
	while (!feof($file_handle)) {
		$line = trim(fgets($file_handle));
		// echo $line."\n";
		$line = str_replace("Wikipedia_en", "Wikipedia_".$language, $line);
		$line = str_replace("wikipedia_en", "wikipedia_".$language, $line);
		$line = str_replace("dbpedia_en", "dbpedia_".$language, $line);
		$line = str_replace("wgLanguageCode = \"en\"", "wgLanguageCode = \"".$language."\"", $line);
		$lines .= $line."\n";
	}
	if ($lines != "") {
		fclose($file_handle);
		$file_handle = fopen($dst."/LocalSettings.php", "w");
		if (!$file_handle) {
			die ("File not found ".$file);
		}
		if (!fwrite($file_handle,$lines)) {
			die ("error".$file);
		}
		fclose($file_handle);
	} else {
		echo " error: $file\n";
	}
}
