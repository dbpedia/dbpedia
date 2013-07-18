<?php

/**
 * Iterates over results of SQL queries. Database access is implemented in sub-classes.
 * 
 * @author Christopher Sahnwaldt <christopher@sahnwaldt.de>
 */
abstract class AbstractSqlIterator implements Iterator
{
	/* number of first row to fetch, zero-based */
	private /* final */ /* int */ $offset;
	
	/* number of rows to fetch */
	private /* final */ /* int */ $limit;
	
	/* batch size, for performance tuning */
	private /* final */ /* int */ $batch;
	
	/** current result rows, null before start or after end has been reached */
	private /* result set */ $rows = null;
	
	/* current value, null before start or after end has been reached */
	private /* string */ $value = null;
	
	/* index of current value, one-based */
	private /* int */ $count = 0;
	
	/**
	 * @param $language 
	 * @param $offset number of first row to fetch, zero-based
	 * @param $limit maximum number of rows to fetch
	 * @param $batch batch size, for performance tuning
	 */
	public function __construct($language, $offset = 0, $limit = PHP_INT_MAX, $batch = PHP_INT_MAX) {
		
		if (! is_numeric($offset) || $offset < 0 || ! is_numeric($limit) || $limit < 0 || ! is_numeric($batch) || $batch < 0) throw new InvalidArgumentException("offset, limit and batch must be non-negative numbers, got offset [$offset], limit [$limit], batch [$batch]");
		
		$this->offset = $offset;
		$this->limit = $limit;
		$this->batch = $batch;
		
		if ($limit == 0) return; // user wants nothing - ok, let's do nothing 
		
		$this->init($language);
	}
	
	// ---------------------------------------------------------------------------------------------
	// public functions implementing iterator interface
	// ---------------------------------------------------------------------------------------------
	
	/**
	 * rewind to first row.
	 */
	public final function rewind() {
		$this->count = 0;
		
		// fetch first line of first batch 
		$this->nextBatch();
		$this->nextRow();
	}	

	/**
	 * advance to next row.
	 */
	public final function next() {
		if ($this->value === null) return; // we're after the end (or before the start)

		// try next row from current batch
		$this->nextRow();
		if ($this->value !== null) return;

		// reached end of current batch - try next one 
		$this->nextBatch();
		$this->nextRow();
	}
	
	/**
	 * @return do we have a current value?
	 */
	public final function valid() {
		return $this->value !== null;
	}
	
	/**
	 * @return current index, one-based
	 */
	public final function key() {
		return $this->count;
	}

	/**
	 * @return current value, if any
	 */
	public final function current() {
		return $this->value;
	}
	
	// ---------------------------------------------------------------------------------------------
	// protected abstract methods that must be implemented by sub-classes
	// ---------------------------------------------------------------------------------------------
	
	/** 
	 * Initialize database access.
	 * @param $language
	 * @return void
	 */
	protected abstract function init($language);

	/**
	 * Fetch rows from database. All returned rows will be processed, even if there are more than
	 * given limit.
	 * @param $offset result offset, zero-based
	 * @param $limit result size limit
	 * @return result set
	 */
	protected abstract function rows($offset, $limit);
	
	/**
	 * fetch next row from result set.
	 * @param $rows result set, as returned by $this->rows()
	 * @return next row from given result set (usually as an array)
	 */
	protected abstract function row($rows);
	
	/**
	 * free result set.
	 * @param $rows result set, as returned by $this->rows()
	 * @return void
	 */
	protected abstract function free($rows);
	
	/**
	 * fetch value from current row.
	 * @param $row row data as returned by $this->row() (usually an array)
	 * @return value for given row
	 */
	protected abstract function value($row);

	// ---------------------------------------------------------------------------------------------
	// private functions that do the actual work
	// ---------------------------------------------------------------------------------------------
	
	/**
	 * If row count has reached limit, set end markers. Otherwise, load next batch 
	 * from database and store it in $this->rows.
	 * @return nothing
	 */
	private function nextBatch() {
		if ($this->count >= $this->limit) {
			$this->end();
			return;
		}
		
		$offset = $this->offset + $this->count;
		$limit = min($this->limit - $this->count, $this->batch);
		$this->rows = $this->rows($offset, $limit);
	}

	/**
	 * Fetch next row from current batch. If there are no more rows in current batch,  
	 * set end markers. Otherwise, get value from row, store it in $this->value, 
	 * and increment row count.
	 * @return nothing
	 */
	private function nextRow() {
		if ($this->rows === null) return; // we're after the end (or before the start)
		$row = $this->row($this->rows);
		if ($row === false) {
			$this->end();
		} else {
			$this->count++;
			$this->value = $this->value($row);
		}
	}
	
	/**
	 * If there is a current batch, call $this->free(). 
	 * Set end markers: set $this->rows and $this->value to null. 
	 * @return nothing
	 */
	private function end() {
		if ($this->rows) $this->free($this->rows);
		$this->rows = null; // gc
		$this->value = null;
	}
	
}
