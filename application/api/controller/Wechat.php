<?php
/**
 * Created by PhpStorm.
 * User: iyahe@qq.com (天明)
 * Date: 2017/11/24/024
 * Time: 22:02
 */
namespace app\api\controller;
use app\index\controller\Action;
use app\index\module;
use think\Db;
use app\api\validate;
use EasyWeChat\Foundation\Application;

/**微信支付 [废弃]
 * Class Wechat
 * @package app\api\controller
 */
class Wechat extends Action
{
    public static $app;
    public function __construct()
    {
        parent::__construct();
        require_once root.'/vendor/autoload.php';
        //$this->server();
    }
    public function  server(){
        if(!self::$app){
            $options = require_once root.'/config/wechat.php';
            $app = new Application($options);
            self::$app = $app;
        }
        return self::$app;
    }
    //授权获取openid
    public function oauth(){
        $oauth = self::$app->oauth;
        // 未登录
        if (empty($_SESSION['wechat_user'])) {
            $_SESSION['target_url'] = 'user/profile';
            return $oauth->redirect();
        }
        $user = $_SESSION['wechat_user'];
    }
    //授权回调
    public function callback(){
        $user =self::$app->oauth->user();
        $_SESSION['wechat_user'] = $user->toArray();
        $targetUrl = empty($_SESSION['target_url']) ? '/' : $_SESSION['target_url'];
        header('location:'. $targetUrl); // 跳转到 user/profile
    }
}