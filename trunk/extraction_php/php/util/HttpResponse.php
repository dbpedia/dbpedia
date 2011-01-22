<?php

namespace dbpedia\util
{

/**
 */
class HttpResponse
{
    private $log;

    /** curl error string, empty if no curl error occurred */
    private $error;
    
    /** HTTP status code, e.g. 404, zero if no http response was received */
    private $code;
    
    /** HTTP reason phrase, e.g. 'Not Found', empty if no http response was received */
    private $reason;
    
    /** HTTP headers, empty if no http response was received */
    private $headers;
    
    /** HTTP body, as returned by curl_exec(), empty if no http response was received */
    private $body;
    
    /** Content-Type: of downloaded object, NULL indicates server did not send valid Content-Type: header */
    private $type; 
    
    /** Total transaction time in seconds for last transfer */
    private $time;

    /**
     */
    public function __construct( $curl )
    {
        $this->log = \dbpedia\core\DBpediaLogger::getLogger(__CLASS__);
        $callback = new ResponseHeaderHandler();
        curl_setopt($curl, CURLOPT_HEADERFUNCTION, array($callback, 'handle'));
        $this->body = curl_exec($curl);
        $this->error = curl_error($curl);
        $this->code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $this->reason = $callback->getReason();
        $this->headers = $callback->getHeaders();
        $this->type = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
        $this->time = curl_getinfo($curl, CURLINFO_TOTAL_TIME);
    }
    
    /**
     * @return curl error string, empty if no curl error occurred
     */
    public function getError()
    {
        return $this->error;
    }
    
    /**
     * @return HTTP status code, e.g. 404, zero if no http response was received
     */
    public function getCode()
    {
        return $this->code;
    }
    
    /**
     * @return HTTP reason phrase, e.g. 'Not Found', empty if no http response was received
     */
    public function getReason()
    {
        return $this->reason;
    }
    
    /**
     * @return HTTP headers, empty if no http response was received. array keys are the names,
     * array values the values.
     */
    public function getHeaders()
    {
        return $this->headers;
    }
    
    /**
     * @return HTTP body, as returned by curl_exec(), empty if no http response was received
     */
    public function getBody()
    {
        return $this->body;
    }
    
    /**
     * @return content type of downloaded object, null indicates server did not send valid Content-Type: header
     * See CURLINFO_CONTENT_TYPE on http://php.net/curl_getinfo
     */
    public function getType()
    {
        return $this->type;
    }
    
    /**
     * @return Total transaction time in seconds for last transfer
     * See CURLINFO_TOTAL_TIME on http://php.net/curl_getinfo
     */
    public function getTime()
    {
        return $this->time;
    }
    
}

class ResponseHeaderHandler
{
    private $log;

    private $first = true;
    
    private $reason;
    
    /** name of last header */
    private $name;
    
    private $headers;
    
    public function __construct()
    {
        $this->log = \dbpedia\core\DBpediaLogger::getLogger(__CLASS__);
    }
    
    /**
     * The 'reason phrase', e.g. 'Not Found' for status code 404. May be empty. 
     */
    public function getReason()
    {
        return $this->reason;
    }
    
    public function getHeaders()
    {
        return $this->headers;
    }
    
    /**
     * TODO: this fails in all kinds of ways if the HTTP headers are malformed.
     */
    public function handle( $ch, $header )
    {
        if ($this->first)
        {
            // The previous header lines may have been for 100 Continue.
            // Now there is another header, so we remove the old lines.
            $this->headers = array();
            
            // See http://www.w3.org/Protocols/rfc2616/rfc2616-sec6.html#sec6.1
            // and http://www.w3.org/Protocols/rfc2616/rfc2616-sec2.html#sec2.2
            
            // Three parts separated by single spaces, for example 'HTTP/1.1 404 Not Found'
            $parts = explode(' ', $header, 3);
            $this->reason = trim($parts[2]);
            
            $this->first = false;
        }
        else if (strlen(trim($header)) === 0)
        {
            // An empty line (only line break) separates headers and body.
            // These header lines may have been for 100 Continue - if there is
            // another header, it will be the first again.  
            $this->first = true;
        }
        else
        {
            // See http://www.w3.org/Protocols/rfc2616/rfc2616-sec4.html#sec4.2
            // and http://www.w3.org/Protocols/rfc2616/rfc2616-sec2.html#sec2.2
            
            // if the line starts with space or tab, it continues the previous line
            if (preg_match('/^[ \t]+/', $header))
            {
                $this->headers[$this->name] .= ' ' . trim($header); 
            }
            else
            {
                $parts = explode(':', $header, 2);
                
                $this->name = $parts[0];
                $value = trim($parts[1]);
                
                // multiple values for the same name are equivalent to comma-separated values
                if (isset($this->headers[$this->name]))
                {
                    $this->headers[$this->name] .= ',' . $value;
                }
                else
                {
                    $this->headers[$this->name] = $value;
                }
            }
        }
        
        return strlen($header);
    }
}

}