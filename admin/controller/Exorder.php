<?php
/**
 * Created by PhpStorm.
 * User: iyahe@qq.com (天明)
 * Date: 2017/10/22/022
 * Time: 22:37
 */
namespace app\admin\controller;

use app\index\controller\Action;
use think\Controller;
use think\Db;

class Exorder extends Action
{
    public function index(){}
    public function getlist(){
        $limit = input('limit',10);
        $list =  Db::name('exorder')
            ->join('user','np_exorder.ex_uid=np_user.user_id','left')
            ->join('order','np_exorder.ex_order=np_order.order_number','left')
            ->order('ex_addtime DESC')
            ->paginate($limit);
        self::AjaxReturn($list);
    }
}