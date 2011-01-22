<?php

class Logger{
   
   	static $silent = false;
   	//static $knownLog = array();
  	static $level = 2;
	static $destination = 'stderr'; 
	static $logpath = 'log/'; 
	static $includeTime = true;
	static $includeMemory = true;
	static $includeLevel = true;
    static $overwriteLogs = false;
	static $ini = array();
    static $overwritten = array();
	
	public static function setDestination($dest){
			self::$destination = $dest;
		}
	
	public static function configureLogger($inifile){
			$fromini = parse_ini_file($inifile);
			
			if(!(is_array($fromini)) || !(count($fromini)>0)){
				echo "Logger : inifile ". $inifile ." not found\n";
				exit;
			}else{
			self::$ini = array_merge(self::$ini, $fromini );
			self::$level = self::toInt(self::$ini['defaultlevel']);
			self::$logpath = self::$ini['logpath'];
			self::$destination = self::$ini['output'];
			self::$includeTime = self::$ini['includeTime'];
			self::$includeLevel = self::$ini['includeLevel'];
			self::$includeMemory = self::$ini['includeMemory'];
			self::$overwriteLogs = self::$ini['overwriteLogs'];
			
			}
		}
	
	public static function error($message){
			$lvl = 0;
			if(self::isLogged($lvl)){
				self::log( self::printlvl($lvl).$message);
			}
		}
	
	
	public static function warn ($message){
			$lvl = 1;
			if(self::isLogged($lvl)){
				self::log(self::printlvl($lvl).$message);
			}
		}
	
	public static function info ($message){
			$lvl = 2;
			if(self::isLogged($lvl)){
				self::log(self::printlvl($lvl).$message);
			}
		}
	
	public static function debug ($message){
			$lvl = 3;
			if(self::isLogged($lvl)){
				self::log(self::printlvl($lvl).$message);
			}
		}
	
	public static function trace ($message){
			$lvl = 4;
			if(self::isLogged($lvl)){
				self::log(self::printlvl($lvl).$message);
			}
		}
		
	
	public static function logComponent ($component, $class, $lvl ,$message){
		//$message = $component.":".$class.": ".$message;
		$message = $class.": ".$message;
		$lvl = self::toInt($lvl);
		if(!empty(self::$ini[strtolower($class)])){
			$classlvl = self::toInt(self::$ini[strtolower($class)]);
			if( self::isLoggedSpecial( $classlvl, $lvl )){
				self::log(self::printlvl($lvl).$message);
			}
		}else if(!empty(self::$ini[strtolower($component)])){
			$componentlvl = self::toInt(self::$ini[strtolower($component)]);
			if( self::isLoggedSpecial($componentlvl, $lvl )){
				 self::log(self::printlvl($lvl).$message);
			}	
		 }else{
			 if(self::isLogged($lvl)){
				self::log(self::printlvl($lvl).$message);
			 }
		 }
	}
	
	private static function toInt($lvl){
			$lvl = strtolower($lvl);
			switch($lvl){
				case 'silent': return -1;
				case 'error': return 0;
				case 'warn': return 1;
				case 'info': return 2;
				case 'debug': return 3;
				case 'trace': return 4;
				case 'nolog': return 5;
				default : return 2;
			}
		
		}
	
	private static function printlvl($lvl){
			if(self::$includeLevel){
				switch($lvl){
					case 0: return '[ERROR] ';
					case 1: return '[WARN] ';
					case 2: return '[INFO] ';
					case 3: return '[DEBUG] ';
					case 4: return '[TRACE] ';
					}
			}else {
				return '';
			}
		}
  
   	private static function isLogged($lvl){
	   		return ($lvl <= self::$level);
	   }
	 
	 private static function isLoggedSpecial($acceptedLevel, $currentLevel){
		// echo $acceptedLevel." " .$currentLevel."\n";
	   		return ($currentLevel <= $acceptedLevel);
	   }
	
	 private static function log($message){
		 	$message = trim ($message);
			if(self::$includeTime){
				$message = self::addDate($message);
				}
			if(self::$includeMemory){
				$message = self::addMemory($message);
				}
				
			if(strtolower(self::$destination)=='stderr'){
				fwrite(STDERR, $message."\n"); 
				}
			else if(strtolower(self::$destination)=='stdout'){
				echo  $message."\n"; 
				}
			else{
				if(!is_dir(self::$logpath)  ){
					mkdir(self::$logpath,0777);
					}
				self::toFile(self::$logpath.self::$destination, $message."\n");
			}
		 
		 }
  
	public static function addDate($str) {
		return "[".@date('Y-m-d H:i:s')."] ".$str; 
	}
	public static function addMemory($str) {
		$str .=  " [mem: ".round (memory_get_usage  (true )/1000 ,0)." KB | ";
		$str .=  "peak:  ".round (memory_get_peak_usage(true )/1000 ,0)." KB]";
		return $str; 
	}
	
	public static function arrayToFile($file, $array, $overwrite = false){
			
    		ob_start();
    		 print_r($array);
    		$str= ob_get_clean();
    		
    		self::toFile($file, $str."\n\n",$overwrite = false);
		}
		
	public static function logInFile($file, $str, $overwrite = false){
			if(!self::$silent)
				self::toFile($file,Logger::addDate($str)."\n",$overwrite = false);
		}
		
	public static function toFile($file, $str, $overwrite = false){
			
            if(in_array($file ,self::$overwritten )){
                $mode = 'a';
            }else{
                $mode = ($overwrite || self::$overwriteLogs)?'w':'a';
                self::$overwritten[] = $file;
            }
			
			$fp = fopen($file,$mode);
			if(false === $fp){
				Logger::warn('Logger: could not write file: '.$file);
			}else{
    			fwrite($fp, $str);
				fclose($fp);
			}
		}
	
	
		
	
	
}

