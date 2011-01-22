<?php
error_reporting(1234);
$base = "/opt/data/SPARUL/";
$who = $_REQUEST['p'];
$debug = $_REQUEST['debug'];
//echo "debug".$debug;
if(!isset($who))die ("Please give correct request parameters");
$path = $base.$who;
echo $path."\n";
echo hasNext($path)."aa\n";
	die;
if(hasNext($path)){
	$out =  get_next_record($path."/");
	echo $out;
	die();
	
}
else die;

function hasNext($path){
			$files=array();
			if ($handle = opendir($path)) { 
				while (false !== ($file = readdir($handle))) { 
					if ($file != "." && $file != "..") {
						$files[] = $file; 
						//print_r($files);
					}
				} 
			}
			
			
			
			return (count($files)>0);
		
			
}	

	function get_next_record($path){
	//	echo $path;
		$file = get_oldest_file($path);
		//echo "oldest".$file."\n";
		$ret = file_get_contents($path.$file);
		//echo $path.$file;
		
		//echo $path.$file."\n";
		unlink($path.$file);
		
		return $ret;
		//return $ret;
	}


	 function get_oldest_file($directory) { 
		if ($handle = opendir($directory)) { 
			while (false !== ($file = readdir($handle))) { 
				if ($file != "." && $file != "..") {
					$files[] = $file; 
					
				}
			} 
		//print_r($files);
			
			foreach ($files as $val) { 
				if (is_file($directory.$val)) { 
					$file_date[$val] = filemtime($directory.$val); 
				} 
			} 
		} 
		closedir($handle); 
		asort($file_date, SORT_NUMERIC); 
		reset($file_date); 
		$oldest = key($file_date); 
		return $oldest; 
	}//end get_oldest_file
	

