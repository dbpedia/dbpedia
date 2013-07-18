<?php
class Options {
	
	public static $config = array();
	
	public static function configureOptions($inifile){
			$arr = parse_ini_file($inifile);
			if(!is_array($arr) || !count($arr)>0){
				die ("core/Options : failed to read inifile ".$inifile.PHP_EOL);
			}else{
				self::$config = array_merge(self::$config , $arr);
			}
		}
		
	public static function getArticleConfiguration(){
			return self::_configHelper('article.');
		}
	public static function getCategoryConfiguration(){
			return self::_configHelper('category.');
		}
	public static function getRedirectConfiguration(){
			return self::_configHelper('redirect.');
		}
	private static function _configHelper($str)	{
			$result = array();
			foreach (self::$config as $key=>$value){
					if(strpos($key,$str )===0){
						$key = str_replace($str,'',$key);
						$result[$key] = strtolower($value);
						}
				}
			return $result;
		}
	
			
		
	static function isOptionSet($name){
			return isset(self::$config[$name]);
		}
	static function setOption($name, $value){
			Logger::warn('Options::filling '.$name.' with "'.$value.'" this might be a hack');
			self::$config[$name] = $value;
		}
	static function setLanguage( $value){
			self::$config['language']=$value;
		}
	
	static function setProcessID($id){
			self::$config['processID'] = $id;
		}
		
	static function getFileOptionWithID($name){
			$filename = self::getOption($name);
			$append = "";
			if(self::isOptionSet('processID')){
				$append = self::$config['processID'];
				}
			return $filename.$append;
		}
	
	
	 static public function getOption($name){
			$old = error_reporting(E_ALL | E_STRICT);
			if(!isset(self::$config[$name])){
			        // TODO: throw exception instead.
					Logger::error ('Option '.$name.' not set returning whatever');
				}
			error_reporting($old);
			return self::$config[$name];
		
		}
	
	
	
	}
