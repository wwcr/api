<?php
/**
 * Created by PhpStorm.
 * User: iyahe@qq.com (天明)
 * Date: 2017/11/13
 * Time: 13:26
 */
namespace app\index\module;
use think\Cache;
use think\Model;

/** 急速数据api接口
 * Class jisuap
 * @package app\api\module
 */
class Jpush extends Model
{
    protected $staffModel;
    protected $attendModel;
     
    // 极光的key和secret，在极光注册完应用后，会生成
    protected $app_key = 'ee2df2ee4b65d3e3d934abe3'; //填入你的app_key
    protected $master_secret = '8cf803d04debf20dd788c937'; //填入你的master_secret
    /**
     * 推送所有人
     */
        function notifyAllUser()
    {
        $client = new \JPush\Client('ee2df2ee4b65d3e3d934abe3', '8cf803d04debf20dd788c937');
        $msg = array(
            "extras" => array(
                "name" => "sb-hongpeifeng",
                "id"    => 1,
                "idcard_no" => "41142419874562545",
                "id_img" => "http://000.000.000.000:9090/uploads/20170619/2d9e87fe911f5aa1af80f7c29d13c332.jpg",
                "rec_img" => "http://000.000.000.000:9090/uploads/20170619/3b9471fa251c837a437acc8259aeaf59.jpg",
                "gate_addr" => "1"
            )
        );
        $result = $client->push()
        // ->addAllAudience() // 推送所有观众
         // ->addAllAudience(['registration_id'=>'1a0018970af4bef5c3f']) 
         ->addRegistrationId('1a0018970af4bef5c3f')
        ->setPlatform('all')
        ->androidNotification($alert = '孙燕飞是傻子', [
   'title' => '通知',
   "builder_id" => 3, 
   "style" =>1,  // 1,2,3
   "alert_type" =>1, // -1 ~ 7
   "big_text" =>"孙燕飞是傻子孙燕飞是傻子孙燕飞是傻子孙燕飞是傻子孙燕飞是傻子",
])
        ->send();
         $result_json = json_encode($result);
        // var_dump(json_decode($result_json, true));
        
    }
 
/**
 * 将数据先转换成json,然后转成array
 */
    function json_array($result)
    {
        $result_json = json_encode($result);
        return json_decode($result_json, true);
    }
 
/**
 * 向特定设备推送消息
 * @param array $regid 接收推送的设备标识
 * @param string $message 需要推送的消息体
 * return array
 */
    function sendNotifySpecial($regid, $alert, $message, $app_key, $master_secret)
    {
        $client = new \JPush\Client($app_key, $master_secret);
     
        $result = $client->push()
        ->addAllAudience() // 推送所有观众
        ->setPlatform('all')
        // ->message($message, $msg) // 应用内消息
        ->addAlias($regid) // 给别名推送
        ->iosNotification($alert, $message)
        ->androidNotification($alert, $message)
        ->send();
        return json_array($result);
    }
    public function push_test()
    {
        $alert = "您收到一条消息";
        $msg = array(
            "extras" => array(
                "name" => "sb-hongpeifeng",
                "id"    => 1,
                "idcard_no" => "41142419874562545",
                "id_img" => "http://000.000.000.000:9090/uploads/20170619/2d9e87fe911f5aa1af80f7c29d13c332.jpg",
                "rec_img" => "http://000.000.000.000:9090/uploads/20170619/3b9471fa251c837a437acc8259aeaf59.jpg",
                "gate_addr" => "1"
            )
        );
 
        $regid = 'null';
        $result = notifyAllUser($alert, $msg, $this->app_key, $this->master_secret);
        var_dump($result);die;
    }
     
    /**
     * 推送给特定用户
     */
    public function push_special()
    {
        $alert = "您收到一条消息";
        $msg = array(
            "extras" => array(
                "name" => "sb-hongpeifeng",
                "id"    => 1,
                "idcard_no" => "41142419874562545",
                "id_img" => "http://000.000.000.000:9090/uploads/20170619/2d9e87fe911f5aa1af80f7c29d13c332.jpg",
                "rec_img" => "http://000.000.000.000:9090/uploads/20170619/3b9471fa251c837a437acc8259aeaf59.jpg",
                "gate_addr" => "1"
            )
        );
 
        $regid = 'null';
        $result = sendNotifySpecial($regid, $alert, $msg, $this->app_key, $this->master_secret, $device_type = "all", $msg);
        var_dump($result);die;
    }
 
 
}