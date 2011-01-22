<?php

namespace dbpedia\util
{

/**
 * Makes sure that reads and writes of a file happen atomically by first acquiring a lock 
 * on the file. For reads, a shared lock is used. For writes, an exclusive lock is used.
 * Does not work if other users of the file don't acquire a lock before reading or writing.
 */
class LockedFile
{
    /**
     * @var string file path
     */
    private $file;
    
    /**
     * @var resource null before lock() or after unlock()
     */
    private $handle;
    
    /**
     * @param $file (string) file path
     */
    public function __construct( $file )
    {
        PhpUtil::assertString($file, 'file');
        $this->file = $file;
    }
    
    /**
     * Acquires an exclusive lock on the file.
     *  
     * WARNING: handle with care! If you forget to unlock a file, no other participants may
     * be able to access the file until your script terminates.
     * 
     * @throws Exception if file cannot be opened and locked or is already open.
     */
    public function lockForWrite()
    {
        $this->lock(true);
    }
    
    /**
     * Acquires a shared lock on the file.
     *  
     * WARNING: handle with care! If you forget to unlock a file, no other participants may
     * be able to access the file until your script terminates.
     * 
     * @throws Exception if file cannot be opened and locked or is already open.
     */
    public function lockForRead()
    {
        $this->lock(false);
    }
    
    /**
     * Acquires a lock on the file. For reads, a shared lock is used. 
     * For writes, an exclusive lock is used.
     * 
     * @param $write (boolean) get lock for writing or reading?
     * @throws Exception if file cannot be opened and locked or is already open.
     */
    private function lock( $write )
    {
        if ($this->handle !== null) throw new \Exception('file aready open');
        
        // always use 'b', as recommended by PHP manual for fopen()
        // we use 'a' instead of 'w' because we don't want to truncate the file yet
        // suppress warning, we'll check the result
        $this->handle = @fopen($this->file, $write ? 'ab' : 'rb');
        
        if ($this->handle === false)
        {
            $this->handle = null;
            throw new \Exception('could not lock ' . $this->file . ' for ' . $write ? 'writing' : 'reading');
        }
        
        if (! flock($this->handle, $write ? LOCK_EX : LOCK_SH))
        {
            $this->unlock();
            throw new \Exception('could not lock ' . $this->file . ' for ' . $write ? 'writing' : 'reading');
        }
    }
    
    /**
     * Releases a lock on the file and closes the file. Does nothing if file is not open.
     */
    public function unlock()
    {
        if ($this->handle !== null)
        {
            // note: fclose() also releases the lock
            fclose($this->handle);
            $this->handle = null;
        }
    }
    
    /**
     * Acquires an exclusive lock on the file, writes given string to file, unlocks and closes the file.
     * @param $string file contents as string
     * @throws Exception if file cannot be opened and locked or is already open.
     */
    public function lockAndWrite( $string )
    {
        $this->lockForWrite();
        
        $this->write($string);
        
        $this->unlock();
    }
    
    /**
     * Writes given string to file. Does not lock, unlock or closes the file.
     * lock() and unlock() must be called before and after this method.
     * @param $string file contents as string
     * @throws Exception if file is not open and locked.
     */
    public function write( $string )
    {
        if ($this->handle === null) throw new \Exception('file not open');
        
        // truncate file now - lock() used fopen('a')
        ftruncate($this->handle, 0);
        
        fwrite($this->handle, $string);
    }
    
    /**
     * Acquires a shared lock on the file, reads its contents, unlocks and closes the file.
     * @return file contents as string
     * @throws Exception if file cannot be opened and locked or is already open.
     */
    public function lockAndRead()
    {
        $this->lockForRead();
            
        $string = $this->read();

        $this->unlock();
        
        return $string;
    }

    /**
     * Reads file contents as string. Does not lock, unlock or closes the file. 
     * lock() and unlock() must be called before and after this method.
     * @return file contents as string
     * @throws Exception if file is not open and locked.
     */
    public function read()
    {
        if ($this->handle === null) throw new \Exception('file not open');
        
        $string = '';

        // read up to 1 MB at a time
        while (! feof($this->handle)) $string .= fread($this->handle, 1024 * 1024);
        
        return $string;
    }

}

}
