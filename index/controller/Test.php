<?php
/**
 * Created by PhpStorm.
 * User: iyahe@qq.com (天明)
 * Date: 2017/10/27/027
 * Time: 12:41
 */
namespace app\index\controller;
use think\Db;
class Test extends Action
{
    public function index(){
    	echo 2312312;
        $sign1 = ['data' =>1111];
    	$res = Db::name('test')->select();
    	// $res = Db::name('test')->insert($sign1);
    	var_dump($res);
    }
}