<?php

class Timer{
	static public $time = array();	
	static public $start = array();
	static public $startingTime;
	
	static public function init(){
			self::$startingTime = microtime(true);
		}
	
	static public function getElapsedSeconds(){
			 return  microtime(true) - self::$startingTime;
		}
	
	static public function timerLabel($component, $class, $rest){
			return "$component::".get_class($class)."::".$rest;
		}
	
	static public function start($name){
			if(isset(self::$start[$name])){
				Logger::warn("Timer: ".$name ." already started. overwriting");
				}
			self::$start[$name] = microtime(true);
		}
	
	static public function staticTimer($name, $timeToAdd){
			self::check($name);
			self::$time[$name]['total'] += $timeToAdd;
			self::$time[$name]['hits'] += 1;
			
		}
	
	static public function stop($name){
			if(!isset(self::$start[$name])){
				Logger::warn("Timer: ".$name ." was never started. ignoring");
				return;
				}
			$before = self::$start[$name];
			unset(self::$start[$name]);
			$needed =  microtime(true) - $before;
			//echo $total."\n";
			self::check($name);
			self::$time[$name]['total'] += $needed;
			self::$time[$name]['hits'] += 1;
			return $needed;
		}
		
	static public function stopAsString($name){
			$t = self::stop($name);			
			return ', needed: '.(round($t*1000,2)).' ms.';		
		}
		
	static private function check($name){
			if(!isset(self::$time[$name])){
				self::$time[$name] = array();
				self::$time[$name]['total'] = 0;
				self::$time[$name]['hits'] = 0;
				}
		
		}
		
	static public function timeToFile($statisticdir){
		    $overall = array();
			$overall['startingtime'] = self::$startingTime;
			$overall['lasttime'] = microtime(true);
			
			
			@mkdir($statisticdir);
			if(is_writable($statisticdir)){
				$fp1 = fopen($statisticdir.'/time.ser' , 'w');
				$fp2 = fopen($statisticdir.'/timeOverall.ser' , 'w');
				
				$ser1 = serialize(self::$time);
				$ser2 = serialize($overall);
				
				fwrite($fp1, $ser1);
				fwrite($fp2, $ser2);
				
				fclose($fp1);
				fclose($fp2);
			}else{
				Logger::warn('statistic dir not writable');
			}
		}	
		
	static public function printTime($precision = 2){
			$message = self::getTimeAsString($precision);
			
			if(count(self::$start)>0){
				Logger::warn("Timer: Unfinished timers:");
				foreach(array_keys(self::$start) as $key){
						Logger::warn("Timer: ".$key);
					}
				}
			Logger::info($message);
		}
	
	static function getTimeAsString($precision = 2){
			$message ="";
			ksort(self::$time);
			foreach(self::$time as $key=>$value){
				$tmp = "";
				$total = round($value['total'],$precision);
				//$total = ($total == 0)?"0.00":$total;
				$percent = self::percentage($value['total']);
				$hits = $value['hits'];
				$avg = round((($value['total']*1000)/$hits), $precision);
				
				//$message .= "total: ".$total." sec\t(".$percent.")\thits: ".$hits."\tavg: ".$avg."\t".$key."\n";
				//$tmp .= Util::deck( "total: ");
				$tmp .= Util::deck(round($total,2).' sec');
				//$tmp .= Util::deck("sec");
				$tmp .= Util::deck("".$percent."");
				//$tmp .= Util::deck("hits:");
				$tmp .= Util::deck($hits);
				//$tmp .= Util::deck("avg:");
				$tmp .= Util::deck($avg);
				$tmp .= Util::deck($key);
				
				$message .= Util::row($tmp,0);
				}
			return $message."";
		}
		
	
		
	private static function percentage($componentTime){
			$total = microtime(true) - self::$startingTime ;
			$result = round(($componentTime / $total)*100,2)."%";
/*
			echo $total."\n";
			echo $componentTime."\n";
			echo $result."\n";
*/
			return $result;
		}
}
