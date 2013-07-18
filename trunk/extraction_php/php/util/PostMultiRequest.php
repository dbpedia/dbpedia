<?php

namespace dbpedia\util
{

class PostMultiRequest
extends HttpRequest
{
    protected $fields;
    
    /**
     * See CURLOPT_POSTFIELDS on http://php.net/curl_setopt . The 'Content-Type' header should
     * not be set by any other method, cURL will set it to 'multipart/form-data'.
     * @param $fields array with the field name as key and field data as value. To post a file,
     * prepend a filename with @ and use the full path as the value. 
     */
    public function setFields( $fields )
    {
        PhpUtil::assertArray($fields, 'fields');
        $this->fields = $fields;
    }
    
    protected function configure( $ch )
    {
        parent::configure($ch);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->fields);
    }
}

}

