<?php
/**
 * Created by PhpStorm.
 * User: iyahe@qq.com (天明)
 * Date: 2017/7/26
 * Time: 14:37
 */
namespace app\index\module;

use think\Controller;
use think\Db;

class login
{
    private static $_instance;
    public static function start()
    {
        if (!self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function Adminlogin($user, $pwd)
    {
        $user = Db::name('admin')
            ->where(['ad_name' => $user])
            ->find();
        if (empty($user)) {
            return '用户不存在';
        } else {
            if (md5(sha1($pwd)) == $user['ad_password']) {
                session('user', $user);
                return $user;
            } else {
                return '密码错误';
            }
        }
    }
}
