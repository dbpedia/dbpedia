<?

class MySQLDatabaseResource extends DatabaseResource{
	
		public $id;	
			
		public  function init($host, $database, $user, $pw, $newconnection){
			$this->id .= '::'.$host.'::'.$database.'::'.$user;
			 Timer::start('MySQLDatabaseResource::init::'.$database);
			 $this->connection = mysql_connect($host, $user, $pw, $newconnection)
			 	or die($this->id."\nConnection not possible: ".mysql_error());
			
			 mysql_select_db($database , $this->connection)
			 	or die($this->id."\nConnection not possible: ".mysql_error());
			 $this->log('debug', "connected to ".$host . 'database: '.$database);
			 $this->wasInititialized =true;
			 Timer::start('MySQLDatabaseResource::init::'.$database);
		}
		
		public function query($query){
			if(!$this->wasInititialized){
				$this->log('warn', $this->id." mysql resource was not intialized");
				}
			Timer::start(get_class($this)."");
			$now = microtime(true);
			$result = mysql_query($query, $this->connection);
			$needed = (microtime(true)-$now);
			$this->log('debug', "query needed ".$needed. " msec : ".$query);
			Timer::stop(get_class($this)."");
			
			return $result;
		}
		
		public function close(){
			mysql_close($this->connection);
		}
		
	
	}

