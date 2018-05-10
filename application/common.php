<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
error_reporting(E_ERROR | E_PARSE );
require_once dirname(__DIR__).'/config/route.php';
require_once dirname(__DIR__).'/config/function.php';
function mk_dirs($path){
    if(!is_dir($path)){
        mk_dirs(dirname($path));
        if(!mkdir($path, 0777)){
            return false;
        }
    }
    return true;
}
