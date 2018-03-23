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

        if ($data['car_status'] == 2) {
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

            $sms = new Sms();
            $sms->MatchingSuccess($info['user_mobile'],$findcard);
            return '车牌号：'.$findcard['card_number'].'为查找车辆，请粘贴GPS。';
        }else{
            return '';
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

    private function updateCardData($id, $data = array())
    {
        if ($data['validate_card']) {
            return "验证车牌号不能为空";
        }

        if ($data['car_gps']) {
            return "gps不能为空";
        }

        if ($data['validate_card'] == $data['car_card']) {
            $data['type'] = 1;
        } else {
            $data['type'] = 2;
        }

        Db::name('cardata')->where(['car_id'=>$id])->update(['validate_card'=>$data['validate_card'], 'car_gps'=>$data['car_gps'], 'type' => $data['type']]);
    }

}