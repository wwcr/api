<?php
/**
 * Created by PhpStorm.
 * User: iyahe@qq.com (天明)
 * Date: 2017/10/27/027
 * Time: 11:03
 */
namespace app\api\controller;

use app\index\controller\Action;

class Sms extends Action
{
    public function index($mobile){}
    //忘记密码
    public function forget(){
        $mobile = input('user_mobile');
        $rands = rand(1000,9999);
        $sms = new \app\api\module\sms();
        $data = [
            'template_code' => 'SMS_107025019',
            'json_string_param' => ["code" =>$rands],
            'phone' =>$mobile,
            'sign'=>'无维科技'
        ];
        if(!$mobile){
            self::AjaxReturnError('手机号不能为空');
        }
        if($res = $sms->send($data) == true){
            session('qcode',$rands);
            self::AjaxReturn('验证码发送成功',$rands);
        }else{
            self::AjaxReturnError('验证码发送失败,'.$res);
        }
    }
    //匹配成功短信
    public function MatchingSuccess($mobile,$info){
        $sms = new \app\api\module\sms();
        $data = [
            'template_code' => 'SMS_113456357',
            'json_string_param' => ["plate" =>$info['card_number']],
            'phone' =>$mobile,
            'sign'=>'无维科技'
        ];
        self::logger($data,'匹配成功短信');
        if(!$mobile){
            self::AjaxReturnError('手机号不能为空');
            self::logger('手机号不能为空','匹配成功短信');
        }
        if($res = $sms->send($data) == true){
            return true;
        }else{
            return $res;
        }
    }
}