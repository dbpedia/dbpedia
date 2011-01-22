<?php

namespace dbpedia\util
{

use dbpedia\util\StringUtil;

/**
 */
class FileProcessor
{
    /**
     * Absolute path to base dir, using forward slashes.  Never null.
     * @var string
     */
    private $baseDir;
    
    /**
     * Names (not paths) of files and directories to skip, e.g. '.svn'. 
     * If empty, all files and directories will be included. Never null.
     * @var string array
     */
    private $skipNames;
    
    /**
     * array of strings, paths of files to use, relative to base dir, using forward slashes.
     * If not given, all files and directories will be included.
     * @var string array
     */
    private $paths;
    
    /**
     * @param $baseDir must end with a directory separator (slash or backslash)
     * @param $skipNames names (not paths) of files and directories to skip, e.g. '.svn'. 
     * If not given, all files and directories will be included.
     * @param $paths array of strings, paths of files to use, relative to base dir,
     * using forward slashes. If not given, all files and directories will be included.
     */
    public function __construct( $baseDir, $skipNames = null, $paths = null )
    {
        PhpUtil::assertString($baseDir, 'base dir');
        $baseDir = str_replace('\\', '/', realpath($baseDir));
        if (! is_dir($baseDir)) throw new \InvalidArgumentException('base dir must be an existing directory, but is ' . $baseDir);
        // make sure that $baseDir ends with /
        if (! StringUtil::endsWith($baseDir, '/')) $baseDir .= '/';
        
        if ($skipNames !== null) PhpUtil::assertArray($skipNames, 'skip names');
        else $skipNames = array();
        
        if ($paths !== null) PhpUtil::assertArray($paths, 'paths');
        
        $this->baseDir = $baseDir;
        $this->skipNames = $skipNames;
        $this->paths = $paths;
    }
    
    /**
     * @param $processor callback function to process a single file. Must have two string 
     * parameters: file path (relative to base dir) and file content.
     */
    public function processFiles( $processor )
    {
        $this->processRecursive($this->baseDir, $processor);
    }

    /**
     * @param $processor callback function to process a single file. Must have two string 
     * parameters: file path (relative to base dir) and file content.
     * @param $dir string, absolute path, must be sub-directory of base dir.
     */
    private function processRecursive( $dir, $processor )
    {
        foreach(new \DirectoryIterator($dir) as $file)
        {
            // skip '.', '..' and configured names
            if ($file->isDot() || in_array($file->getFilename(), $this->skipNames, true)) continue;
            
            if ($file->isDir())
            {
                $this->processRecursive($file->getPathname(), $processor);
            }
            else if ($file->isFile())
            {
                $path = $this->relativePath($file->getPathname());
                if ($this->paths !== null && ! in_array($path, $this->paths, true)) continue;
                $source = file_get_contents($file->getPathname());
                call_user_func_array($processor, array(&$path, &$source));
            }
        }
    }
    
    /**
     * @param $path absolute path, may use backslashes
     * @return file path relative to base dir, using forward slashes
     */
    protected function relativePath( $path )
    {
        return substr(str_replace('\\', '/', $path), strlen($this->baseDir));
    }
    
}

}
