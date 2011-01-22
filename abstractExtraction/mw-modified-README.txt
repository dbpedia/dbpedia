This file describes how the MediaWiki instance was modified.
============================================================

* Get MediaWiki version used by Wikipedia from 
  http://www.mediawiki.org/wiki/Download .
  
  Information on this version as well as installed extensions can be found here:
  http://en.wikipedia.org/wiki/Special:Version .

* Keep only content from directory "phase3". Since this name might change, the directory listing in 2008-11 was:
	<DIR> bin
	<DIR> config
	<DIR> docs
	<DIR> extensions
	<DIR> images
	<DIR> includes
	<DIR> languages
	<DIR> locale
	<DIR> maintenance
	<DIR> math
	<DIR> serialized
	<DIR> skins
	<DIR> t
	<DIR> tests
	
* Download (at least) the following MediaWiki extensions:
  * ParserFunctions
  * FixedImage
  * CategoryTree
  * CharInsert
  * SpecialCrossNamespaceLinks
  * ExtensionFunctions
  * Cite
  * SpecialCite
  * ExpandTemplates
  
  It is recommended to use the extension download links on
  http://en.wikipedia.org/wiki/Special:Version to get the right version.
  
  The installation process includes downloading and extracting the extensions
  to the ./extensions/ directory and afterwards adding the following lines
  to ./LocalSettings.php (cf. extension sites for updated installation guides!)

		$wgUseAjax = true;
		require_once("$IP/extensions/ExtensionFunctions.php");
		require_once( "$IP/extensions/ParserFunctions/ParserFunctions.php" );
		require_once("$IP/extensions/CharInsert/CharInsert.php");
		require_once "$IP/extensions/CrossNamespaceLinks/SpecialCrossNamespaceLinks.php";
		require_once("$IP/extensions/Cite/SpecialCite.php");
		require_once( $IP.'/extensions/Cite/Cite.php' );
		require_once("$IP/extensions/ExpandTemplates/ExpandTemplates.php");

* Adding of DBpediaFunctions.php to the base directory of the MediaWiki installation.
  
* Add the following line to LocalSettings.php:
      require_once("DBpediaFunctions.php");

* In includes/api/ApiParse.php, replace the line
      $result->setContent($result_array['text'], $p_result->getText());
  with the line
      $result->setContent($result_array['text'], DBpediaFunctions::cleanHtml($p_result->getText()));




== OBSOLETE MODIFICATIONS ==

This section applies to the old setup of the AbstractExtractor, prior to 2010-12. Its functionality is not used anymore.

* Open ./includes/Article.php.
  Find "public function outputWikiText( $text ...) {".
  
  Add the following line at the start of the function outputWikiText():
  
  $text = DBpediaFunctions::getAbstract($text);
  
  This call extracts the introduction from the Wiki source and cleans it up a bit.
  
  Add the following line at the end of the function outputWikiText():
  
  DBpediaFunctions::cleanOutput();
  
  This call cleans up the HTML result that is stored in the global variable $wgOut.
  

