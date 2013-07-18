<?php

/**
 * This file contains helper functions for datatype recognition.
 * These are called by parseAttributeValue in extractTemplates.php
 *
 */

class ParseAttribute implements ParseAttributeInterface {

	public function parseValue($object, $subject, $predicate, &$extractor, $language=NULL) {

		$dtype = null;
		$object_is = 'l';

		if(isBlanknote($object)) {
			$object_is='b';
			$object=str_replace(";","",$object);
		}
		else if(isInt($object)) {
			$dtype='http://www.w3.org/2001/XMLSchema#integer';
		}
		else if(isIntwithComma($object)) {
			$object=str_replace(",","",$object);
			$dtype='http://www.w3.org/2001/XMLSchema#integer';
		}
		else if(isFloat($object)) {
			$dtype='http://www.w3.org/2001/XMLSchema#decimal';
		}
		// infobox extractor should not extract the image which has been extracted
		// by the image extractor; however it is difficult to decide which images has been
		// extracted by the image extractor;
		// currently we extract all images - this involved the risk of extracting non-free
		// images
		else if(catchPictureURI($object,$subject)) {
			$object_is='r';
			$image = substr($object,strrpos($object, '/')+1);
			$wikipediaImageDescription = 'http://'.$language.'.wikipedia.org/wiki/Image:'.$image;
			writeTripel($object, 'http://purl.org/dc/terms/rights', $wikipediaImageDescription ,'main',$object_is);
		}
		else if(catchMonthYear($object)) {
			$dtype='http://www.w3.org/2001/XMLSchema#gYearMonth';
		}
		else if(catchDate($object)) {
			$dtype='http://www.w3.org/2001/XMLSchema#date';
		}
		else if(catchYear($object)) {
			$dtype='http://www.w3.org/2001/XMLSchema#gYear';
		}
		else if(catchRank($object)) {
			$dtype=$GLOBALS['W2RCFG']['w2ruri'].'Rank';
		}
		else if(catchLargeNumber($object)) {
			$dtype='http://www.w3.org/2001/XMLSchema#integer';
		}
		else if($dtype=catchLargeMoney($object));
		else if($dtype=catchMoneyWoCent($object));
		else if($dtype=catchMoney($object));
		else if(catchPercent($object)) {
			$dtype=$GLOBALS['W2RCFG']['w2ruri'].'Percent';
		}
		else if($dtype=catchUnited($object));
		else if(catchLink($object)) {
			$object_is='r';
		}
		// Parses objects with remanining internal links. If any link is found,
		// $object is processed and the function calls itself writeTripel(...),
		// and must return null therefore.
		else if( catchLinkList($object, $subject, $predicate, $dtype, $extractor) ) {
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
		else if ( catchNumberWithReference($object,$subject,$predicate,$extractor) ) {
			return null;
		}
		// Ignore, if no specific data-type, but still contains Wiki-Code (PIET)
		//	elseif ( containsWikiCode($subject, $predicate, $object) ) return;

		// Remove WikiMedia formatting commands ('',''',*,#)
		else {
			removeWikiCode($object);
		}

		//if ($GLOBALS['addExplicitTypeTriples'])
		//	printexplicitTyping($predicate,$GLOBALS['filename'],'p',$object_is);
		//if ($GLOBALS['addExplicitTypeTriples']&&$GLOBALS['correctPropertyType'])
		//	$object_is=printexplicitTyping($predicate,$GLOBALS['filename'],'p',$object_is);
		if ( strlen(trim($object)) < 1 ) {
			return null;
		}
		return array($object,$object_is,$dtype,$language);

	}

	public function parseSubTemplate($predicate, $object) {
		return false;
	}

}
