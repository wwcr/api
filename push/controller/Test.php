<?php

namespace app\push\controller;
use think\Db;
use think\Cache;
class Test
{
    
    public function test(){
    // $str = file_get_contents('wechattest.txt');//将整个文件内容读入到一个字符串中
    // var_dump($str);
    // $str_encoding = mb_convert_encoding($str, 'UTF-8', 'UTF-8,GBK,GB2312,BIG5');//转换字符集（编码）
    // $arr = explode("\r\n", $str_encoding);//转换成数组

    // //去除值中的空格
    // foreach ($arr as &$row) {
    //     $row = trim($row);
    // }

    // unset($row);
    // //得到后的数组
    // var_dump($arr);
    $options = [
            // 缓存类型为File
            'type' => 'redis',
            'prefix' => ''
        ];
        $this->redis = Cache::connect($options);//连接redi
        $res = $this->redis->get(131313);
        var_dump($res);
    }
}