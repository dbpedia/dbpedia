<?php

/**
 * Cycles over all entries from a given MySQL-Source
 * 
 */

class MysqlIterator implements Iterator
{
    protected $row = null;
    protected $query = null;

    public function __construct($query)
    {
        if(is_string($query)) {
            $this->query = mysql_query($query);
        } else if(is_resource($query)) {
            $this->query = $query;
        }
    }

    public function key() { } // Not Implemented

    public function current()
    {
        if($this->row != null)
        {
        $PageTitle =  $this->row['page_title'];
		return $PageTitle;
        }
    }

    public function next()
    {
        $this->row = mysql_fetch_assoc($this->query);
        $PageTitle =  $this->row['page_title'];
		return $PageTitle;
    }

    public function rewind()
    {
        $this->row = mysql_data_seek($this->query, 0);
        $PageTitle =  $this->row['page_title'];
		return $PageTitle;
    }

    public function valid()
    {
        if($this->row == false) {
            return false;
        }

        return true;
    }
}

