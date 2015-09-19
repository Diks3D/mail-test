<?php

namespace App\Model;

/**
 * Manages uploaded files
 */
class FileStorage
{
    protected $storePath = null;
    
    /**
     * Create new instance and set storage path
     * 
     * @param string $path
     */
    public function __construct($path) {
        if($this->checkStorage($path)){
            $this->storePath = $path;
        }
    }
    
    /**
     * Check access for file system
     * 
     * @param type $path
     * @throws \Exception
     */
    protected function checkStorage($path)
    {
        if(!is_dir($path)){
            $this->createStorage($path);
        }
        $testFileName = 'file_test.txt';
        if(!touch($path . DIRECTORY_SEPARATOR . $testFileName)){
            throw new \Exception('Access to the file system is restricted');
        }
        if(!unlink($path . DIRECTORY_SEPARATOR . $testFileName)){
            throw new \Exception('Insufficient access rights to the file system');
        }
        return true;
    }
    
    /**
     * Create directory for file storage
     * 
     * @param string $path
     * @throws \Exception
     */
    public function createStorage($path)
    {
        if(!mkdir($path, 0775, true)){
            throw new \Exception('Can not create storage directory');
        }
    }
    
    /**
     * Save uploaded file as copy from tmp file and return real filename as id
     * 
     * @param string $tmpUploadedPath
     * @return string
     * @throws \Exception
     */
    public function addFile($tmpUploadedPath)
    {
        $fileId = md5(time() . rand(0, 20));
        
        $result = copy($tmpUploadedPath, $this->storePath . DIRECTORY_SEPARATOR . $fileId);
        if(!$result){
            throw new \Exception('Can not save uploaded file');
        }
        
        return $fileId;
    }
    
    /**
     * Check and return full file path by it id
     * 
     * @param type $fileid
     * @return string|boolean
     */
    public function getFile($fileid)
    {
        $fullPath = $this->storePath . DIRECTORY_SEPARATOR . $fileid;
        if(is_file($fullPath)){
            return $fullPath;
        }
        
        return false;
    }
    
    /**
     * remove selected file by it id
     * 
     * @param string $fileid
     */
    public function removeFile($fileid)
    {
        unlink($this->storePath . DIRECTORY_SEPARATOR . $fileid);
    }
    
    /**
     * Remove all files in storage
     */
    public function clearStorage()
    {
        $fileList = scandir($this->storePath);
        foreach ($fileList as $fileid){
            if(!in_array($fileid, array('.', '..'))){
                $this->removeFile($fileid);
            }
        }
    }
}