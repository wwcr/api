<?php
/**
 * Created by PhpStorm.
 * User: iyahe@qq.com (天明)
 * Date: 2017/10/26/026
 * Time: 21:17
 */
namespace app\api\controller;
use app\index\controller\Action;
use app\index\module;
use think\Db;
use app\api\validate;
use app\index\module\Jpush;//极光推送

/**车联匹配
 * Class Matching
 * @package app\api\controller
 */
class Matching extends Action
{
    public $data;
    public function __construct($data)
    {
        parent::__construct();
        $this->data = $data;
    }
    //每次添加车辆信息时候更新一次
    public function inits($hash,$id, $data){
        $data = Db::name('findcard')
            ->where(['card_number'=>$hash])
            ->find();

        if ($data['car_status'] >= 2) {
            return '';
        }

        $order = Db::name('findcard')
            ->where(['card_number'=>$hash])
            ->order('card_addtime DESC')
            ->update(['car_status'=>2,'card_cardata'=>$id]);
        //车牌匹配ok
        if($order){
            // $this->updateCardData($id, $data);

            $findcard = Db::name('findcard')
                ->where(['card_number'=>$hash])
                ->field('card_uid,card_number')
                ->find();
            $info = Db::name('user')
                ->where(['user_id'=>$findcard['card_uid']])
                ->field('user_mobile')->find();

            $this->sendMessage($findcard['card_uid'], $findcard['card_number']);
            //发短信，更新版本后这里的发短信取消
            $sms = new Sms();
            $sms->MatchingSuccess($info['user_mobile'],$findcard);

            return '车牌号：'.$findcard['card_number'].'为查找车辆，请粘贴GPS。';
        }else{
            return '';
        }
    }

    public function inits_new($hash,$id, $data,$uid){
        $data = Db::name('findcard')
            ->where(['card_number'=>$hash])
            ->find();
// var_dump($data);die;
        if ($data['car_status'] != 1 ) {
            return '';
        }

        $order = Db::name('findcard')
            ->where(['card_number'=>$hash, 'recycle' => 1])
            ->order('card_addtime DESC')
            ->update(['card_cardata'=>$id]);
        $user_proxy = self::proxy_get($uid);//判断用户是否有自己的服务器，有就连接
         if($user_proxy){///
            Db::name('findcard')
            ->where(['card_number'=>$hash, 'recycle' => 1])
            ->order('card_addtime DESC')
            ->update(['card_cardata'=>$id]);
        }
        // $order = Db::name('findcard')
        //     ->where(['card_number'=>$hash, 'recycle' => 1])
        //     ->order('card_addtime DESC')
        //     ->find();
        //车牌匹配ok
        if($order){
            // $this->updateCardData($id, $data);

            // $findcard = Db::name('findcard')
            //     ->where(['card_number'=>$hash])
            //     ->field('card_uid,card_number')
            //     ->find();
            // $info = Db::name('user')
            //     ->where(['user_id'=>$findcard['card_uid']])
            //     ->field('user_mobile')->find();

            // $this->sendMessage($findcard['card_uid'], $findcard['card_number']);
            //发短信，更新版本后这里的发短信取消
            // $sms = new Sms();
            // $sms->MatchingSuccess($info['user_mobile'],$findcard);

            return true;
        }else{
            return 0;
        }
    }

    private function sendMessage($uid,$card_number)
    {
        $content = '车辆'.$card_number.'已经找到';
        $data = array(
            'from_uid' => 0,
            'uid' => $uid,
            'content' => $content,
            'to_usermobile' => 0,
            'from_deleted' => 0,
            'date' => time(),
        );
        $mid = Db::name('message')->insertGetId($data);

        $insertOne = array(
            'mid' => $mid,
            'to_uid' => $uid,
            'is_readed' => 0,
            'is_deleted' => 1,
        );

        Db::name('mesreceiver')->insert($insertOne);
    }

    public function updateCardData($id, $data = array())
    {
        if (!$data['validate_card']) {
            return "验证车牌号不能为空";
        }

        if (!$data['car_gps']) {
            return "gps不能为空";
        }

        if (!$data['car_img']) {
            return "上传凭证不能为空";
        }

        if ($data['validate_card'] == $data['car_card']) {
            $data['type'] = 1;
            $data['card_number'] = $data['car_card'];

            $order = Db::name('findcard')
                ->where(['card_number'=>$data['car_card'], 'recycle' => 1])
                ->order('card_addtime DESC')
                ->update(['car_status'=>2]);

            $findcard = Db::name('findcard')
                ->where(['card_number'=>$data['car_card']])
                ->field('card_uid,card_number')
                ->find();

            $user_proxy = self::proxy_get($findcard['card_uid']);//判断用户是否有自己的服务器，有就连接
             if($user_proxy){///
                    $user_proxy->name('np_findcard')
                    ->where(['card_number'=>$data['car_card'], 'recycle' => 1])
                    ->order('card_addtime DESC')
                    ->update(['car_status'=>2]);//获取ID
            }
            $this->sendMessage($findcard['card_uid'], $findcard['card_number']);

            $sms = new Sms();
            $sms->MatchingSuccess($data['user_mobile'], $data);
            $jpushid = Db::name('user')
                ->where('user_mobile',$data['user_mobile'])
                ->field('jpush')
                ->find();
                //调用极光推送
            $jpush_data = '您所查找的车辆'.$findcard['card_number'].'位置已发送,请关注';//通知内容
            if($jpushid['jpush']){
                $jpush = new Jpush();
                $jpush->notifyAllUser($jpushid['jpush'],$jpush_data);//极光
            }
        } else {
            $data['type'] = 2;
        }

        $result = Db::name('cardata')->where(['car_id'=>$id])->update(['validate_card'=>$data['validate_card'], 'car_gps'=>$data['car_gps'], 'type' => $data['type'], 'car_img' => $data['car_img']]);

        if ($result) {
            return '操作成功';
        } else {
            return 0;
        }

    }

}
