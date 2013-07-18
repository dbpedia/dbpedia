<?php

abstract class AbstractMySqlIterator extends AbstractSqlIterator {

	/** database connection */
	private /* MySQL */ $mysql;
	
	/** SQL template for sprintf */
	private /* string */ $template;
	
	// ---------------------------------------------------------------------------------------------
	// protected methods implementing abstract AbstractSqlIterator methods
	// ---------------------------------------------------------------------------------------------
	
	/** 
	 * Initialize database access and SQL template.
	 */
	protected function init($language) {
		include('databaseconfig.php');
		$catalog = $this->catalog($dbprefix, $language);
		$this->mysql = new MySQL($host, $user, $password, $catalog);
		
		$this->template = $this->template($language);
	}

	/**
	 * fetch rows from database.
	 */
	protected function rows($offset, $limit) {
		$sql = sprintf($this->template, $offset, $limit);
		return $this->mysql->query($sql);
	}
	
	/**
	 * fetch next row from result set.
	 */
	protected function row($rows) {
		return mysql_fetch_assoc($rows);
	}
	
	/**
	 * free result set.
	 */
	protected function free($rows) {
		mysql_free_result($rows);
	}
	
	// ---------------------------------------------------------------------------------------------
	// protected abstract methods that must be implemented by sub-classes
	// ---------------------------------------------------------------------------------------------
	
	/**
	 * @param $dbprefix
	 * @param $language
	 * @return name of db catalog (aka database) to use
	 */
	protected abstract function catalog($dbprefix, $language);
	
	/**
	 * @param $language
	 * @return SQL string, may contain sprintf placeholders for offset and limit
	 */
	protected abstract function template($language);
	
	// declared in AbstractSqlIterator
	// protected abstract function value($row);
}
