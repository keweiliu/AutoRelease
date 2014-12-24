<?php
require_once '/var/AutoRelease/Tools.php';
require_once '/var/www/AutoReleaseReady/CheckIP.php';

$result = array('result' => false);



$ds=DIRECTORY_SEPARATOR;
$filename="{$ds}var{$ds}AutoRelease{$ds}start.txt";
$configFile="{$ds}var{$ds}AutoRelease{$ds}config.txt";
if (!$file = fopen($filename, 'a')){
    $result['result_text'] = 'open file({$filename}) fail';
    echo json_encode($result);
    exit();
}

if (!isset($_GET['type']) || !isset($_GET['version'])){
    $result['result_text'] = "type or version parameters no found";
    echo json_encode($result);
    exit();
}

$type = $_GET['type'];
$version= $_GET['version'];
$extend= (isset($_GET['extend']) && !empty($_GET['extend'])) ? $_GET['extend'] : '';

$fileContents = file_get_contents($configFile);
$forumSetting = json_decode($fileContents, true);


if (empty($type) || !array_key_exists($type, $forumSetting)){
    $result['result_text'] = "incorrect forum type";
    echo json_encode($result);
    exit();
}
if (empty($version)){
    $result['result_text'] = "incorrect version";
    echo json_encode($result);
    exit();
}

$date=time();
$line = "$type,$version,$date,$extend\n";
if(fwrite($file, $line) == FALSE){
    $result['result_text'] = "running fail.Please try again";
    echo json_encode($result);
    exit();
}

$outName = Tools::getOutName($forumSetting[$type]['name'], $version);
$result['resultFile'] = $forumSetting[$type]['resultHttpRoot'].'/'.$outName;
$result['result'] = true;

echo json_encode($result);
