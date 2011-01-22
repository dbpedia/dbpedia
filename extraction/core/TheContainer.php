<?php

/**
 * This class is a container for all things that only nee to be initialized once
 * during runtime of the framework,
 * feel free to add anything you wish
 * 
 */

class TheContainer{
	
	private static $container = array();
	
	public static function set($label, $content){
			self::$container[$label] = $content;
		}
	public static function wasSet($label){
			return isset(self::$container[$label]);
		}
	public static function get($label){
			if(!self::wasSet($label)){
				Logger::error('TheContainer: access to '.$label.' failed, not set previously');
				}
			return self::$container[$label] ;
		}
	
	
	
}
