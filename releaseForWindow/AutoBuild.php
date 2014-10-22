<?php
include_once './BuildFileSums.php';
include_once './BuildZip.php';
include_once './MyGit.php';
include_once './KLogger.php';
include_once './Tools.php';
include_once './PHPFilesSyntaxCheck.php';

set_time_limit(0);
$ds=DIRECTORY_SEPARATOR;
$filename="C:{$ds}xampp{$ds}htdocs{$ds}AutoRelease{$ds}releaseForWindow{$ds}start.txt";
$configFile="C:{$ds}xampp{$ds}htdocs{$ds}AutoRelease{$ds}releaseForWindow{$ds}config.txt";
$logFile = "C:{$ds}xampp{$ds}htdocs{$ds}AutoRelease{$ds}releaseForWindow{$ds}log";


while (true){
    echo 'start<br>';
    $result = array('result' => false);
    if (!$file = fopen($filename, 'r')){
        $result['result_text'] = "open file({$filename}) fail";
        echo json_encode($result);
        exit();
    }
    $buffer=fgets($file);
    if ($buffer === false){
        sleep(1);
        echo 'end<br>';
        exit();
        continue;
    }
    list($forumType,$forumVersion,$version,$date)=preg_split('/,/', $buffer);

    $ds = DIRECTORY_SEPARATOR;

    $fileContents = file_get_contents($configFile);
    $forumSetting = json_decode($fileContents, true);

    if (!isset($forumType) || empty($forumType) || !array_key_exists($forumType, $forumSetting)){
        $result['result_text'] = "incorrect forum type";
        echo json_encode($result);
    }
    if (!isset($version) || empty($version) ){
        $result['result_text'] = "incorrect version";
        echo json_encode($result);
    }

    $outName = Tools::getOutName(array('forum_name'=>$forumSetting[$forumType]['name'],
                                    'forum_version'=>$forumVersion,
                                    'plugin_version'=>$version));
    $zipName = "$outName.zip";

    $autoBuild = new AutoBuild($forumSetting[$forumType]['name'],
    $forumSetting[$forumType]['root'],
    $forumSetting[$forumType]['outPath'].$ds.$zipName,
    $forumSetting[$forumType]['resultHttpRoot'],
    $forumSetting[$forumType]['gitFilePath'],
    $forumSetting[$forumType]['gitBranch'],
    isset($forumSetting[$forumType]['sumFiles']) && !empty($forumSetting[$forumType]['sumFiles']) ? $forumSetting[$forumType]['sumFiles'] : array(),
    isset($forumSetting[$forumType]['fileSumPath']) && !empty($forumSetting[$forumType]['fileSumPath']) ? $forumSetting[$forumType]['fileSumPath'] : "",
    $forumType,
    $version,
    $forumSetting[$forumType]['versionFiles'],
    $forumSetting[$forumType]['lastReleaseGitVersion'],
    $configFile,
    $logFile,
    isset($forumSetting[$forumType]['ignore_files']) ? $forumSetting[$forumType]['ignore_files'] : array());

    $resultName = "$outName.txt";
    $file = fopen($forumSetting[$forumType]['outPath'].$ds.$resultName, 'w');
    $wirteResult = fwrite($file, json_encode($autoBuild->autoBuild()).'\n');

    $txt_arr = file($filename);
    unset($txt_arr[0]);

    file_put_contents($filename, $txt_arr);

}

class AutoBuild{
    private $forumName = "";
    private $gitResults = "";
    private $root = "";
    private $gitFilePath = "";
    private $gitBranch;
    private $outPath = "";
    private $logOutPath = "";
    private $sumFiles = array();
    private $fileSumPath = "";
    private $forumSystem = "";
    private $version = "";
    private $versionFiles = array();
    private $lastReleaseGitVersion = "";
    private $configFile = "";
    private $log;
    private $resultHttpRoot = "";
    private $extra_content = array();
    private $ignoreFiles = array();

    public $ds = DIRECTORY_SEPARATOR;
     
    public function __construct($forumName, $root, $outPath, $resultHttpRoot, $gitFilePath, $gitBranch = 'master', $sumFiles, $fileSumPath, $forumSystem, $version, $versionFiles, $lastReleaseGitVersion, $configFile, $logFile, $ignoreFiles = array()){
        $this->forumName = $forumName;
        $this->root = $root;
        $this->outPath = $outPath;
        $this->resultHttpRoot = $resultHttpRoot;
        $this->gitFilePath = $gitFilePath;
        $this->gitBranch = $gitBranch;
        $this->forumSystem = $forumSystem;
        $this->version = $version;
        $ds = DIRECTORY_SEPARATOR;
        if (isset($fileSumPath) && !empty($fileSumPath)){
            $this->fileSumPath = $root.$ds.$fileSumPath;
        }
        $this->lastReleaseGitVersion = $lastReleaseGitVersion;
        $this->log = new KLogger($logFile, KLogger::INFO );
        if (isset($sumFiles) && !empty($sumFiles) && is_array($sumFiles)){
            foreach ($sumFiles as $value){
                $this->sumFiles[$root.$ds.$value] = $root;
            }
        }
        if (isset($ignoreFiles) && !empty($ignoreFiles) && is_array($ignoreFiles)){
            foreach ($ignoreFiles as $value){
                $this->ignoreFiles[] = $root.$ds.$value;
            }
        }

        foreach ($versionFiles as $key => $value){
            $this->versionFiles[$root.$ds.$key] = $value;
        }
        $this->configFile = $configFile;
    }

    public function autoBuild(){
        $result = array('result'=>false);
        $root = $this->root;
        $outPath = $this->outPath;
        $fileSumPath = $this->fileSumPath;
        $ds = DIRECTORY_SEPARATOR;
        $this->gitResults="";

        //        $errorPath="";
        //        if (!chmod(dirname($outPath), 0777)){
        //            $errorPath=dirname($outPath);
        //        }else if (!chmod(dirname($fileSumPath), 0777)){
        //            $errorPath=dirname($fileSumPath);
        //        }
        //        if (!empty($errorPath)){
        //            $this->log->logError("Changes file($errorPath) mode fail.Please confirm parent folder have save permission<br>");
        //            return "Changes file($errorPath) mode fail.Please confirm parent folder have save permission<br>";
        //        }

        //pull code form git
        $myGit = new MyGit('"C:\Program Files (x86)\Git\bin\sh.exe" --login -i', $this->gitFilePath);
        $this->addGitResult($myGit->run_command("git checkout {$this->gitBranch}"));
        $this->addGitResult($myGit->run_command("git pull origin {$this->gitBranch}"));

        //PHP syntax check
        $myPHP = new PHPFilesSyntaxCheck("C:/xampp/php", $this->ignoreFiles);
        $phpSyntaxCheckResult = $myPHP->syntaxCheck(array($root), strlen($root)+1);
        if ($phpSyntaxCheckResult !== true){
            return $this->getError($myGit, 'find some syntax error', array('syntaxError' => $phpSyntaxCheckResult));
        }

        //output git log
        $outputResult = $this->outputLog($myGit, $this->lastReleaseGitVersion, $this->root);
        if($outputResult["result"] === true){
            $result['result'] = true;
            $result['changeLog'] = $outputResult['result_text'];
        }else{
            return $this->getError($myGit, 'print git log fail.', $result);
        }

        //modify version number
        foreach ($this->versionFiles as $key => $value){
            if ($key == $this->root.$ds.'extra_modify' && is_array($value)){
                foreach ($value as $file => $operate){
                    $modifyVersionResult =$this->modifyVersion($this->root.$ds.$file, $operate);

                    if ($modifyVersionResult !== true){
                        return $this->getError($myGit, "$key:$modifyVersionResult", $result);
                    }
                }
            }else{
                $modifyVersionResult =$this->modifyVersion($key, $value, $this->version);

                if ($modifyVersionResult !== true){
                    return $this->getError($myGit, "$key:$modifyVersionResult", $result);
                }
            }
        }

        //generate a calibration file
        if (isset($fileSumPath) && !empty($fileSumPath)){
            $buildFileSumsResult = BuildFileSums::generateFileSums($fileSumPath, $this->sumFiles, $this->ignoreFiles);
            if (!$buildFileSumsResult){
                return $this->getError($myGit, "generate fild sums fail", $result);
            }
        }

        //generate zip file
        $zipFile=BuildZip::createZip($outPath);
        if ($zipFile === false){
            return $this->getError($myGit, 'create ZipArchive object fail', $result);
        }
        $buildZipResult = BuildZip::filesToZip($root, $zipFile, strlen($root)+1, $this->ignoreFiles);

        $zipFile -> close();
        if ($buildZipResult === true){
            $result['result'] = ($result['result'] && true);
            $result['zip'] = $this->resultHttpRoot.$ds.basename($outPath);
        }else{
            return $this->getError($myGit, 'generate zip file fail', $result);
        }

        //delete calibration file in git branch
        if (isset($buildFileSumsResult) && $buildFileSumsResult) {
            unlink($fileSumPath);
        }

        //git push
        $this->addGitResult($myGit->run_command("git add ."));
        $this->addGitResult($myGit->run_command("git commit -m 'release {$this->forumSystem} v{$this->version}'"));
        $this->gitTag($myGit);
        $this->addGitResult($myGit->run_command("git push origin {$this->gitBranch}"));

        //modify hash value of git in config file
        $gitResult = $myGit->run_command("git log --pretty=format:'%H' -1");
        if ($gitResult['result'] === true){
            $gitResult["result_text"]=substr(trim($gitResult["result_text"]), -40);
            $modifyResult = $this->modifyLastReleaseGitVersion($gitResult["result_text"], $this->forumSystem, $this->configFile);
            if ($modifyResult !== true){
                return $this->getError($myGit, $modifyResult, $result);
            }
        }else {
            $this->addGitResult($gitResult);
        }

        if (!empty($this->gitResults)){
            return $this->getError($myGit, '', $result);
        }

        return $result;
    }

    public function modifyVersion($filename, $regex, $version = ''){
        if (!file_exists($filename)){
            return "file($filename) no find";
        }
        $file = fopen($filename, 'r+');
        while (!feof($file)){
            $line = fgets($file);
            $lineLen = strlen($line);
            $isChange = false;

            if (is_array($regex)){
                foreach ($regex as $key => $value){
                    if (!preg_match($key, $line)) continue;
                    $modifyContent = Modify::parseString($line, $key, $value, $this->extra_content);
                    $this->extra_content[] = preg_replace('/\$\{\d+\}/', '', $modifyContent);
                    $line = preg_replace($key, $modifyContent, $line);
                    $isChange = true;
                }
            }else{
                if (!preg_match($regex, $line, $match)){
                    continue;
                }
                $line = preg_replace($regex, $match[1].$version.$match[3], $line);

                $isChange = true;
            }

            if (!$isChange){
                continue;
            }
            if (strlen($line) > $lineLen){
                $line = $line.file_get_contents($filename, null, null, ftell($file));
            }else if (strlen($line) < $lineLen){
                $line = $line.str_repeat(' ', $lineLen-strlen($line));
            }

            fseek($file, -$lineLen, SEEK_CUR);
            if (!fwrite($file, $line)){
                return 'write fail';
            }
        }
        return true;
    }

    public function outputLog(MyGit $myGit, $lastReleaseGitVersion = "", $gitfile = ""){
        $gitCommand = 'git log --date=iso --pretty=format:"%an : %ad : %s" '.(!empty($lastReleaseGitVersion)?"$lastReleaseGitVersion.. ":'').'--no-merges' .(!empty($gitfile)?" -- $gitfile":'');
        $gitResult = $myGit->run_command($gitCommand);

        $this->addGitResult($gitResult);

        return $gitResult;
    }

    private function addGitResult($gitResult){
        if (isset($gitResult['result']) && $gitResult['result'] !== true){
            $this->gitResults .= $gitResult['result_text'];
        }
    }

    private function modifyLastReleaseGitVersion($newGitVersion, $forumType, $configFile){
        if (!file_exists($configFile)){
            return "File($configFile) no find.";
        }

        $fileContents = file_get_contents($configFile);
        $config = json_decode($fileContents, true);
        $config[$forumType]['lastReleaseGitVersion']=$newGitVersion;

        $file = fopen($configFile, 'w');
        if (fwrite($file, Tools::jsonFormat($config))===false){
            return false;
        }
        return true;
    }

    private function gitTag(MyGit $myGit){
        $gitResult = $myGit->run_command("git tag");
        if (!$gitResult['result']){
            $this->addGitResult($gitResult);
            return;
        }
        if (strstr($gitResult['result_text'], $this->forumName."v".$this->version) === false){
            $this->addGitResult($myGit->run_command("git tag -a {$this->forumName}v{$this->version} -m 'release {$this->forumSystem} v{$this->version}'"));
            $this->addGitResult($myGit->run_command("git push origin --tags"));
        }
    }

    private function getError($myGit, $error, $result = array()){
        $this->addGitResult($myGit->run_command("git reset --hard origin/master"));
        $result['result'] = false;
        $error = $error."\n".(!empty($this->gitResults)?"GitResults:".$this->gitResults:"");
        $result['result_text']= $error;
        $this->log->logError($error);
        return $result;
    }
}

Class Modify{
    public static function parseString($string, $regex, $operate, $extra_content = array()){
        if (substr($operate, 0, 1) != '_'){
            return $string;
        }
        $result = '';
        //#$ 是方法中的参数，需要用正则的结果替换
        preg_match($regex, $string, $match);
        if (preg_match($regex, $string, $match) == 0){
            return $result;
        }
        foreach ($match as $key => $value){
            $operate = preg_replace('/\#\$\{'.$key.'\}/', $value, $operate);
        }

        $strArr=preg_split('/_/', substr($operate, 1));
        $param = array();

        foreach ($strArr as $value){
            if ($value[0] == '$'){
                if (count($param)>=1){
                    $functionName = $param[0];
                    if (method_exists('Modify', $functionName)){
                        $result .= call_user_func("Modify::$functionName", $param);
                    }
                }
                $param = array();
                $result .= $value;
                continue;
            }

            //^number 表示extra_content从后向前数第number的值
            $match = array();
            preg_match('/\^\{(\d+)\}/', $value, $match);
            if (count($match)>1){
                $key = count($extra_content)-$match[1];
                if (array_key_exists($key, $extra_content)){
                    $param[] = $extra_content[$key];
                }
                continue;
            }

            $param[] = $value;
        }
        return $result;
    }
    //参数是从1开始的，0是方法的名字
    public static function genHookVersionByVersionLong(array $params){
        if (count($params)<2)    return false;
        $version = preg_replace('/\./', '', $params[1]);
        $versionLong = (string)($version+1);
        return $versionLong[0].'.'.$versionLong[1].'.'.$versionLong[2];
    }

    /**
     *
     * 计算表达式的int结果
     * @param array $params, $params[1]为运算表达式，如10+2*8
     */
    public static function calculate(array $params){
        if (count($params)<2)    return false;
        return eval("return {$params[1]};");
    }

    /**
     * 获取当前日期
     */
    public static function formatData(array $params){
        if (count($params)<2)    return false;
        return gmdate($params[1], time() + 3600 * 8);
    }
}