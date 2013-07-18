<?php

/*
 * 
 * from http://de3.php.net/manual/de/class.iterator.php
string(18) "meinIterator::rewind"
string(17) "meinIterator::valid"
string(19) "meinIterator::current"
string(15) "meinIterator::key"
int(0)
string(12) "erstesElement"

string(16) "meinIterator::next"
string(17) "meinIterator::valid"
string(19) "meinIterator::current"
string(15) "meinIterator::key"
int(1)
string(13) "zweitesElement"

string(16) "meinIterator::next"
string(17) "meinIterator::valid"
string(19) "meinIterator::current"
string(15) "meinIterator::key"
int(2)
string(11) "letztesElement"

string(16) "meinIterator::next"
string(17) "meinIterator::valid"

*/


class LiveUpdateIterator implements Iterator
{
    protected $hasNext = false;
	
	
	private $path = '' ;
	private $key = '' ;
	private $debug_delete_file = false;
	private $currentArticleFile;
	private $semaphoreID;
	
    public function __construct($path,  $currentArticleFile, $debug_delete_file)
    {
		$this->path = $path;
		$this->debug_delete_file = $debug_delete_file;
		$this->currentArticleFile = $currentArticleFile;
		$this->semaphoreID = sem_get(OAIRECORDFILES);
	}
	
	
    public function key() {
		return $this->key;
		 } // Not Implemented

	/*
	 * splits the current file 
	 * */

    public function current()
    {
			Timer::start('iterator::'.get_class($this).'::current');
     		$content = $this->get_next_record();
			$separator = "**********\n";
			//split the file at the separator
			$meta = substr($content, 0, strpos($content,$separator));
			$source = substr(strstr($content, $separator),strlen($separator) );
			Logger::info($meta);
			
			$fp = fopen($this->currentArticleFile, "w");
			fwrite($fp, $meta);
			fclose($fp);
			
			$fp = fopen($this->currentArticleFile, "r");
					
			$p = new Properties();
			$p->load($fp);
			$meta =array();
			$names = $p->propertyNames();

			foreach($names as $key){
				$meta[$key] = $p->getProperty($key);
				}
			fclose($fp);

			$source = html_entity_decode($source);
			
			$fp = fopen($this->currentArticleFile, "w");
			fwrite($fp, $source);
			fclose($fp);
			Timer::stop('iterator::'.get_class($this).'::current');
			
			//$meta['title'] = urldecode($meta['title']);
			$meta['pageTitle'] = urldecode($meta['pageTitle']);
			$this->key = $meta['pageTitle'];
//			return urldecode($pageID);
			return $meta;
    }
	
	
/*
* Checks for next article
* if at least one file is found sets hasNext to true
*/
    public function next() { 
			

			Timer::start('iterator::'.get_class($this).'::next');
			$files=array();
			if ($handle = opendir($this->path)) { 
				while (false !== ($file = readdir($handle))) { 
					if ($file != "." && $file != "..") {
						Timer::stop('iterator::'.get_class($this).'::next');
						$this->hasNext = true;
						return ;
						
					}
				} 
			}
			Timer::stop('iterator::'.get_class($this).'::next');
			$this->hasNext = false;
			return ;
			
		
		}

    public function rewind() { 
		
			$this->next();
		}

	/*
	 * Checks if next Article is available as File
	 * */
    public function valid()
    {
		
		return $this->hasNext;
			
      	 
    }
	
	
	
		
	
	
	private function get_next_record(){
		$directory = $this->path;
		sem_acquire($this->semaphoreID);
		if(Options::getOption('fastFileHandling')){
			$file = $this->get_any_file($directory);
			}
		else {
			$file = $this->get_oldest_file($directory);
			}
		Logger::info(get_class($this).": found file: ".$file);
		$ret = file_get_contents($directory.$file);
		if($this->debug_delete_file == false)unlink($directory.$file);
		sem_release($this->semaphoreID);
		return $ret;
		//return $ret;
	}
	
	private function get_any_file($directory) {
		
			if ($handle = opendir($directory)) { 
			while (false !== ($file = readdir($handle))) { 
				if ($file != "." && $file != "..") {
					closedir($handle);
					return $file; 
					
					}
				} 
		
			}
		}


	private function get_oldest_file($directory) { 
		if ($handle = opendir($directory)) { 
			while (false !== ($file = readdir($handle))) { 
				if ($file != "." && $file != "..") {
					$files[] = $file; 
					
				}
			} 
			
			
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
	
	
	
}

