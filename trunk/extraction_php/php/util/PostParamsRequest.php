<?php

namespace dbpedia\util
{

class PostParamsRequest
extends HttpRequest
{
    protected $body;
    
    /**
     * @param $params query params - array keys are the names, array values the values.
     * Values may be arrays. http://php.net/http_build_query is used to build the query.
     * TODO: http_build_query() may not always be the best way to deal with multi-valued
     * parameters. Maybe we should implement it here.
     */
    public function setParams( $params )
    {
        PhpUtil::assertArray($params, 'params');
        $this->body = http_build_query($params);
    }
    
    protected function configure( $ch )
    {
        parent::configure($ch);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->body);
    }
    
}

}

