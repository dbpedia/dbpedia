<?php
//define ("ODBC_MAX_LONGREAD_LENGTH", 8000);

class ODBC {
	var $dsn;
	var $user;
	var $pw;
	var $con;
	var $previous;
	const wait = 5;
	const cutstring = 1000;
	
public function __construct($dsn, $user, $pw){
		$this->dsn = $dsn;
		$this->user = $user;
		$this->pw = $pw;
		$this->connect();
	
	}
	
	
public static function getDefaultConnection(){
		 $dataSourceName = Options::getOption('Store.dsn');
    	 $username       = Options::getOption('Store.user');
    	 $password       = Options::getOption('Store.pw');

    	return new ODBC($dataSourceName, $username, $password);
		
	}


/*
 * Blocks until connection exists
 * 
 * */
public function connect($debug=false){
		 if (!function_exists('odbc_connect')) {
       		 Logger::warn(
       		     "Virtuoso adapter requires PHP ODBC extension to be loaded");
      	 		die;
   		 }
		odbc_close_all();
		$failedonce = false;
		while (false === ($con = @odbc_connect($this->dsn, $this->user, $this->pw))){
				Logger::warn('ODBC connect to '.$this->dsn.' failed, waiting '.self::wait.' and retrying');
				sleep(self::wait);
			};
		if($debug){
				Logger::info('ODBC connection re-established');
			}	
		$this->con = $con;
	}

public function execAsJson($query, $logComponent){
		$query= 'sparql define output:format "RDF/XML" ' .$query;
/*
		$query= 'sparql define output:format "TTL" ' .$query;
*/
/*
		echo $query;
		die;
*/
		$odbc_result = $this->exec($query, $logComponent);
		
		odbc_longreadlen ($odbc_result, ODBC_MAX_LONGREAD_LENGTH);
		odbc_fetch_row($odbc_result);
		$data=false;
		do {
			$temp = odbc_result($odbc_result, 1);
			if ($temp != null) $data .= $temp;
		} while ($temp != null);
		//=$data;
		
		$conv = new XmlConverter();
		$arr = $conv->toArray($data);
		//print_r($arr);
		//die;
		//Logger::warn('check if faster with default graph, ');
		return $arr;
	}
	
public function exec($query, $logComponent){
		
		Timer::start($logComponent.'::odbc_exec');
		$result = @odbc_exec($this->con, $query);
		Timer::stop($logComponent.'::odbc_exec');
		
		if (false === $result) {
			$errornr = odbc_error($this->con);
			$err = odbc_errormsg($this->con);
			switch ($errornr){
				case 37000: {
					Logger::warn($logComponent."::odbc_exec failed: query length = ".strlen($query)."\n".$err);
					Logger::warn($query);
					break;
				}
				//lost connection to server
				case '08S01': {
					Logger::warn($logComponent."::odbc_exec: lost connection to server, going into loop".$err);
					Logger::warn("Waiting at: ***********\n".substr($query, 0, self::cutstring));
					Logger::warn("Previous was:**********\n".$this->previous);
					do{
						
						sleep(self::wait);
						$this->connect(true);
						$result = @odbc_exec($this->con, $query);
						$errornr = odbc_error($this->con);
						Logger::warn('Currently looping last odbc_exec, waiting '.self::wait.' and retrying '.$errornr.$result);
					}while (false === $result && $errornr == '08S01' );
					break;
				}
                case 40001: {}
				case 'SR172': {
					Logger::warn($logComponent."::odbc_exec: Transaction deadlocked, going into loop".$err);
					Logger::warn("Waiting at: ***********\n".substr($query, 0, self::cutstring));
					Logger::warn("Previous was:**********\n".$this->previous);
					do{
						
						sleep(self::wait);
						$this->connect(true);
						$result = @odbc_exec($this->con, $query);
						$errornr = odbc_error($this->con);
						Logger::warn('Currently looping last odbc_exec, waiting '.self::wait.' and retrying '.$errornr.$result);
					}while (false === $result && ($errornr == 'SR172' || $errornr == 40001  ));
					break;
				}
				//query on a non existant db table
				case '42S02': {}
				case 'S0002': {
					//do nothing returning false is ok
					Logger::info('no db table found'."\n". $err);
					break;
					}
				
				default: {
					Logger::warn($logComponent."::odbc_exec failed: \n".$query."\nnumber: ".$errornr."\nerror: ".$err);
					}
				
				}

		}else{
			Logger::info( $logComponent."::SUCCESS ($result): ");
		}
		$this->setPrevious( $query);
		return $result;
	}
	
	
	
	
	/*
	 * returns the odbc statement
	 * */
	public function prepare($query, $logComponent){
		Timer::start($logComponent.'::odbc_prepare');
	 	$result = @odbc_prepare($this->con, $query);
	 	Timer::stop($logComponent.'::odbc_prepare');
		if (false === $result) {
			$errornr = odbc_error($this->con);
			$err = odbc_errormsg($this->con);
			//echo $err.$errornr;die;
			switch ($errornr){
				case 37000: {
					Logger::warn($logComponent."::odbc_prepare failed: query length = ".strlen($query)."\n".$err);
					break;
					}
				case '08S01': {
					Logger::warn($logComponent."::odbc_prepare: lost connection to server going into loop".$err);
					Logger::warn("Waiting at: ***********\n".substr($query, 0, self::cutstring));
					Logger::warn("Previous was:**********\n".$this->previous);
					do{
						Logger::warn('Currently looping last odbc_prepare, waiting '.self::wait.' and retrying');
						sleep(self::wait);
						$this->connect(true);
						$result = @odbc_prepare($this->con, $query);
						$errornr = odbc_error($this->con);
					}while (false === $result && $errornr == '08S01' );
					break;
				}
                case 40001: {}
				case 'SR172': {
					Logger::warn($logComponent."::odbc_prepare: Transaction deadlocked, going into loop".$err);
					Logger::warn("Waiting at: ***********\n".substr($query, 0, self::cutstring));
					Logger::warn("Previous was:**********\n".$this->previous);
					do{
						Logger::warn('Currently looping last odbc_prepare, waiting '.self::wait.' and retrying');
						sleep(self::wait);
						$this->connect(true);
						$result = @odbc_prepare($this->con, $query);
						$errornr = odbc_error($this->con);
					}while (false === $result && $errornr == 'SR172' );
					break;
				}
				default: {
					Logger::warn($logComponent."::odbc_prepare failed: \n".$query."\nnumber: ".$errornr."\nerror: ".$err);
					}
				
				}

		}else{
			Logger::debug( $logComponent.":: successfully prepared  ($query): ");
		}
	 $this->setPrevious( $query);
	 return $result;
	}
	
	public function setPrevious($s){
		$this->previous = substr($s,0,self::cutstring);
		}

}
