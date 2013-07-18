<?php
namespace dbpedia\destinations
{

use dbpedia\util\PhpUtil;

class FileQuadDestination implements QuadDestination
{
    private $file;
    
    private $handle;
    
    public function __construct( $file )
    {
        PhpUtil::assertString($file, 'file');
        $this->file = $file;
    }
    
    public function open()
    {
        $this->handle = fopen($this->file, 'wb');
    }
    
    public function addQuad( $quad )
    {
        if (! isset($this->handle)) throw new \Exception('file not open - call open() first');
        fwrite($this->handle, $quad . PHP_EOL);
    }
    
    public function close()
    {
        fclose($this->handle);
        $this->handle = null;
    }
}
}
