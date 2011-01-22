<?php

/**
 * Encapsulates a MySQL connection, does an automatic reconnect if a query
 * fails or the connection fails. Mostly necessary because we get an error
 * 'MySQL server has gone away' sometimes on Windows. Also helpful to allow
 * us to restart the MySQL server without crashing the processes that are
 * currently running.
 * 
 * Similar to the class ODBC in this folder.
 * 
 * @author Christopher Sahnwaldt
 */
class MySQL {
	
	/* mysql host name (and maybe port) */
	private /* final */ /* string */ $host;
	
	/* mysql user name */
	private /* final */ /* string */ $user;
	
	/* mysql password */
	private /* final */ /* string */ $password;

	/* database (aka catalog) to use */
	private /* final */ /* string */ $catalog;

	/* database connection */
	private /* resource */ $link;
	
	/**
	 * @param $host mysql host name (and maybe port)
	 * @param $user mysql user name
	 * @param $password mysql password
	 * @param $catalog database (aka catalog) to use
	 */
	public function __construct($host, $user, $password, $catalog) {
		$this->host = $host;
		$this->user = $user;
		$this->password = $password;
		$this->catalog = $catalog;
	}
	
	private function ping() {
		if ($this->link) {
			// Note: before MySQL 5.0.13, mysql_ping did an automatic reconnect... but not anymore...
			if (mysql_ping($this->link)) return; // everything is fine
			else mysql_close($this->link);
		} 
		$this->link = mysql_connect($this->host, $this->user, $this->password, true); 
		if (! $this->link) return; // retry
		$selected = mysql_select_db($this->catalog, $this->link);
		if (! $selected) return; // retry
		// TODO: make this configurable. Better: configure ini files correctly.
		mysql_query("SET NAMES utf8", $this->link);
	}	
	
	/**
	 * Run given query, retry if it fails. 
	 * Note: 50 sleeps with linear backoff starting at 10 seconds amount to ca 3.5 hours
	 * @param $query SQL query
	 * @param $seconds sleep interval, will be multiplied by current retry count. Default: 10
	 * @param $max max retry count. Default: 50
	 * @return result of mysql_query()
	 */
	public function query($query, $seconds = 10, $max = 50) {
		$tries = 0;
		for (;;) {
			
			$this->ping();
			
			if ($this->link) {
				$batch = mysql_query($query, $this->link);
				if ($batch) return $batch;
			}
			
			$tries++;
			$errno = mysql_errno($this->link);
			$error = mysql_error($this->link);
			
			// 2006 is 'MySQL server has gone away', 2013 is 'Lost connection to server during query'
			// See http://dev.mysql.com/doc/refman/5.1/en/error-messages-client.html
			// and http://dev.mysql.com/doc/refman/5.1/en/gone-away.html
			if ($tries > $max || ($errno !== 2006 && $errno !== 2013)) throw new Exception($error);
			
			$stack = debug_backtrace(false);
			$caller = $stack[0];
			$sleep = $seconds * $tries;
			echo "mysql error in {$caller['file']} line {$caller['line']} code $errno - sleeping $sleep seconds after try #$tries.\n";
			sleep($sleep);
		}
	}
	
	public function escape( $string ) {
		return mysql_real_escape_string($string, $this->link);
	}
}