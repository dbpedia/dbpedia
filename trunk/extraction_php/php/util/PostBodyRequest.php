<?php

namespace dbpedia\util
{

class PostBodyRequest
extends HttpRequest
{
    protected $body;
    
    /**
     * @param $body HTTP POST body data
     * @param $type content type, e.g. 'text/xml; charset=UTF-8'. Replaces any value for the
     * 'Content-Type' header that may have been set by another method.
     */
    public function setBody( $body, $type )
    {
        PhpUtil::assertString($body, 'body');
        PhpUtil::assertString($type, 'type');
        $this->body = $body;
        $this->headers['Content-Type'] = $type;
    }
    
    protected function configure( $ch )
    {
        parent::configure($ch);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->body);
    }
}

}

