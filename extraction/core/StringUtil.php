<?php

/**
 * The Util class offers  String manipulation functions t
 *

 **/

class StringUtil {

	
	
	public static function startsWith($haystack, $needle){
		// Recommended version, using strpos
		return strpos($haystack, $needle) === 0;
	}
	
	public static function endsWith($haystack, $needle){
		$start = strlen($haystack) - strlen($needle);
		return strpos($haystack, $needle, $start) === $start;
	}
	public static function deck($in, $space = 0){
		$w = str_repeat('&nbsp;',$space);
		return '<td>'.$w .$in.$w .'</td>';
	}
	public static function row($in, $space = 0){
		$w = str_repeat('&nbsp;',$space);
		return '<tr>'.$w.$in.$w.'</tr>'."\n";
	}
	
	
	
}
