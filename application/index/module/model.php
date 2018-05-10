<?php
/**
 * Created by PhpStorm.
 * User: iyahe@qq.com (天明)
 * Date: 2017/7/27
 * Time: 11:31
 */
namespace app\index\module;
use think\Controller;
use think\Db;
class model{
    //生成唯一的订单号
    public static function getOrder(){
        return date('Ymd').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
    }
}