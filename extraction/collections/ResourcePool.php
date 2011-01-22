<?php

class ResourcePool {

/*
	static protected $resourcesByClass = array();
	static protected $resourcesById = array();
*/
	static protected $resources = array();

	public static function initResource($inifile){
		//Timer::start("a");
		$arr = parse_ini_file($inifile);
/*
		Timer::stop("a");
		Timer::start("b");
		$p = new Properties();
		$fp = fopen($inifile.'2','r');
		$p->load($fp);
		
		//print_r($p->propertyNames());
		Timer::stop("b");
		Timer::printTime(10);
		die();
*/
		if(!is_array($arr) || !count($arr)>0){
			die ( "core/ResourcePool : inifile ". $inifile ." not found\n");
		}
		
		Timer::start('ResourcePool::init::'.$class);
		$class = new ReflectionClass($arr['className']);
		$resourceInstance = $class->newInstance($arr);
		$resourceInstance->init();
		self::$resources = $resourceInstance;
		Timer::stop('ResourcePool::init::'.$class);
	
		//$resourceInstance
		//$resourceInstance->setStatus($status);
		
	}	
	
	public static function getMediaWikiApiResource($requestingclass, $language){
			$found = false;
			foreach (self::resources as $resource){
					if($resource instanceOf MediaWikiApiResource){
							if($resource->getLanguage() == $language){
								self::log('debug',$requestingclass. ' requested: MediaWikiApiResource '.$language);
								return $resource;
								}
						}
				}
			self::log('warn','appropriate Resource not found: MediaWikiApiResource, language: '.$language);
			return false;
		
		}
	
/*
	public static function getResourceByClass($class){
			return self::$resourcesByClass[$class];
		}
		
	public static function getResourceById($id){
			return self::$resourcesById[$id];
		}
*/
		
	public static function log ($lvl, $message){
		
			Logger::logComponent("resourcepool","", $lvl ,$message);
		
		}
	
	//public static	
				
		
	
}
