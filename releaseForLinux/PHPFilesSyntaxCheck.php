<?php
include_once './MyCommand.php';
include_once './Tools.php';
class PHPFilesSyntaxCheck{
    private $phpRoot;
    private $myPHP;
    private $ignoreFiles = array();

    public function __construct($phpRoot, $shell = null, $ignoreFiles = array()){
        $this->phpRoot = $phpRoot;
        $this->myPHP = new MyCommand($phpRoot, $shell);
        $this->ignoreFiles = $ignoreFiles;
    }

    public function syntaxCheck(array $files, $exclusiveLength){
        $result = array();
        foreach ($files as $file){
            $result = array_merge($result, $this->synaxCheckForOneFile($file, $exclusiveLength));
        }
        return empty($result) ? true : $result;
    }

    public function synaxCheckForOneFile($file, $exclusiveLength = 0){
        if (Tools::ignoreFile($file, $this->ignoreFiles)){
            return true;
        }
        $result = array();
        $localPath = substr($file, $exclusiveLength);
        if (!file_exists($file)){
            $result[substr($file, $exclusiveLength)]="file no find when running syntax check";
            return $result;
        }

        if (is_file($file)){
            if (preg_match('/\.php$/', basename($file)) == 0) return true;
            $checkResult = $this->myPHP->run_command('php -l '.$file);
            if ($checkResult['return_value'] !== 0){
                if (!empty($checkResult['error'])){
                    $error = $checkResult['error'];
                }else{
                    $error = $checkResult['response'];
                }
                $result[substr($file, $exclusiveLength)] = $error;
                return $result;
            }
            return true;
        }else{
            $fileNames = scandir($file);
            foreach ($fileNames as $fileName){
                if (preg_match("/^\..*/", $fileName) != 0) {
                    continue;
                }
                $checkResult = $this->synaxCheckForOneFile($file.DIRECTORY_SEPARATOR.$fileName, $exclusiveLength);
                if ($checkResult !==true){
                    $result = array_merge($result, $checkResult);
                }
            }
            return $result;
        }
    }
}