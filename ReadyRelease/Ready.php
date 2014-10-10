<?php
require_once './Tools.php';
$ds=DIRECTORY_SEPARATOR;
$filename="{$ds}var{$ds}AutoRelease{$ds}start.txt";
$configFile="{$ds}var{$ds}AutoRelease{$ds}config.txt";
if (!$file = fopen($filename, 'a')){
    echo 'open file fail';
    exit();
}

$type = $_GET['type'];
$version= $_GET['version'];

$result = array('result' => false);

$fileContents = file_get_contents($configFile);
$forumSetting = json_decode($fileContents, true);

preg_match('/([a-zA-z]+)(\w+)/', $type, $match);
if (count($match)<3){
    $result['result_text'] = "parse forum type fail";
    echo json_encode($result);
    exit();
}
$forumType = $match[1];
$forumVersion = $match[2];

if (!isset($forumType) || !array_key_exists($forumType, $forumSetting)){
    $result['result_text'] = "incorrect forum type";
    echo json_encode($result);
    exit();
}
if (!isset($version) || empty($version) ){
    $result['result_text'] = "incorrect version";
    echo json_encode($result);
    exit();
}

$date=time($forumType,$forumVersion);
$line = "$forumType,$forumVersion,$version,$date\n";
if(fwrite($file, $line) == FALSE){
    $result['result_text'] = "running fail.Please try again";
    echo json_encode($result);
    exit();
}

$outName = Tools::getOutName(array('forum_name'=>$forumSetting[$forumType]['name'],
                                    'forum_version'=>$forumVersion,
                                    'plugin_version'=>$version));
$result['resultFile'] = $forumSetting[$forumType]['resultHttpRoot'].'/'.$outName;
$result['result'] = true;

echo json_encode($result);
