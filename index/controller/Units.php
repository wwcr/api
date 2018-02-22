<?php
/**
 * Created by PhpStorm.
 * User: iyahe@qq.com (天明)
 * Date: 2017/10/22/022
 * Time: 22:37
 */
namespace app\index\controller;
use app\index\module\article;
use app\index\module\login;
use app\index\module\user;
use think\Db;

class Units extends Action
{
    public function index(){
        echo 'Units';
    }
}