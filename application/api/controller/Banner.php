<?php
namespace app\api\controller;
use app\index\controller\Action;
use think\Db;

class Banner extends Action
{

    public function getFindcarBanner(){
        $data = config('findcarBanner');
        self::AjaxReturn($data, '获取导航成功', 1);
    }

}