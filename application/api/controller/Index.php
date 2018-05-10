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
    	echo 1231313;
    }
}