<?php

/**
 * Only returns true if all filters are true
 *
 */
class AndCompoundFilter
    implements IFilter
{
    private $filters;
    private $default = false; // used filters array is empty

    public function __construct($filters)
    {
        $this->filters = $filters;
    }

    public function doesAccept($value)
    {
        if(sizeof($this->filters) == 0)
            return $this->default;

        $result = true;
        foreach($this->filters as $filter)
            $result = $result && $filter->doesAccept($value);

        return $result;
    }
}
