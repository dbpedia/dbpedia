<?php

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


	//<ref></ref> samt Inhalt entfernen
	$tpl= preg_replace('/(<ref>.+?<\/ref>)/s','',$tpl);


	// Do not use this function, as it can merge words, e.g. separated by <br /> tags  "word1<br />word2" => "word1word2"
	// all tags should be stript, but not the <ref>-tags. : these and the content between these tags should be filtered out
	//$GLOBALS['W2RCFG']['allowedtags'] = $GLOBALS['W2RCFG']['allowedtags']."<ref> </ref>";
	$tpl=strip_tags($tpl,$GLOBALS['W2RCFG']['allowedtags']);
	//$GLOBALS['W2RCFG']['allowedtags'] = str_replace("<ref>","",$GLOBALS['W2RCFG']['allowedtags']);
	//$GLOBALS['W2RCFG']['allowedtags'] = str_replace("</ref>","",$GLOBALS['W2RCFG']['allowedtags']);

	if ( $templateCount > 1 && strlen($tmpTemplateName) > 1 ) {
		if ( !isset($knownTemplates[$tmpTemplateName])  ) {
			$knownTemplates[$tmpTemplateName] = 1;
		} else {
			$knownTemplates[$tmpTemplateName]++;
		}
		$subject = $GLOBALS['W2RCFG']['wikipediaBase'].$page.'/'.$tmpTemplateName.$knownTemplates[$tmpTemplateName];

		//////////////////////////////////////
		// Call function parseTemplate
		//////////////////////////////////////

		if($extracted=$this->parseTemplate($subject,$tpl, $language)) {
			writeTripel( $GLOBALS['W2RCFG']['wikipediaBase'].$page,$GLOBALS['W2RCFG']['propertyBase'].'relatedInstance',$subject,'r' );
			if ( isset($tplCount[$tplName]) )
				$tplCount[$tplName]++;
			else
				$tplCount[$tplName] = 1;
		}

	} else {
		$subject = $GLOBALS['W2RCFG']['wikipediaBase'].$page;

		//////////////////////////////////////
		// Call function parseTemplate
		//////////////////////////////////////

		if($extracted=$this->parseTemplate($subject,$tpl, $language)) {
			if ( isset($tplCount[$tplName]) )
				$tplCount[$tplName]++;
			else
				$tplCount[$tplName] = 1;
		}
	}

} // END foreach Template
return $tplCount;
