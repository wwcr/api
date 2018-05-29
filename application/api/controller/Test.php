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
use geetest\lib\GeetestLib;
define('CARTCHA_ID',"05a95933413c98a5b9f7ed6aa3ff2ccf");
define('PRIVATE_KEY',"af757051409ca80091b11d0364440cdc");
class Test extends Action
{
   public function test(){

    $GtSdk = new GeetestLib(CAPTCHA_ID, PRIVATE_KEY);

		$data = array(
				"user_id" => "test", # 网站用户id
				"client_type" => "h5", #web:电脑上的浏览器；h5:手机上的浏览器，包括移动应用内完全内置的web_view；native：通过原生SDK植入APP应用的方式
				"ip_address" => "127.0.0.1" # 请在此处传输用户请求验证时所携带的IP
			);

		$status = $GtSdk->pre_process($data, 1);
		session('gtserver',$status);
		session('user_id',$data['user_id']);
		echo $GtSdk->get_response_str();
   }
   public function test1111(){
   	return $this->fetch('show');
   }
}