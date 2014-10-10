<?php 
include_once './Tools.php';

class BuildFileSums{
    /**
     *
     * generate FileSums file.
     * @param $filePaths Arrya of file paths. [filePath]=>[root]
     */
    public static function generateFileSums($outPath, array $filePaths, $ignoreFiles = array()){
        if (!file_exists(dirname($outPath))){
            mkdir(dirname($outPath));
        }else{
            if (!chmod(dirname($outPath), 0777)){
                echo "Changes file(dirname($outPath)) mode fail.Please confirm parent folder have save permission<br>";
            }
        }
        $fileSums = array();
        foreach ($filePaths as $filePath => $root){
            $fileSums = array_merge($fileSums, self::getFileSums($filePath, $root, $ignoreFiles));
        }
        $content="<?php
class FileSums{
    public static function getHashes()
    {
        return array(\n";
        foreach ($fileSums as $key => $value){
            $content .= "       '$key' => '$value',\n";
        }
        $content .= "       );
    }
}";

        $file = fopen($outPath, 'w');
        chmod($outPath, 0777);
        $result = fwrite($file, $content);
        fclose($file);
        return $result;
    }

    public static function getFileSums($dirPath, $root = '', &$ignoreFiles = array()){
        $fileSums = array();
        if (!file_exists($dirPath)){
            return $fileSums;
        }
        if (Tools::ignoreFile($dirPath, $ignoreFiles)){
            return $fileSums;
        }

        if (is_file($dirPath)){
            $key = substr($dirPath, strlen($root)+1);
            $fileSums[$key] = self::getFileContentsHash(file_get_contents($dirPath));
        }else{
            $fileNames = scandir($dirPath);
            foreach ($fileNames as $fileName){
                if ($fileName == '.' || $fileName == '..' || $fileName == 'FileSums.php'){
                    continue;
                }
                $fileSums = array_merge($fileSums, self::getFileSums($dirPath.DIRECTORY_SEPARATOR.$fileName, $root, $ignoreFiles));
            }
        }
        return $fileSums;
    }

    public static function getFileContentsHash($contents)
    {
        $contents = str_replace("\r", '', $contents);
        return md5($contents);
    }
}