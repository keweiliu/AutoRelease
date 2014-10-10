<?php
include_once './Tools.php';
class BuildZip{
    public static function createZip($outZipPath){
        if (is_dir($outZipPath)){
            return false;
        }
        $z = new ZipArchive();
        if (file_exists($outZipPath)){
            unlink($outZipPath);
        }
        $z->open($outZipPath,ZipArchive::CREATE);
        return $z;
    }

    public static function filesToZip($file, &$zipFile, $exclusiveLength, $ignoreFiles = array()) {
        $result = true;
        if (Tools::ignoreFile($file, $ignoreFiles)){
            return true;
        }
        if (!is_dir($file)){
            // Remove prefix from file path before add to zip.
            $localPath = substr($file, $exclusiveLength);
            $result = $zipFile->addFile($file, $localPath);
            return $result;
        }
        $handle = opendir($file);
        while (false !== $f = readdir($handle)) {
            if (preg_match("/^\..*/", $f) == 0) {
                $filePath = $file.DIRECTORY_SEPARATOR.$f;
                if (is_dir($filePath)) {
                    $localPath = substr($filePath, $exclusiveLength);
                    if (Tools::ignoreFile($filePath, $ignoreFiles)){
                        continue;
                    }
                    // Add sub-directory.
                    $zipFile->addEmptyDir($localPath);
                }
                $result = $result && self::filesToZip($filePath, $zipFile, $exclusiveLength, $ignoreFiles);
            }
        }
        closedir($handle);
        return $result;
    }
}