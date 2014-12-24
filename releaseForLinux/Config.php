<?php
include_once './Tools.php';
$ds = DIRECTORY_SEPARATOR;
$outPath="C:{$ds}xampp{$ds}htdocs{$ds}AutoRelease{$ds}releaseForLinux{$ds}config.txt";
if(Config::generateConfigTxt($outPath) === false){
    echo 'fail';
}else{
    echo 'success';
}
class Config{
    /**
     * 参数说明：
     * name: 论坛模板名称
     * root：插件根目录
     * outPath：结果输出目录
     * resultHttpRoot：结果输出的网络目录
     * gitFilePath：本地Git项目路径
     * gitBranch：要发布的Git分支
     * sumFiles：可选，要验证的文件
     * fileSumPath：可选，验证结果文件输出的位置
     * versionFiles：版本号相关文件, 格式为path=>regex, path:版本相关文件相对于root的路径, regex：修改版本号的正则表达式。当path为
     *      extra_modify：可选，表示额外要修改的东西, 结果可以有线性关系，也可以没有，不支持嵌套、递归。
     *      格式为regex=>operate;
     *      regex:查找要修改内容的正则表达式
     *      operate：操作字符串
     *          规则：${number}同正则表达式，表示查账出的第number个（）中的内容
     *               #${number}表示查账出的第number个（）中的内容,当作自创方法的参数使用
     *               ^{number}表示前number次操作的结果，例^{2}表示上上次操作的结果
     *              若要调用自创函数，需遵循_${1}_funcitonName_param1_param2_...._${3}格式，要以'_'开头，并且以'_'分割每个元素
     *              自创函数需放在AutoBuild.php的Modify类中，格式应为public funciton name(array $params){}并且$params中参数是
     *              从1开始的，0是方法的名称。
     *              可以为preg_replace允许的替换字符串,如'${1}${2}${3}'
     *          注意：现有设计，是按照文本顺序执行操作，所以结果的线性关系需要遵循文本的顺序，比如说文本内容为AB，先对A进行操作，
     *              但操作如果要用到B的结果，那么取不到，因为还没有对B进行操作。如果B要用A的结果，倒是可以，使用^{1}就可以了
     * ignore_files：可选，忽略的文件，相对于root的目录
     * lastReleaseGitVersion：上一次发布的Git版本号，拉git log时需要
     */
    public static function getConfig()
    {
        return array(
            'xf10' => array(
                'name' => 'xenforo-1.0',
                'root' => "/var/www/git/xenforo/xenforo",
                'outPath'=>"/var/www/AutoReleaseReady/Result/xenforo",
                'resultHttpRoot' => "http://dbd.tapatest.com/AutoReleaseReady/Result/xenforo",
                'gitFilePath'=>"/var/www/git/xenforo",
                'gitBranch' => 'master',
                'versionFiles' => array(
                    "mobiquo/config/config.php" => '/([\'"]version[\'"]\s*=>\s*[\'"]xf10_)([\d|\.]+)(.*)/',
                    "addon-Tapatalk.xml" => '/(title=[\'"]Tapatalk[\'"].*version_string\s*=\s*")([\d|\.]+)(.*)/',
                    "extra_modify" => array(
                        "addon-Tapatalk.xml" => array(
                            '/(title=[\'"]Tapatalk[\'"].*version_id\s*=\s*[\'"])(\d+)([\'"])/' => '_${1}_calculate_#${2}+1_${3}',
                        )
                    )
                ),
                'sumFiles' => array(
                    "library/Tapatalk" ,
                    "mobiquo",
                ),
                'fileSumPath' => "mobiquo/printScreen/FileSums.php",
                'ignore_files' => array(
                    'mobiquo/printScreen',
                ),
                'lastReleaseGitVersion' => "1d7384f6d532f755483389664cb47b510603b7da",
            ),
            'mb16' => array(
                'name' => 'mybb-1.x',
                'root' => "/var/www/git/tapatalk-mybb",
                'outPath'=>"/var/www/AutoReleaseReady/Result/mybb",
                'resultHttpRoot' => "http://dbd.tapatest.com/AutoReleaseReady/Result/mybb",
                'gitFilePath'=>"/var/www/git/tapatalk-mybb",
                'gitBranch' => 'master',
                'versionFiles' => array(
                    "mobiquo/config/config.txt" => '/(version\s*=\s*\w+_)([\d\.]+)(.*)/',
                    "inc/plugins/tapatalk.php" => '/([\'"]version[\'"]\s*=>\s*[\'"])([\d\.]+)(.*)/',
                ),
                'lastReleaseGitVersion' => "a88b4db7fa0c5de324dd54a8618856de5d50b030",
            ),
            'ip34' => array(
                'name' => 'ipb-3.4',
                'root' => "/var/www/git/plugin/IPBoard/ipb_3.4",
                'outPath'=>"/var/www/AutoReleaseReady/Result/ipb",
                'resultHttpRoot' => "http://dbd.tapatest.com/AutoReleaseReady/Result/ipb",
                'gitFilePath'=>"/var/www/git/plugin",
                'gitBranch' => 'master',
                'versionFiles' => array(
                    "mobiquo/config/config.txt" => '/(version\s*=\s*\w+_)([\d\.]+)(.*)/',
                        "extra_modify" => array(
                        "mobiquo/tapatalk.xml" => array(
                            '/(<hook_version_human>)([\d\.]+)(<\/hook_version_human>)/' => '_${1}_genHookVersionByVersionLong_#${2}_${3}',
                            '/(<hook_version_long>)([\d]+)(<\/hook_version_long>)/' => '_${1}_calculate_(#${2}/100+1)*100_${3}',
                        ),
                        "mobiquo/config/config.txt" => array(
                            '/(long_version\s*=\s*)([\d]+)(\s*)/' => '_${1}^{1}${3}',
                        )
                    )
                ),
                'ignore_files' => array(
                    'ChangeLog.txt',
                ),
                'lastReleaseGitVersion' => "5b8cf9e49d173ff9d6c7b3f1e05c2a0dcbaa769b",
            ),
            'pb30' => array(
                'name' => 'phpBB-3.0',
                'root' => "/var/www/git/tapatalk-phpbb3",
                'outPath'=>"/var/www/AutoReleaseReady/Result/phpBB",
                'resultHttpRoot' => "http://dbd.tapatest.com/AutoReleaseReady/Result/phpBB",
                'gitFilePath'=>"/var/www/git/tapatalk-phpbb3/tapatalk-phpbb3",
                'gitBranch' => 'master',
                'versionFiles' => array(),
                'lastReleaseGitVersion' => "d58c1b0af21c8a22b6b8f3100b12a6495a0350d8",
            ),
            'pb31' => array(
                'name' => 'phpBB-3.1',
                'root' => "/var/www/git/plugin/phpBB/3.1/upload",
                'outPath'=>"/var/www/AutoReleaseReady/Result/phpBB",
                'resultHttpRoot' => "http://dbd.tapatest.com/AutoReleaseReady/Result/phpBB",
                'gitFilePath'=>"/var/www/git/plugin",
                'gitBranch' => 'master',
                'versionFiles' => array(
                    "mobiquo/config/config.txt" => '/(version\s*=\s*\w+_)([\d\.]+)(.*)/',
                    "ext/tapatalk/tapatalk/composer.json" => '/([\'"]version[\'"]\s*:\s*[\'"])([\d\.]+)([\'"]\s*,)/',
                    "extra_modify" => array(
                        "ext/tapatalk/tapatalk/composer.json" => array(
                            '/([\'"]time[\'"]\s*:\s*[\'"])([^"\']*)([\'"]\s*,)/' => '_${1}_formatData_Y-m-d_${3}',
                        )
                    )
                ),
                'lastReleaseGitVersion' => "a452070294c78ea838ff849e67a4053e12cf8d57",
            ),
            'sm-2a' => array(
                'name' => 'smf-2a',
                'root' => "/var/www/git/tapatalk-smf2",
                'outPath'=>"/var/www/AutoReleaseReady/Result/smf2",
                'resultHttpRoot' => "http://dbd.tapatest.com/AutoReleaseReady/Result/smf2",
                'gitFilePath'=>"/var/www/git/tapatalk-smf2",
                'gitBranch' => 'master',
                'versionFiles' => array(
                    "mobiquo/config/config.txt" => '/(version\s*=\s*[^_]+_)([\d\.]+)(.*)/',
                    "package-info.xml" => '/(<version>)([\d\.]+)(<\/version>)/',
                ),
                'lastReleaseGitVersion' => "aecb506221bf32f8ae2b38be5e5a4b548faaaf93",
            ),
            'vb3x' => array(
                'name' => 'vb-3.x',
                'root' => "/var/www/git/plugin/vb3x",
                'outPath'=>"/var/www/AutoReleaseReady/Result/vb3x",
                'resultHttpRoot' => "http://dbd.tapatest.com/AutoReleaseReady/Result/vb3x",
                'gitFilePath'=>"/var/www/git/plugin",
                'gitBranch' => 'master',
                'versionFiles' => array(
                    "mobiquo/config/config.txt" => '/(version\s*=\s*[^_]+_)([\d\.]+)(.*)/',
                    "mobiquo/product-tapatalk.xml" => '/(<version>)([\d\.]+)(<\/version>)/',
                ),
                'lastReleaseGitVersion' => "f51810a28d129edb568148c874294632a38328f1",
            ),
            'vb40' => array(
                'name' => 'vb-4.0',
                'root' => "/var/www/git/plugin/vb40",
                'outPath'=>"/var/www/AutoReleaseReady/Result/vb40",
                'resultHttpRoot' => "http://dbd.tapatest.com/AutoReleaseReady/Result/vb40",
                'gitFilePath'=>"/var/www/git/plugin",
                'gitBranch' => 'master',
                'versionFiles' => array(
                    "mobiquo/config/config.txt" => '/(version\s*=\s*[^_]+_)([\d\.]+)(.*)/',
                    "mobiquo/product-tapatalk.xml" => '/(<version>)([\d\.]+)(<\/version>)/',
                ),
                'lastReleaseGitVersion' => "9eac412866ebbbe85b448614df7837d2a8ca73b5",
            ),
        );
    }
    public static function generateConfigTxt($outPath){
        $forumSetting = Config::getConfig();
        $file = fopen($outPath, 'w');
        return fwrite($file, Tools::jsonFormat($forumSetting));
    }
}