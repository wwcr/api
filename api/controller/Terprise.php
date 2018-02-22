<?php
/**
 * Created by PhpStorm.
 * User: iyahe@qq.com (天明)
 * Date: 2017/10/26
 * Time: 15:12
 */
namespace app\api\controller;
use app\index\controller\Action;
use app\index\module;
use think\Db;
use app\api\validate;

class Terprise extends Action
{
    public function en_nameChange(){
        $name = input('name');
        if(!$name){
            self::AjaxReturnError('请检查企业名称是否完整');
        }
        $uid = Db::name('terprise')->where(['enuser_city_id'=>$this->uid])->find();
        if($uid['enuser_city_id'] != $this->uid){
            self::AjaxReturnError('您还未认证,暂未查到您的企业');
        }else{
            $res = Db::name('terprise')->where(['enuser_city_id'=>$this->uid])->update(['en_name'=>$name]);
            if($res){
                self::ajaxReturnSuccess('修改成功');
            }
        }
    }
}