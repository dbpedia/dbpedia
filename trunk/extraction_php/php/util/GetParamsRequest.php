<?php

namespace dbpedia\util
{

class GetParamsRequest
extends HttpRequest
{
    /**
     * @param $params query params - array keys are the names, array values the values.
     * Values may be arrays. http://php.net/http_build_query is used to build the query.
     * TODO: http_build_query() may not always be the best way to deal with multi-valued
     * parameters. Maybe we should implement it here.
     */
    public function setParams( $params )
    {
        if ($this->url === null) throw new \InvalidArgumentException('set url first');
        PhpUtil::assertArray($params, 'params');
        $this->url .= '?' . http_build_query($params);
    }
}

}

