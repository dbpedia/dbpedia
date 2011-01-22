<?php

/**
 *	Parsing file for Korean language constructs
 */

class KoParseAttribute implements ParseAttributeInterface {

	public function parseValue($object, $subject, $predicate, &$extractor, $language=NULL) {

		$dtype = null;
		$object_is = 'l';
		if($this->catchMonthYear($object)) {
			$dtype='http://www.w3.org/2001/XMLSchema#gYearMonth';
		}
		else if($this->catchDate($object)) {
			$dtype='http://www.w3.org/2001/XMLSchema#date';
		}
		else if($this->catchYear($object)) {
			$dtype='http://www.w3.org/2001/XMLSchema#gYear';
		}

		if ( strlen(trim($object)) < 1 ) {
			return null;
		}
		if ($dtype || $object_is != 'l') {
			return array($object,$object_is,$dtype,$language);
		} else {
			return null;
		}

	}

	public function parseSubTemplate($predicate, $object) {

		$dtype = null;
		if($this->catchBirthday($object)) {
			$dtype='http://www.w3.org/2001/XMLSchema#date';
			return array($predicate, $object, $dtype);
		}
		else if($this->catchDeathday($object)) {
			$dtype='http://www.w3.org/2001/XMLSchema#date';
			return array($predicate, $object, $dtype);
		}

		return false;
	}


	/**
	 * Catch date like: 2010년 2월 (February 2010)
	 */
	function catchMonthYear(&$x) {
		if ( preg_match('~^\[?\[?([0-9]{1,4}년) ([0-9]{1,2}월)\]?\]?$~', $x, $matches) ) {
			$x=substr('0000'.substr($matches[1], 0, -3), -4).'-'.substr('00'.substr($matches[2], 0, -3), -2);
			return true;
		}
	}

	/**
	 * Korean Date e.g. [[2003년]] [[1월 1일]]
	 */
	private function catchDate(&$x) {
		if(preg_match('~^\[?\[?([0-9]{1,4}년)\]?\]? \[?\[?([0-9]{1,2}월) ([0-9]{1,2}일)\]?\]?$~',$x,$matches)) {
			$x = substr('0000'.substr($matches[1], 0, -3), -4).'-'.substr('00'.substr($matches[2], 0, -3), -2).'-'.substr('00'.substr($matches[3], 0, -3), -2);			//yearmonthday
			return true;
		}
		return false;
	}

	/**
	 * Korean Year e.g. [[2003년]]
	 */
	private function catchYear(&$x) {
		if (preg_match('~^\[?\[?([0-9]{1,4})년\]?\]?$~',$x,$matches)) {
			$x = substr('0000'.$matches[1],-4);
			return true;
		}
		// 기원전 9000년  or 기원전 9000년기  =  9000 BC
		else if (preg_match('~^\[?\[?(기원전) ([0-9]{1,4})(년)(기)?\]?\]?$~',$x,$matches)) {
			$x = "-".substr('0000'.$matches[2],-4);
			return true;
		}
		return false;
	}

	/**
	 * Korean Birthday Template e.g. 출생일|1926|3|6
	 */
	private function catchBirthday(&$x) {
		if(preg_match('~^(출생)(.*)\|([0-9]{2,4})\|([0-9]{1,2})\|([0-9]{1,2})$~',$x,$matches)) {
			$x = substr('0000'.$matches[3], -4).'-'.substr('00'.$matches[4], -2).'-'.substr('00'.$matches[5], -2);
			return true;
		}
		return false;
	}

	/**
	 * Korean Deathday Template e.g. 사망일|1926|3|6
	 */
	private function catchDeathday(&$x) {
		if(preg_match('~^(사망)(.*)\|([0-9]{2,4})\|([0-9]{1,2})\|([0-9]{1,2})\|([0-9]{2,4})\|([0-9]{1,2})\|([0-9]{1,2})\|?$~',$x,$matches)) {
			$x = substr('0000'.$matches[3], -4).'-'.substr('00'.$matches[4], -2).'-'.substr('00'.$matches[5], -2);
			return true;
		}
		else if(preg_match('~^(사망)(.*)\|([0-9]{2,4})\|([0-9]{1,2})\|([0-9]{1,2})$~',$x,$matches)) {
			$x = substr('0000'.$matches[3], -4).'-'.substr('00'.$matches[4], -2).'-'.substr('00'.$matches[5], -2);
			return true;
		}
		return false;
	}

}

