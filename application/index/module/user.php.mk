<?php
/**
 * Created by PhpStorm.
 * User: iyahe@qq.com (天明)
 * Date: 2017/7/27
 * Time: 15:13
 */

namespace app\index\module;
use think\Controller;
use think\Model;
use think\Db;

/** 用户操作模型
 * Class user
 * @package app\index\module
 */
class user extends model {
    /** 新增用户
     * @param $mobile
     * @param $nickname
     * @param $pwd
     */
    private static $_instance;
    public static function start() {
        if (!self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    public function add($mobile, $nickname, $pwd, $proxyCity = NULL, $role = 0){
        $is = Db::name('user')->where(['user_mobile'=>$mobile])->field('user_id')->find();
        if(!empty($is))
        {
            return '当前用户已经注册过了';
        }
        $insert = array(
            'user_mobile'=>$mobile,
            'user_nickname'=>$nickname,
            'user_password'=>$pwd,
            'proxy_city' => $proxyCity,
            'user_type'=>'',
            'user_sex'=>1,
            'user_token'=>sha1($mobile),
            'user_regtime'=>getStrtime(),
            'role' => $role,
        );

        return Db::name('user')->insertGetId($insert);
    }
    public function Metype($id,$uid){
        return Db::name('user')->where(['user_id'=>$uid])->update(['user_type'=>$id]);
    }
    // public function get($uid){
    //     return Db::name('user')->where(['user_id'=>$uid])->field('user_password',true)->find();
    // }
    public function getlist($where,$limit=10){
        return Db::name('user')->where($where)->paginate($limit);
    }
    //修改用户认证情况
    public function editAuth($uid,$a){
        return Db::name('user')->where(['user_id'=>$uid])->update(['user_anthen'=>$a]);
    }
}
