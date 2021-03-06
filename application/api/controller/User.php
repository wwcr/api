<?php
/**
 * Created by PhpStorm.
 * User: iyahe@qq.com (天明)
 * Date: 2017/10/25
 * Time: 17:39
 */
namespace app\api\controller;
use app\index\controller\Action;
use app\index\module;
use think\Db;
use app\api\validate;

class User extends Action
{
    public function info(){
        $list = Db::name('user')
            ->where('user_id',$this->uid)
            ->field('user_nickname,user_header')
            ->find();
        self::AjaxReturn($list);
    }
    public function updateHeader(){
        $header = input('header','');
        if($header){
            $update = Db::name('user')
                ->where('user_id',$this->uid)
                ->update(['user_header'=>$header]);
            if($update){
                self::AjaxReturnSuccess('更新成功');
            }
            self::AjaxReturnError('更新失败');
        }
    }
    public function changeNickname(){
        $name  = input('name');
        if(mb_strlen($name) >= 10){
            self::AjaxReturnError('太长,请输入少于10个字的昵称吧');
        }else{
           $user =  Db::name('user')
                ->where(['user_id'=>$this->uid])
                ->update(['user_nickname'=>$name]);
           if($user){
               self::AjaxReturnSuccess('修改成功');
           }else{
               self::AjaxReturnError('修改失败');
           }
        }
    }
    public function changepwd(){
        $oid = input('oldpwd');
        $pwd = input('pwd');
        $info = Db::name('user')->where(['user_id'=>input('uid')])->find();
        if($info['user_password'] != $this->getpwd($oid)){
            self::ajaxReturnError('旧密码输入有误');
        }elseif ($info['user_password'] == $this->getpwd($pwd)) {
        	self::ajaxReturnError('新密码不能与旧密码相同');
        }else{
            $uopdate = Db::name('user')
                ->where(['user_id'=>input('uid')])
                ->update(['user_password'=>$this->getpwd($pwd)]);
            if($uopdate){
                self::ajaxReturnSuccess('密码修改成功,请重新登陆');
            }else{
                self::ajaxReturnError('系统繁忙,请稍后再试');
            }
        }
    }
}