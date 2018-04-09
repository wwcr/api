<?php
/**
 * Created by PhpStorm.
 * User: iyahe@qq.com (天明)
 * Date: 2017/10/27/027
 * Time: 11:26
 */
namespace app\api\controller;
use app\index\controller\Action;
use app\index\controller\ApiCenter;
use app\index\module;
use think\Db;
use app\api\validate;

class Index extends Action
{
    public function index(){
        if(input('act') == 'cardata'){
            $run = new ApiCenter();
            $run->cardata();
        }
    }
    public function test(){
    	$data = "[78 78 11 01 00 00 01 36 11 28 12 40 01 00 32 00 00 00 BB CE 0D 0A ]";
    	$sign1 = ['data' =>$data];
    	$res = Db::name('test')->insert($sign1);
    }
}