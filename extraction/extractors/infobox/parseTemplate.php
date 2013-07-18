<?php

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

//	if(!isBlanknote($subject) && !$GLOBALS['onefile'])
//		$GLOBALS['filename']=urlencode($templateName).'.'.$GLOBALS['outputFormat'];


// Array containing URIs to subtemplates. If the same URI is in use already, add a number to it
$knownSubTemplateURI = array();

// subject
$s=$subject;
$z = 0;
foreach ($triples as $triple) {

	if($equal) {
		$split = explode('=',$triple,2);
		if(count($split)<2)
			continue;
		list($p,$o)= $split;
		$p=trim($p);
	} else {
		$p="property".(++$z);
		$o=$triple;
	}
	$o=trim($o);

	// if property date and object an timespan we extract it with following special case
	if ($p == "date")
	{
		$o = str_replace("[","",str_replace("]","",$o));
		$o = str_replace("&ndash;","-", $o);
	}

	// Do not allow empty Properties
	if ( strlen($p) < 1 )
		continue;

	if(in_array($p, $GLOBALS['W2RCFG']['ignoreProperties']))
		continue;

	if($o!=='' & $o!==NULL) {
		$pred=$p;
			// if(!$GLOBALS['templateStatistics'] && $GLOBALS['propertyStat'][$p]['count']<10)
			//continue;

		// predicate
		// Write properties CamelCase, no underscores, no hyphens. If first char is digit, add _ at the beginning
		$p = propertyToCamelCase($p);

		// Add prefixProperties if set true in config.inc.php
		if ( $GLOBALS['prefixPropertiesWithTemplateName']) $p = propertyToCamelCase($templateName).'_'.$p;
		else if ( !$equal ) $p = propertyToCamelCase($templateName."_".$p);



		// object
		$o=str_replace('***','|',$o);
		// Remove HTML Markup for whitespaces
		$o = str_replace('&nbsp;',' ',$o);

		//remove <ref> Content</ref>
		//$o = preg_replace('/(<ref>.+?<\/ref>)/s','',$o);

		// Parse Subtemplates (only parse Subtemplates with values!)
		if ( preg_match_all("/(\{{2})([^\}]+)(\}{2})/",$o,$subTemplates, PREG_SET_ORDER) ) {
			foreach ( $subTemplates as $subTemplate ) {
				// Replace #### back to |, in order to parse subtemplate properly
				$tpl = str_replace("####","|",$subTemplate[2]);
				// If subtemplate contains values, the subject is only the first word
				if ( preg_match("/(^[^\|]+)(\|)/",$tpl,$match) ) {
					$subTemplateSubject = $subject.'/'.$p.'/'.$match[1];
				} else {
					$subTemplateSubject = $subject.'/'.$p.'/'.$tpl;
				}

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

				// Search for special Templates
				foreach ($this->parsers as $parser) {
					list($subTemplPredicat,$subTemplObject,$SubTemplDtype) = $parser->parseSubTemplate($p, $tpl);
					if ($subTemplPredicat) {
						break;
					}
		 		}

				// If a known template is discovered, then write the corresponding triple
				if ($subTemplPredicat) {
					writeTripel($subject,$GLOBALS['W2RCFG']['propertyBase'].$subTemplPredicat,$subTemplObject,'main','l',$SubTemplDtype,null);
				} // If subtemplate contained real values, write the corresponding triple
				else if ( $this->parseTemplate( $subTemplateSubject, $tpl ) ) {
					writeTripel($s,$GLOBALS['W2RCFG']['propertyBase'].$p,$subTemplateSubject,'main','r',null,null);
				}
			}
		}

		// Remove subTemplates from Strings
		$o = str_replace("####","|",$o);
		$o = preg_replace("/\{{2}[^\}]+\}{2}/","",$o);
		// Sometimes only whitespace remain, then continue with next triple
		if ( preg_match("/^[\s]*$/",$o) )
			continue;


		//replace predicate if necessary to make them unambiguous
		$p=replacePredicate($p);

		// Add URI prefixes to property names
		$p=$GLOBALS['W2RCFG']['propertyBase'].$p;

		if(isBlanknoteList($o)) {
			printList($s,$p,$o);
		}
		else {
			list($o,$o_is,$dtype,$lang)=$this->parseAttributeValue($o,$s,$p,$language);

			// special newline handling
			$br = array('<br>','<br/>','<br />');
			if($o_is=='l') {
				$o = str_replace($br,"\n",$o);
			} else if($o_is=='r') {
				$o = str_replace($br,'',$o);
			}

			if($o!==NULL)
				writeTripel($s,$p,$o,'main',$o_is,$dtype,$lang);
		}

		//if($GLOBALS['templateStatistics'] && $o!=NULL && $equal) {
		//	$GLOBALS['propertyStat'][$pred]['count']++;
		//	$GLOBALS['propertyStat'][$pred]['maxCountPerTemplate']=max($GLOBALS['propertyStat'][$pred]['maxCountPerTemplate'],++$pc[$pred]);
		//	if(!$GLOBALS['propertyStat'][$pred]['inTemplates'] || !in_array($templateName,$GLOBALS['propertyStat'][$pred]['inTemplates']))
		//		$GLOBALS['propertyStat'][$pred]['inTemplates'][]=$templateName;
		//}
		$extracted=true;
	}
}
if(isset($extracted) && $extracted) {
	//writeTripel($s,$GLOBALS['W2RCFG']['templateProperty'],$GLOBALS['W2RCFG']['wikipediaBase'].$GLOBALS['templateLabel'].':'.encodeLocalName($templateName),$GLOBALS['filedecisionTemplate']);
	writeTripel($s,$GLOBALS['W2RCFG']['templateProperty'],$GLOBALS['W2RCFG']['wikipediaBase'].$GLOBALS['templateLabel'].':'.$templateName);
	//if ($GLOBALS['addExplicitTypeTriples'])
	//	printexplicitTyping($templateName,$GLOBALS['filename'],'t');
}
