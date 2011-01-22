<?php

namespace dbpedia\util
{

class HttpRequest
{
    protected $verbose;
    
    protected $url;
    
    protected $headers = array();
    
    /**
     * Path to file to hold received cookies
     */
    protected $cookieJar;

    /**
     * Path to file that holds cookies to be sent with the request
     */
    protected $cookieFile;
    
    public function setUrl( $url )
    {
        PhpUtil::assertString($url, 'url');
        // TODO: validate URL here...
        $this->url = $url;
    }
    
    /**
     * @param $verbose true to output verbose information. cURL writes output to stderr.
     * Default is false. See CURLOPT_VERBOSE on http://php.net/curl_setopt .
     */
    public function setVerbose( $verbose )
    {
        PhpUtil::assertBoolean($verbose, 'verbose');
        $this->verbose = $verbose;
    }
    
    /**
     * @param $enableCookieJar true to save cookies
     * @param $tmpDir Directory for temporary cookie jar file (defaults to /tmp)
     * Default is false. See CURLOPT_COOKIEJAR on http://php.net/curl_setopt .
     * @returns Path to cookie jar file
     */
    public function enableCookieJar( $enableCookieJar, $tmpDir = "/tmp")
    {
        PhpUtil::assertBoolean($enableCookieJar, 'enableCookieJar');
        if ($enableCookieJar) {
	        $this->cookieJar = tempnam($tmpDir, "cookiejar");
	        return $this->cookieJar;
        } else {
        	unset($this->cookieJar);
        }
    }
    
    /**
     * @param $cookieFile The name of the file containing the cookie data.
     * See CURLOPT_COOKIEFILE on http://php.net/curl_setopt .
     */
    public function setCookieFile( $cookieFile )
    {
        PhpUtil::assertString($cookieFile, 'cookieFile');
        $this->cookieFile = $cookieFile;
    }    

    /**
     * A value in the given array replaces the value for the same key that may have been
     * set by other methods. If another method set a value for a header key that does not
     * occur in the given array, that value is not removed or replaced.
     * @param $headers http headers - array keys are the names, array values the values. 
     */
    public function setHeaders( $headers )
    {
        PhpUtil::assertArray($headers, 'headers');
        $this->headers = array_merge($this->headers, $headers);
    }
    
    public function execute()
    {
        $this->validate();
        
        $curl = curl_init();
        
        $this->configure($curl);
        
        $response = new HttpResponse($curl);
        
        curl_close($curl);
        
        return $response;
    }
    
    protected function validate()
    {
        if ($this->url === null) throw new \InvalidArgumentException('url not set');
    }
    
    protected function configure( $curl )
    {
        curl_setopt($curl, CURLOPT_VERBOSE, $this->verbose);
        
        curl_setopt($curl, CURLOPT_URL, $this->url);
        
        $headers = array();
        foreach ($this->headers as $name => $value)
        {
            $headers[] = $name . ': ' . $value;
        }
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        
         // we don't like 30X
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
        
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        
        // TODO: do we need / want BINARYTRANSFER??? 
        // curl_setopt($curl, CURLOPT_BINARYTRANSFER, true);
        
        if (isset($this->cookieJar)) {
            curl_setopt($curl, CURLOPT_COOKIEJAR, $this->cookieJar);
        }
        
        if (isset($this->cookieFile)) {
            curl_setopt($curl, CURLOPT_COOKIEFILE, $this->cookieFile);
        }		
    }
    
}

}

