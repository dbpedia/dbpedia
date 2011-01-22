<?php

include("./extractors/classes/CategoriesToClasses.php");
include("./extractors/classes/CategoriesClassesExtraction.php");
class CategoriesClassesExtractor implements GlobalExtractor{
	private $replaceMentArray=array("");
	// notifies the ExecutionManager that a GlobalExtractor comes along
	// public $isGlobalExtractor=true;


	/**
	 * starts the extraction 
	 *
	 * @param int $pageID
	 * @param String $pageTitle
	 * @param String $pageSource
	 * @return extractionResult extractionResult - the result of the Extraction
	 */
	function extract($destination){
		include("infobox/config.inc.php");
		include("databaseconfig.php");
		include("classes/configCategoriesClasses.php");
		echo "starte Klassenextraktion";
		$pageID=815;
		$extractionResult=new ExtractionResult($pageID,$this->language,$this->getExtractorID());
		$link = mysql_connect($host, $user, $password, true)
			or die("Keine Verbindung m�glich: " . mysql_error());

		mysql_select_db($dbprefix.'en', $link) or die("CategoriesClassesExtractor : Auswahl der Datenbank fehlgeschlagen\n");
		// detect which categories are classes
		$categoriesToClasses=new CategoriesToClasses($link,$tempTableName);
		$categoriesToClasses->start();
		$result=$categoriesToClasses->getClasses();
		// detect class - subclass - relationships
		$categoriesClassesExtraction=new CategoriesClassesExtraction($tempTableName,$extractionResult,$result,$destination);
		$categoriesClassesExtraction->setLink($link);
		$categoriesClassesExtraction->extractClasses();
		$extractionResult=$categoriesClassesExtraction->getExtractionResult();
		return $extractionResult;
	}
	
	/**
	 * finishes the extraction. no implementation
	 *
	 * @return null
	 */
	function finish(){
		return null;
	}
	
	
}
