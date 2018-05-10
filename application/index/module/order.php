<?php
/**
 * Created by PhpStorm.
 * User: iyahe@qq.com (天明)
 * Date: 2017/7/27
 * Time: 11:28
 */
namespace app\index\module;
use think\Controller;
use think\Db;

/** 订单模型
 * Class order
 * @package app\index\module
 */
class order extends model {
    private static $_instance;
    public static function start() {
        if (!self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    public function add($type=1,$price,$uid,$utype=1){
        $order = self::getOrder();
        //生成一条支付记录
        $pay = new pay();
        $order_pay = $pay->add($order,$utype);
        $insert = [
            'order_type'=>$type, //订单类型
            'order_number'=>$order, //订单号
            'order_time'=>time(),
            'order_uid'=>$uid,
            'order_pay'=>$order_pay,
            'order_price'=>$price*100,
        ];
        $addpay = Db::name('order')->insertGetId($insert);
        if($addpay){
            return $order;
        }else{
            return '';
        }
    }
    public function changePrice($order,$price){
        return Db::name('order')->where([
            'order_number'=>$order
        ])->update([
            'order_price'=>(intval($price)*100)]
        );
    }
    public function get_price($order){
        return Db::name('order')->where([
            'order_number'=>$order
        ])->select();
    }
    public function getlist($where,$limit,$type){
        if($type == 1){
            return Db::name('order')
                ->join('nurse','np_nurse.card_order=np_order.order_number')
                ->join('pay','np_order.order_number=np_pay.pay_order')
                ->where($where)
                ->order('order_time DESC')
                ->paginate($limit);
        }else{
            return Db::name('order')
                ->join('findcard','np_findcard.card_order=np_order.order_number')
                ->join('pay','np_order.order_number=np_pay.pay_order')
                ->where($where)
                ->order('order_id DESC')
                ->paginate($limit);
        }
    }
}
