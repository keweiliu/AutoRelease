<?php
class Tools{
    public static function ignoreFile($filePath, &$ignoreFiles){
        foreach ($ignoreFiles as $key => $ignoreFile){
            if (realpath($filePath) == realpath($ignoreFile)){
                unset($ignoreFiles[$key]);
                return true;
            }
        }
        return false;
    }

    /** Json数据格式化
     * @param  Mixed  $data   数据
     * @param  String $indent 缩进字符，默认4个空格
     * @return JSON
     */
    public static function jsonFormat($data, $indent=null){

        // 对数组中每个元素递归进行urlencode操作，保护中文字符，不使用的原因是正则表达式中的\会丢失
//        array_walk_recursive($data, 'self::jsonFormatProtect');

        // json encode
        $data = json_encode($data);

        // 将urlencode的内容进行urldecode
//        $data = urldecode($data);

        // 缩进处理
        $ret = '';
        $pos = 0;
        $length = strlen($data);
        $indent = isset($indent)? $indent : '    ';
        $newline = "\n";
        $prevchar = '';
        $outofquotes = true;

        for($i=0; $i<=$length; $i++){
            $char = substr($data, $i, 1);

            if($char=='"' && $prevchar!='\\'){
                $outofquotes = !$outofquotes;
            }elseif(($char=='}' || $char==']') && $outofquotes){
                $ret .= $newline;
                $pos --;
                for($j=0; $j<$pos; $j++){
                    $ret .= $indent;
                }
            }

            $ret .= $char;

            if(($char==',' || $char=='{' || $char=='[') && $outofquotes){
                $ret .= $newline;
                if($char=='{' || $char=='['){
                    $pos ++;
                }

                for($j=0; $j<$pos; $j++){
                    $ret .= $indent;
                }
            }

            $prevchar = $char;
        }

        return $ret;
    }

    /** 将数组元素进行urlencode
     * @param String $val
     */
    private static function jsonFormatProtect(&$val){
        if($val!==true && $val!==false && $val!==null){
            $val = urlencode($val);
        }
    }

    /**
     * 获得输出的名字
     * Enter description here ...
     * @param array $params
     */
    public static function getOutName($forumName, $pluginVersion){
        if (empty($forumName)){
            return "tapatalk_v$pluginVersion";
        }
        return "tapatalk_{$forumName}_v{$pluginVersion}";
    }
}