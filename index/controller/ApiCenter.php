<?php
/**
 * Created by PhpStorm.
 * User: iyahe@qq.com (天明)
 * Date: 2017/7/29
 * Time: 13:09
 */
namespace app\index\controller;

use app\api\controller\Matching;
use think\Db;

/** api 数据交互中心
 * Class Api
 * @package app\index\controller
 */
class ApiCenter extends Action{
    public function cardata()
    {
        if (self::$repost) {
            $model = input('model');
            if($model == 'add'){
                $insert = array(
                    'car_card' => input('card'), //车牌
                    'car_location' => input('location'), //地址
                    'car_photo' => input('photo'), //照片
                );
                $validate = ['请检查车牌号', '请检查地址', '请检查照片'];
                $num = -1;
                foreach ($insert as $k => $v) {
                    $num++;
                    if (!$insert[$k]) self::AjaxReturn($validate[$num], '', 0);
                }
                $insert['car_hash'] = md5(input('card'));
                $insert['car_addtime'] = getStrtime();
                $insert['car_mark'] = input('mark','');
                $result = Db::name('cardata')->insertGetId($insert);
                if ($result) {
                    //更新匹配
                    $math = new Matching($insert);
                    $result = $math->init($insert['car_hash'],$result);
                    //车牌匹配, 立刻返回结果
                    self::AjaxReturn('添加成功',$result);
                } else {
                    self::AjaxReturn('添加失败', '', 0);
                }
            }
        }else{
            self::AjaxReturn('请求失败', '', 0);
        }
    }
    public function test(){
        self::AjaxReturn('你好师姐');
    }
    public function f(){}
}