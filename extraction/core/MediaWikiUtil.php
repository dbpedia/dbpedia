<?php

/**
 * Instances of this class are bound to a certain mediawiki uri.
 * This is because decoding article names depends on the known templates there.
 *
 */
class MediaWikiUtil
{
    private $idToNamespace;
    //private $namespaces; // this array is array_values($this->idToNamespace)

    private static $baseUriToInstance = array();


    /**
     * baseWikiUri: e.g. http://en.wikipedia.org/wiki/
     * (the trailing slash is currently mandatory)
     * @param <type> $baseWikiUri
     * @return <type>
     */
    public static function getInstance($baseWikiUri)
    {
        if(array_key_exists($baseWikiUri, self::$baseUriToInstance))
            return self::$baseUriToInstance[$baseWikiUri];

        $result = new MediawikiUtil($baseWikiUri);
        self::$baseUriToInstance[$baseWikiUri] = $result;
        
        return $result;
    }

    private function __construct($baseWikiUri)
    {
        $this->idToNamespace =
            Util::retrieveNamespaceMappings($baseWikiUri . "Special:Export/");
        //$this->namespaces = array_values($this->idToNamespace);
    }

    public function toCanonicalWikiCase($name)
    {
        return Util::toCanonicalWikiCase($name, $this->idToNamespace);
    }



}
