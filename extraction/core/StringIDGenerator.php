<?php

class StringIDGenerator{
	
	public static $char_list = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890";
	public static $l;
	public static $digits = 15;
	public static $current = array();
	public static $file ;
	public static $previewcounter = 0;
	public static $prefetch = 100;
	public static $previewarray=array();
	public static $semaphoreID;
	public static $initCalled = false;

public static function init($file, $prefetch = 100){
		self::$prefetch = $prefetch;
		self::$semaphoreID = sem_get(STRINGIDFILE);
		sem_acquire(self::$semaphoreID);
		self::$file = $file;
		self::$l = strlen(self::$char_list);
		if(file_exists($file)){
			self::backup($file);
			self::$current = unserialize(file_get_contents($file));
			}
		else{
			for($x=0;$x<self::$digits;$x++){
				self::$current[$x]=0;
				self::save(self::$current);
				}
			
			}
		self::validate(self::$current);
		self::generatePreview();
		self::$initCalled = true;
		sem_release(self::$semaphoreID);
		
		
	}
	
public static function nextID(){
		if(!self::$initCalled){
			Logger::error('StringIDGenerator not initialized ');
			die;
			}
		$ret = '';
		for($x=0;$x<self::$digits;$x++){
			$ret .= self::$char_list[self::$current[$x]];
			}
		
		if(self::$previewcounter < self::$prefetch){
			self::$current = self::plusone(self::$current);
			self::$previewcounter++;
		}else{
			sem_acquire(self::$semaphoreID);
			self::$current = self::plusone(self::$current);
			self::generatePreview();
			sem_release(self::$semaphoreID);
		}
		return $ret;	
	}
	
private static function validate($arr){
		if(count($arr)!=self::$digits){
			Logger::error('StringIDGenerator corrupted file, use recent backup, see files');
			die();
			}
		
	}	

private static function generatePreview(){
		self::$previewcounter = 0;
		$previewarray = self::plusX(self::$prefetch, self::$current);
		self::save($previewarray );
		self::backup(self::$file);
	}
private static function backup($file){
		$c = file_get_contents($file);
		file_put_contents($file.time(),$c);
	}
	
private static function plusX($x, $arr){
		
		for($a=0;$a<$x;$a++){
			$arr = self::plusone($arr);
		}
		return $arr;
	}
	
public static function test(){
	 	print_r(self::$current);
		echo self::nextID();
		echo self::nextID();
		echo self::nextID();
		echo self::nextID();
		echo self::nextID();
		print_r(self::$current);
		//print_r(self::plusX(100,self::$current));
		//print_r(self::$current);
	}
	
private static function plusone ($arr){
		for($x=0;$x<self::$digits;$x++){
				$arr[$x]++;
				if($arr[$x]==self::$l){
					$arr[$x]=0;
				}else{
					break;		
				}
			}
		return $arr;
	}

private static function save($arr){
				Timer::start('StringIDGenerator::save');
				$fp = fopen(self::$file , 'w');
				if(false === $fp){
					Logger::error('StringIDGenerator.php:  file not writable: '. self::$file);
					die;
					}
				$ser = serialize($arr);
				fwrite($fp, $ser);
				fclose($fp);
				Timer::stop('StringIDGenerator::save');
	}
	
	
}
