<?php
/**
 * Created by PhpStorm.
 * User: iyahe@qq.com (天明)
 * Date: 2017/10/27/027
 * Time: 11:03
 */
namespace app\api\controller;

use app\index\controller\Action;
use think\Cache;
use think\Request;
use geetest\lib\GeetestLib;
use think\Db;
class Sms extends Action
{
	public function _initialize()
    {
         $options = [
            // 缓存类型为File
            'type' => 'redis',
            'prefix' => ''
        ];
        $this->redis = Cache::connect($options);//连接redis
    }
    public function second_geetest(){//二次验证
        $request = Request::instance();
        $GtSdk = new GeetestLib(CAPTCHA_ID, PRIVATE_KEY);
        $data = array(
            "user_id" => session('user_id'), # 网站用户id
            "client_type" => "h5", #web:电脑上的浏览器；h5:手机上的浏览器，包括移动应用内完全内置的web_view；native：通过原生SDK植入APP应用的方式
            "ip_address" => $request->ip() # 请在此处传输用户请求验证时所携带的IP
        );
    if (session('gtserver') == 1) {   //服务器正常
        $result = $GtSdk->success_validate(input('post.geetest_challenge'), input('post.geetest_validate'), input('post.geetest_seccode'), $data);
        if ($result) {
            //发送短信
            $this->get_rQTcOfbSfqekYex(input('post.mobile'));
        } else {
            echo '{"status":"fail"}';
        }
    } else {  //服务器宕机,走failback模式
        if ($GtSdk->fail_validate(input('post.geetest_challenge'),input('post.geetest_validate'),input('geetest_seccode'))) {
            $this->get_rQTcOfbSfqekYex(input('post.mobile'),input('post.type'));
            // echo input('post.mobile');
        } else {
            echo '{"status":"fail"}';
        }
    }
   }
    public function check_user($mobile,$type){
         $info = Db::name('user')->where(['user_mobile'=>$mobile])->find();
         if($type == 1){//注册验证码
             if(!empty($info)){//注册过
                self::AjaxReturn('用户已注册','',0);
                return;
             }
         }else if($type == 2){//忘记密码验证码
            if(empty($info)){//注册过
                self::AjaxReturn('用户不存在','',0);
                return;
             }
         }
       }
    public function get_rQTcOfbSfqekYex($mobile,$type){
        $this->check_user($mobile,$type);
        $rands = rand(1000,9999);
        $sms = new \app\api\module\sms();
        $data = [
            'template_code' => 'SMS_127153066',
            'json_string_param' => ["code" =>$rands],
            'phone' =>$mobile,
            'sign'=>'无维科技'
        ];
        if(!$mobile){
            self::AjaxReturnError('手机号不能为空');
        }
        $res = $sms->send($data);
        // var_dump($res);
        if($res = $sms->send($data) == true){
            $this->redis->set($mobile, $rands,300);
            self::AjaxReturn('验证码发送成功');
        }else{
            self::AjaxReturnError('验证码发送失败,'.$res);
        }
    }
    //匹配成功短信
    // public function MatchingSuccess(){
    public function MatchingSuccess($mobile,$info){
        $sms = new \app\api\module\sms();
        $data = [
            'template_code' => 'SMS_127163139',
            // 'json_string_param' => ["plate" =>514543513132],
            'json_string_param' => ["plate" =>$info['card_number']],
            'phone' =>$mobile,
            'sign'=>'无维科技'
        ];
        self::logger($data,'匹配成功短信');
        if(!$mobile){
            self::AjaxReturnError('手机号不能为空');
            self::logger('手机号不能为空','匹配成功短信');
        }
        $res = $sms->send($data);
        // var_dump($res);
        if($res){

            return true;

        }else{
            return $res;
        }
    }

    public function nurseNotice($mobile,$info){
        $sms = new \app\api\module\sms();
        $data = [
            'template_code' => 'SMS_133961091',
            // 'json_string_param' => ["plate" =>514543513132],
            'json_string_param' => ["card_number" =>$info['card_number']],
            'phone' =>$mobile,
            'sign'=>'无维科技'
        ];
        self::logger($data,'匹配成功短信');
        if(!$mobile){
            self::AjaxReturnError('手机号不能为空');
            self::logger('手机号不能为空','匹配成功短信');
        }
        $res = $sms->send($data);
        // var_dump($res);
        if($res){

            return true;

        }else{
            return $res;
        }
    }
}