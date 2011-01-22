<?
/**
 * This file is used by the Infobox Extractor and contains the main parsing functions for Infoboxes
 * Required files are:
 * - catchObjectDataTypeFunctions.php: The heart of data extraction. Contains helper functions to recognize
 * data types, internal / external Links, dates, images, etc.
 * - config.inc.php: Setup file, includes database configuration, namespace prefix, templates to ignore, etc.
 * - cleanUpFunctions.php :Inlcudes some functions for code clean up and text coversion
 * - testFunctions.php: Contains helper functions for catchObjectDataTypeFunctions
 */

include ("config.inc.php");
include("cleanUpFunctions.php");
include('testFunctions.php');
include('catchObjectDataTypeFunctions.php');


// Database Connection
global $infoboxDBconnection;
$infoboxDBconnection = mysql_connect($host,$user,$password);
mysql_select_db($db, $infoboxDBconnection);




//////////////////////////////////////////
//
//	Begin legacy code.
//	Should be excluded in the future.
//	Though it has to be tested first, if 
//	these functions really are superfluous
//
//////////////////////////////////////////


if (@$errormsg==true) {
	echo "Extraction will work on Command Line!\n\n";
	echo "Options:\n";
	echo "-c <FILENAME>\t--config <FILENAME>\t Config Filename\n";
	echo "[-n]\t\t[--nohup]\t\t Assume all delete-Questions as no";
	exit;
}

if(!defined("STDIN")) { 
	define("STDIN", fopen('php://stdin','r'));
}


$filedecisionTemplate=($GLOBALS['rdftypeProperty']!=$GLOBALS['W2RCFG']['templateProperty'])?'main':'type';
$filedecisionCategory=($GLOBALS['rdftypeProperty']!=$GLOBALS['W2RCFG']['categoryProperty'])?'main':'type';

if (!function_exists('fnmatch')) {
	function fnmatch($pattern, $string) {
		return @preg_match('/^' . strtr(addcslashes($pattern, '\\.+^$(){}=!<>|'), array('*' => '.*', '?' => '.?')) . '$/i', $string);
	}
}

// Old main function, triggering the extraction process,
// not needed anymore

function extractTemplates() {
	global $W2RCFG;
	$time=microtime(true);
	$pid=0;

	if($GLOBALS['outputFormat']=='csv' && $GLOBALS['powl_db']) {
		mysql_query('TRUNCATE TABLE '.$GLOBALS['powl_db'].'.statements');
		#mysql_query('DELETE FROM '.$GLOBALS['powl_db'].'.statements WHERE modelID='.$GLOBALS['modelID']);
		if($GLOBALS['onefile']) {
			mysql_query('LOAD DATA INFILE "'.str_replace('\\','/',getcwd()).$GLOBALS['outputDir'].$GLOBALS['filename'].'" IGNORE INTO TABLE '.$GLOBALS['powl_db'].'.statements');
			if (is_string($GLOBALS['typefilename'])) 
				mysql_query('LOAD DATA INFILE "'.str_replace('\\','/',getcwd()).$GLOBALS['outputDir'].$GLOBALS['typefilename'].$GLOBALS['filename'].'" IGNORE INTO TABLE '.$GLOBALS['powl_db'].'.statements');
			if (is_string($GLOBALS['labelfilename'])) 
				mysql_query('LOAD DATA INFILE "'.str_replace('\\','/',getcwd()).$GLOBALS['outputDir'].$GLOBALS['labelfilename'].$GLOBALS['filename'].'" IGNORE INTO TABLE '.$GLOBALS['powl_db'].'.statements');			
		}
	}

	// deleting too small files
	if(!$GLOBALS['onefile']) {
		foreach(scandir($GLOBALS['outputDir']) as $file)
			if($file!='.' && $file!='..' && filesize($GLOBALS['outputDir'].$file)<$W2RCFG['minFileSize'])
				unlink($GLOBALS['outputDir'].$file);
			else if($GLOBALS['powl_db'] && $GLOBALS['outputFormat']=='csv') {
				mysql_query('LOAD DATA INFILE "'.str_replace('\\','/',getcwd()).$GLOBALS['outputDir'].$file.'" IGNORE INTO TABLE '.$GLOBALS['powl_db'].'.statements');
			}
	}

	echo("\nTemplate from $pages pages extracted in ".(microtime(true)-$time)."s, last: $last\n");
}
function extractCategoryRelationships() {
	$res=mysql_query('SELECT page_title,cl_to FROM page INNER JOIN categorylinks ON(page_id=cl_from) WHERE page_namespace=14');
	while($row=mysql_fetch_array($res)) {
		if ((empty($GLOBALS['W2RCFG']['categories'])&&empty($GLOBALS['W2RCFG']['categoriesPattern']))||preg_match('~^'.implode($GLOBALS['W2RCFG']['categories'],'|').'$~i',decodeLocalName($row[0]))||fnmatch(implode($GLOBALS['W2RCFG']['categoriesPattern'],'|'),decodeLocalName($row[0])))
			writeTripel($GLOBALS['W2RCFG']['wikipediaBase'].$GLOBALS['categoryLabel'].':'.$row[0],$GLOBALS['W2RCFG']['subCategoryProperty'],$GLOBALS['W2RCFG']['wikipediaBase'].$GLOBALS['categoryLabel'].':'.$row[1],'type');
	}
}



//////////////////////////////////////////
//
// END legacy code
//
//////////////////////////////////////////




/**
 * Ignores specified Templates (see config.inc.php)
 */
function isIgnored($tpl,&$tplName) {
	$tplName=trim(strtolower(substr($tpl,0,strpos($tpl,'|'))));
	// if(!$GLOBALS['templateStatistics'] && $GLOBALS['tplStat'][$tplName]['count']==1)
	// 	return true;
	if(strlen($tplName) < 1) return true;
	if($tplName[0]=='#' || in_array($tplName,$GLOBALS['W2RCFG']['ignoreTemplates']))
		return true;
	foreach($GLOBALS['W2RCFG']['ignoreTemplatesPattern'] as $pattern)
		if(fnmatch($pattern,$tplName))
			return true;
}

/**
* Replace forbidden Ascii symbols by "-"
*
* Forbidden Ascii symbols in a String are replaced by -
*
* @param	string	$string	any text
* @return	string	$string	Text mit valid Ascii symbols
*/
function encodeLocalName($string) {
//  return urlencode(str_replace(" ","_",trim($string)));
	$string = urlencode(str_replace(" ","_",trim($string)));
	// Decode slash "/", colon ":", as wikimedia does not encode these
	$string = str_replace("%2F","/",$string);
	$string = str_replace("%3A",":",$string);
	
    return $string;
}

function decodeLocalName($string) {
	return urldecode(str_replace("_"," ",trim($string)));
}

/**
* Mainfunction to parse Wikitext
*
* The page is parsed recursively for Templates, Html Tags are removed.
* Subtemplates are stored in an array, and afterwards URIs are generated for the subtemplates
* Depending on the template (including =, |, etc) the appropriate function is called and 
* the extraction result is passed to writeTriple.
*
* @param	string	$page	current Wikipediapage (/Subtemplate)
* @param	string	$text	the pagesource code
*/

function parsePage($page,$text) {
	static $bnId; //$nc;
	$tplCount = array();
	// Array containing templatenames to templates occuring more than once on a page
	if ( !isset($knownTemplates) )
		$knownTemplates = array();

	// Array containing parsed Templates
	if ( !isset($parsedTemplates) )
		$parsedTemplates = array();

	$text=preg_replace('~{\|.*\|}~s','',$text); //Prettytables entfernen
	preg_match_all('/\{{2}((?>[^\{\}]+)|(?R))*\}{2}/x',$text,$templates); //  {{....}} suchen
	
	// Loop through every template on the page
	foreach($templates[0] as $tpl) {
		if($tpl[0]!='{')
			continue;
		$tpl=substr($tpl,2,-2);
		$tpl=preg_replace('/<\!--[^>]*->/mU','',$tpl);
		if(isIgnored($tpl,$tplName))
			continue;
		
		// If template occurs more than once on a page generate separate URI:
		// Count occurences
		$templateCount = preg_match_all('/(\{\{\s*)('.preg_quote($tplName,'/').')/',$text,$tmp);
		// Current templatename
		$tmpTemplateName = $tplName; //$tmp[2][0];
		
		
		
		$tpl=preg_replace('~</sup[^>]~','</sup>',$tpl);	//fehlendes </sup   >   reparieren
		
		
		// Do not use this function, as it can merge words, e.g. separated by <br /> tags  "word1<br />word2" => "word1word2"
		$tpl=strip_tags($tpl,$GLOBALS['W2RCFG']['allowedtags']);
	    
	    
		if ( $templateCount > 1 && strlen($tmpTemplateName) > 1 ) {
			if ( !isset($knownTemplates[$tmpTemplateName])  ) {	
				$knownTemplates[$tmpTemplateName] = 1;
			} else {
				$knownTemplates[$tmpTemplateName]++;
			}			
			$subject = $GLOBALS['W2RCFG']['wikipediaBase'].encodeLocalName($page.'/'.$tmpTemplateName.$knownTemplates[$tmpTemplateName]);
			
			//////////////////////////////////////
			// Call function parseTemplate
			//////////////////////////////////////
			
			if($extracted=parseTemplate($subject,$tpl)) {
				writeTripel( $GLOBALS['W2RCFG']['wikipediaBase'].encodeLocalName($page),$GLOBALS['W2RCFG']['propertyBase'].'relatedInstance',$subject,'r' );
				if ( isset($tplCount[$tplName]) ) 
					$tplCount[$tplName]++;
				else 
					$tplCount[$tplName] = 1;
			}
		
		} else {
			$subject = $GLOBALS['W2RCFG']['wikipediaBase'].encodeLocalName($page);
			
			//////////////////////////////////////
			// Call function parseTemplate
			//////////////////////////////////////
			
			if($extracted=parseTemplate($subject,$tpl)) {
				if ( isset($tplCount[$tplName]) ) 
					$tplCount[$tplName]++;
				else 
					$tplCount[$tplName] = 1;
			}
				
		}

	} // END foreach Template
	return $tplCount;
}


// Helpfunction for preg_replace_callback, to replace "|" with #### inside subtemplates
function replaceBarInSubTemplate($stringArray) {
	return str_replace("|","####",$stringArray[0]);
}


function parseTemplate($subject,$template) {
	// If template/subTemplate is listed as ignored, return false
	if (isIgnored($template,$tplName)) return false;
	
	// Find subtemplates and remove Subtemplates, which are listed as ignored!
	preg_match_all('~\{((?>[^{}]+)|(?R))*\}~x',$template,$subTemplates);
	foreach($subTemplates[0] as $key=>$subTemplate) {
		$subTemplate=preg_replace("/(^\{\{)|(\}\}$)/","",$subTemplate); // Cut Brackets / {}
		if(isIgnored($subTemplate,$tplName)) 
			$template=str_replace('{{'.$subTemplate.'}}','',$template);	
	}
	 
	// Replace "|" inside subtemplates with "\\" to avoid splitting them like triples
	$template = preg_replace_callback("/(\{{2})([^\}\|]+)(\|)([^\}]+)(\}{2})/",'replaceBarInSubtemplate',$template);
	
	
	$equal=preg_match('~=~',$template);

	// Gruppe=[[Gruppe-3-Element|3]]  ersetzt durch Gruppe=[[Gruppe-3-Element***3]]
	do $template=preg_replace('/\[\[([^\]]+)\|([^\]]*)\]\]/','[[\1***\2]]',$template,-1,$count); while($count);
	$triples=explode('|',$template);
	if(count($triples)<=$GLOBALS['W2RCFG']['minAttributeCount'])
		return false;

	$templateName=strtolower(trim(array_shift($triples)));

	if(!isBlanknote($subject) && !$GLOBALS['onefile'])
		$GLOBALS['filename']=urlencode($templateName).'.'.$GLOBALS['outputFormat'];


	// Array containing URIs to subtemplates. If the same URI is in use already, add a number to it
	$knownSubTemplateURI = array();

	// subject
	$s=$subject;
	$z = 0;
	foreach ($triples as $triple) {
		if($equal) {
			list($p,$o)=explode('=',$triple,2);
			$p=trim($p);
		} else {
			$p="property".(++$z);
			$o=$triple;
		}
		$o=trim($o);		
		
		// Do not allow empty Properties
		if ( strlen($p) < 1 )
			continue;
		
		
		if($o!=='' & $o!==NULL) {
			$pred=$p;
				// if(!$GLOBALS['templateStatistics'] && $GLOBALS['propertyStat'][$p]['count']<10)
				//continue;
				
			// predicate
			// Write properties CamelCase, no underscores, no hyphens. If first char is digit, add _ at the beginning
			$p = encodeLocalName(propertyToCamelCase($p));
			// Add prefixProperties if set true in config.inc.php
			if ( $GLOBALS['prefixPropertiesWithTemplateName']) $p = encodeLocalName( propertyToCamelCase($templateName).'_'.$p);
			else if ( !$equal ) $p = propertyToCamelCase(encodeLocalName($templateName."_".$p));
			
					
			
			// object
			$o=str_replace('***','|',$o);
			// Remove HTML Markup for whitespaces
			$o = str_replace('&nbsp;',' ',$o);
			
						
			// Parse Subtemplates (only parse Subtemplates with values!)
			
			if ( preg_match_all("/(\{{2})([^\}]+)(\}{2})/",$o,$subTemplates, PREG_SET_ORDER) ) {
				foreach ( $subTemplates as $subTemplate ) {
					// Replace #### back to |, in order to parse subtemplate properly
					$tpl = str_replace("####","|",$subTemplate[2]);
					// If subtemplate contains values, the subject is only the first word
					if ( preg_match("/(^[^\|]+)(\|)/",$tpl,$match) )
						$subTemplateSubject = encodeLocalName($subject.'/'.$p.'/'.$match[1]);
					else
						$subTemplateSubject = encodeLocalName($subject.'/'.$p.'/'.$tpl);
					// Look up URI in Array containing known URIs, if found add counter to URI.
					// e.g. http://dbpedia.org/United_Kingdom/footnote/cite_web
					// ==>  http://dbpedia.org/United_Kingdom/footnote/cite_web1 ...							
					if ( !isset($knownSubTemplateURI[$subTemplateSubject]) ) {
						// array_push( $knownSubTemplateURI, $subTemplateSubject );	
						$knownSubTemplateURI[$subTemplateSubject] = 0;
					} else {
						$knownSubTemplateURI[$subTemplateSubject]++;
						$subTemplateSubject .= $knownSubTemplateURI[$subTemplateSubject];
					}
					
					// If subtemplate contained real values, write the corresponding triple
					if ( parseTemplate( $subTemplateSubject, $tpl ) )
						writeTripel($s,$GLOBALS['W2RCFG']['propertyBase'].$p,$subTemplateSubject,'main','r',null,null);
				}
			}
			
			
			// Remove subTemplates from Strings
			$o = str_replace("####","|",$o);
			$o = preg_replace("/\{{2}[^\}]+\}{2}/","",$o);
			// Sometimes only whitespace remain, then continue with next triple
			if ( preg_match("/^[\s]*$/",$o) )
				continue;
			
			// Add URI prefixes to property names
			$p=$GLOBALS['W2RCFG']['propertyBase'].$p;
			
			if(isBlanknoteList($o)) {
				printList($s,$p,$o);
			}	
			else {
				list($o,$o_is,$dtype,$lang)=parseAttributeValue($o,$s,$p);
				if($o!==NULL) 
					writeTripel($s,$p,$o,'main',$o_is,$dtype,$lang);
			}
			if($GLOBALS['templateStatistics'] && $o!=NULL && $equal) {
				$GLOBALS['propertyStat'][$pred]['count']++;
				$GLOBALS['propertyStat'][$pred]['maxCountPerTemplate']=max($GLOBALS['propertyStat'][$pred]['maxCountPerTemplate'],++$pc[$pred]);
				if(!$GLOBALS['propertyStat'][$pred]['inTemplates'] || !in_array($templateName,$GLOBALS['propertyStat'][$pred]['inTemplates']))
					$GLOBALS['propertyStat'][$pred]['inTemplates'][]=$templateName;
			}
			$extracted=true;
		}
	}
	if($extracted) {
		writeTripel($s,$GLOBALS['W2RCFG']['templateProperty'],$GLOBALS['W2RCFG']['wikipediaBase'].$GLOBALS['templateLabel'].':'.encodeLocalName($templateName),$GLOBALS['filedecisionTemplate']);
		if ($GLOBALS['addExplicitTypeTriples'])
			printexplicitTyping($templateName,$GLOBALS['filename'],'t');
	}
	return $extracted;
}

// Converts internal Links to Lins to DBpedia, as these were copied & pasted from Wikipedia
// and do not work in wikicompany (e.g. products = [[Computer]] => products = [http://dbpedia.org/resource/Computer])
function convertDBpediaLinks($matches) {
	return '[http://dbpedia.org/resource/'.encodeLocalName(trim($matches[1])).']';
}


/**
 * Main function to extract data-types, internal Links etc. from the Template.
 * 
 * 
 */
function parseAttributeValue($object,$subject,$predicate) {
	// Generate DBpedia Links from Internal Links
	//$object = preg_replace_callback('/\[\[([a-zA-Z\., \-_!&\$€¥]]+)(\|[a-zA-Z\., \-_!&\$€¥])*\]\]/','convertDBpediaLinks',$object);
	$object = preg_replace('/\[\[([0-9]{4})\]\]/','$1',$object);
	$object = preg_replace_callback('/\[\[([^\:\|\]]+)(\|[^\]]+)?\]\]/','convertDBpediaLinks',$object);
	
	
	
	removeSemanticExtension($object);
	$object = str_replace("[[image:green up.png]]","",$object);
	$object = str_replace("[[image:red down.png]]","",$object);
	
	
	
	
	
	$dtype = null;
	$lang = null;
		
	$object_is='l';
	if(isBlanknote($object)) {
		$object_is='b';
		$object=str_replace(";","",$object);
	} else if(isInt($object))
		$dtype='http://www.w3.org/2001/XMLSchema#integer';
	else if(isIntwithComma($object)) {
		$object=str_replace(",","",$object);
		$dtype='http://www.w3.org/2001/XMLSchema#integer';
	} else if(isFloat($object))
		$dtype='http://www.w3.org/2001/XMLSchema#decimal';
	else if(catchPictureURI($object)) {
		$object_is='r';
	}
	else if(catchMonthYear($object) )
		$dtype='http://www.w3.org/2001/XMLSchema#gYearMonth';
	else if(catchDate($object))
		$dtype='http://www.w3.org/2001/XMLSchema#date';
	else if(catchYear($object))
		$dtype='http://www.w3.org/2001/XMLSchema#gYear';
	else if(catchRank($object)) {
		$dtype=$GLOBALS['W2RCFG']['w2ruri'].'Rank';
	}
	else if(catchLargeNumber($object))
		$dtype='http://www.w3.org/2001/XMLSchema#integer';
	else if($dtype=catchLargeMoney($object));
	else if($dtype=catchMoneyWoCent($object));
	else if($dtype=catchMoney($object));
	else if(catchPercent($object))
		$dtype=$GLOBALS['W2RCFG']['w2ruri'].'Percent';
	else if($dtype=catchUnited($object));
	else if(catchLink($object)) {
		$object_is='r';
	}

	// Parses objects with remanining internal links. If any link is found,
	// $object is processed and the function calls itself writeTripel(...),
	// and must return null therefore.
	else if( catchLinkList($object, $subject, $predicate, $dtype) ) {
		return null;
	}
	// Parses for external Links
	else if( $list = catchExternalLink($object) ) {
		// $list = catchExternalLink($object);		
		foreach($list[1] as $l) {
			if(strlen($l)>1) {
				$l=explode(" ",$l);
				$object = $l[0];
				$object_is='r';
				writeTripel($subject,$predicate,$object,'main',$object_is);
				unset($object);
			}
		}
		return null;
	}
	// Parses numbers followed by reference-link or year e.g.: revenue = 12000 $ (2004)
	else if ( catchNumberWithReference($object,$subject,$predicate) )
		return null;

	// Ignore, if no specific data-type, but still contains Wiki-Code (PIET)
	//	elseif ( containsWikiCode($subject, $predicate, $object) ) return;
	
	// Remove WikiMedia formatting commands ('',''',*,#)
	else {
		removeWikiCode($object);
		
	}

	if ($GLOBALS['addExplicitTypeTriples'])
		printexplicitTyping($predicate,$GLOBALS['filename'],'p',$object_is);
	if ($GLOBALS['addExplicitTypeTriples']&&$GLOBALS['correctPropertyType'])
		$object_is=printexplicitTyping($predicate,$GLOBALS['filename'],'p',$object_is);
	if ( strlen(trim($object)) < 1 )
		return null;
	return array($object,$object_is,$dtype,$lang);
}

/**
 * Writes the triple + additional information such as language, whether an object is a reference
 * or a literal and the datatype into a global array ($parseResult)
 * 
 * @param subject: String containing the triples subject
 * @param predicate: String containing the triples predicate
 * @param object: String containing the triples object
 * @param file: Legacy, should be removed in the future
 * @param object_is: 'r' if object is a reference, 'l' if object is a literalm 'b' if object is a blanknode
 * @param dtype: String containing a literals XSD:datatype
 * @param lang: String containing a literals language
 * 
 */

function writeTripel($subject,$predicate,$object,$file='main',$object_is='r',$dtype=NULL,$lang=NULL) {
	global $parseResult;
	if ( $object_is == 'r' && !URI::validate($object) ) return null;

	$predicate = str_replace("%","_percent_",$predicate);
	$parseResult[] = array($subject, $predicate, $object, $object_is, $dtype, $lang );
}



/**
 * This function is legacy Code and should be removed in the future
 * 
 * 
* Speichert Ausgabe in Variable, die sp‰ter in Datei geschrieben wird
*
* Diese Funktion wird nur benutzt, wenn als Wert eines Pr‰dikates mehrere Blanknotes erscheinen
* Es wird f¸r jede Blanknote eine Zeile in der Ausgabe generiert
*
* @param	string	$page	aktuelle Wikiseite bzw. aktuell bearbeitete Blanknote
* @param	string	$p	Pr‰dikat, welches als Objekt eine Blanknoteliste hat
* @param	string	$o	Blanknoteliste der Form _:a1;_:a2;
* @param	string	$propertyBase	Standarduri
* @param	string	$template	aktuelles Template
*/
function printList($s,$p,$o) {
	$o=explode(';',$o);
	foreach($o as $object)
		if(strlen($object)>1) {
			list($ob,$ob_is,$dtype,$lang)=parseAttributeValue(trim($object),$s,$p);
			if ($ob)
				writeTripel($s,$p,trim(str_replace("\n",'',$ob)),'main',$ob_is);
		}
}

/**
* This function is legacy Code and should be removed in the future.
* 
* 
* Gibt Kategorien als rdf:type aus
*
* Kategorien auf den Seiten mit den Templates werden als rdf:type ausgegeben.
* um den nat¸rlichsprachlichen Inhalt der Klassen nicht zu verlieren wird ausserdem
* ein rdfs:label ausgegeben
*
* @param	string	$category	Kategoriename
* @param	string	$page	aktueller Seitenname
* @param	string	$propertyBase	Standarduri
* @param	string	$template	aktuelles Template
*/
function printCategory($category,$subject) {
	#static $done;
	$categoryURI=$GLOBALS['W2RCFG']['wikipediaBase'].$GLOBALS['categoryLabel'].':'.encodeLocalName($category);
	if ((empty($GLOBALS['W2RCFG']['categories'])&&empty($GLOBALS['W2RCFG']['categoriesPattern']))||preg_match('~^'.implode($GLOBALS['W2RCFG']['categories'],'|').'$~i',$category)||fnmatch(implode($GLOBALS['W2RCFG']['categoriesPattern'],'|'),$category)) {
		writeTripel($subject,$GLOBALS['W2RCFG']['categoryProperty'],$categoryURI,$GLOBALS['filedecisionCategory']);
		#if($done[$category])
		#	return;
		#$done[$category]=true;
		if (!categoryTypeWritten($categoryURI,$GLOBALS['filename'],'c')) {
			writeTripel($categoryURI,$GLOBALS['W2RCFG']['labelProperty'],trim($category),'label','l');
			if ($GLOBALS['addExplicitTypeTriples'])
				printexplicitTyping($categoryURI,$GLOBALS['filename'],'c');	
		}
	}
}

function categoryTypeWritten($categoryName,$filename,$catortemp) {
	static $categoryarray=array();
	$categoryName.=($catortemp=='c')?':Cat':':Temp';
	if (!arrayMultiSearch($categoryName,$categoryarray[$filename])) {
		$categoryarray[$filename][]=$categoryName;
		return false;
	}
	return true;
}

function printexplicitTyping($name,$filename,$name_is,$object_is='n') {
	static $namearray=array();
	static $predicatetypearray=array();
	if ($name_is=='c')
		$save=$name.':Cat';
	if ($name_is=='t')
		$save=$name.':Temp';
	if ($name_is=='p')
		$save=$name.':Pred';
	if (!arrayMultiSearch($save,$namearray[$filename])) {
		$namearray[$filename][]=$save;
		if ($object_is!='n')
			$predicatetypearray[$save][$filename]['is']=$object_is;
		$filedecisionTemplate=($GLOBALS['rdftypeProperty']!=$GLOBALS['W2RCFG']['templateProperty'])?'main':'type';
		$filedecisionCategory=($GLOBALS['rdftypeProperty']!=$GLOBALS['W2RCFG']['categoryProperty'])?'main':'type';
		if ($name_is=='c'&&$filedecisionCategory=='type')
			writeTripel($name,$GLOBALS['W2RCFG']['categoryProperty'],$GLOBALS['W2RCFG']['classBase'],'type');
		if ($name_is=='c'&&$filedecisionCategory=='main')
			printexplicitTyping($GLOBALS['W2RCFG']['categoryProperty'],$filename,'p','r');
		if ($name_is=='t'&&$filedecisionTemplate=='type')
			writeTripel($GLOBALS['W2RCFG']['wikipediaBase'].$GLOBALS['templateLabel'].':'.encodeLocalName($name),$GLOBALS['rdftypeProperty'],$GLOBALS['W2RCFG']['classBase'],'type');
		if ($name_is=='t'&&$filedecisionTemplate=='main') 
			printexplicitTyping($GLOBALS['W2RCFG']['templateProperty'],$filename,'p','r');
		if ($name_is=='p')
			writeTripel($name,$GLOBALS['rdftypeProperty'],($object_is=='l')?$GLOBALS['W2RCFG']['datatypePropertyBase']:$GLOBALS['W2RCFG']['objectPropertyBase'],'type');	
		return;
	} else
		return $predicatetypearray[$save][$filename]['is'];	
}

/**
 * Help function to search in arrays
 */
function arrayMultiSearch( $p_needle, $p_haystack ) {
   if( !is_array( $p_haystack ) )
       return false;

   if( in_array($p_needle, $p_haystack ))
       return true;

   foreach( $p_haystack as $row ) {
       if(arrayMultiSearch( $p_needle, $row ))
           return true;
   }
   return false;
}

/**
 * This function is legacy code and should be removed in the future
 * 
 * 
 * Serializes Strings to their uncicode representation 
 */
function unicodeLiterals($str) {
	$transTable = array();
	$utf8reg = '/[\xC0-\xDF][\x80-\xBF]|[\xE0-\xEF][\x80-\xBF][\x80-\xBF]/';
	if(!preg_match_all($utf8reg, $str, $matches))
		return $str;
	  
	foreach($matches[0] as $utf8char) {
		if(isset($transTable[$utf8char])) {
			continue;
		}
		$transTable[$utf8char] = '\u'. substr('0000'. bin2hex(mb_convert_encoding($utf8char, 'UTF-16', 'UTF-8')), -4);
	}
	return strtr($str, $transTable);
}
