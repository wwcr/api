<?php
/**
 * Created by PhpStorm.
 * User: iyahe@qq.com (天明)
 * Date: 2017/10/24/024
 * Time: 23:54
 */
namespace app\admin\controller;

use think\Controller;

class Index extends Controller
{
    public function index(){
        return view('./admin/index.html');
    }
}