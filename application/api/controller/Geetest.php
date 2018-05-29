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
use think\Request;
define('CARTCHA_ID',"e00fc7b4e783a1a62a7fd2e9d3c20d23");
define('PRIVATE_KEY',"158cb7b3beafa4ecde07745c54f26801");
class Geetest extends Action
{
   public function init_geetest(){
$request = Request::instance();
    $GtSdk = new GeetestLib(CAPTCHA_ID, PRIVATE_KEY);

		$data = array(
				"user_id" => rand(1,9999), # 网站用户id
				"client_type" => "h5", #web:电脑上的浏览器；h5:手机上的浏览器，包括移动应用内完全内置的web_view；native：通过原生SDK植入APP应用的方式
				"ip_address" => $request->ip() # 请在此处传输用户请求验证时所携带的IP
			);

		$status = $GtSdk->pre_process($data, 1);
		session('gtserver',$status);
		session('user_id',$data['user_id']);
		echo $GtSdk->get_response_str();
   }
   public function second_geetest(){//二次验证
   	$GtSdk = new GeetestLib(CAPTCHA_ID, PRIVATE_KEY);
	$data = array(
	        "user_id" => session('user_id'), # 网站用户id
	        "client_type" => "web", #web:电脑上的浏览器；h5:手机上的浏览器，包括移动应用内完全内置的web_view；native：通过原生SDK植入APP应用的方式
	        "ip_address" => "127.0.0.1" # 请在此处传输用户请求验证时所携带的IP
	    );
	if (session('gtserver') == 1) {   //服务器正常
	    $result = $GtSdk->success_validate(input('post.geetest_challenge'), input('post.geetest_validate'), input('post.geetest_seccode'), $data);
	    if ($result) {
	        echo '{"status":"success"}';
	    } else {
	        echo '{"status":"fail"}';
	    }
	} else {  //服务器宕机,走failback模式
	    if ($GtSdk->fail_validate(input('post.geetest_challenge'),input('post.geetest_validate'),input('geetest_seccode'))) {
	        echo '{"status":"success"}';
	    } else {
	        echo '{"status":"fail"}';
	    }
	}
   }
   public function test1111(){
   	echo 1111;
   	return $this->fetch('show');
   }
}