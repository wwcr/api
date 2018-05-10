<?php
/**
 * Created by PhpStorm.
 * User: iyahe@qq.com (天明)
 * Date: 2017/10/26
 * Time: 13:18
 */
namespace app\admin\controller;

use app\index\controller\Action;
use think\Controller;
use think\Db;

class Order extends Action
{
    public function index(){}
    public function listd(){
        $list = Db::name('exorder')
            ->join('np_single','np_single.single_order = np_exorder.ex_oid','left')
            ->join('np_user','np_user.user_id = np_single.single_uid','left')
            ->join('np_findcard','np_findcard.find_id=np_exorder.ex_fid','left')
            ->paginate(12);
        self::AjaxReturn($list);
    }
}