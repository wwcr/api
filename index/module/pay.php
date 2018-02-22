<?php
/**
 * Created by PhpStorm.
 * User: iyahe@qq.com (天明)
 * Date: 2017/7/27
 * Time: 11:35
 */
namespace app\index\module;
use think\Controller;
use think\Db;
class pay{
    /** 新增一个订单
     * @param $order
     * @return int|string
     */
    public function add($order){
        $insert = array(
            'pay_status'=>1,
            'pay_time'=>'',
            'pay_order'=>$order
        );
        return Db::name('pay')->insertGetId($insert);
    }

    /**更改订单状态
     * @param $where 条件
     * @param $type 支付方式
     * @param $status 支付状态
     * @return int|string
     */
    public function orderComplete($where,$type,$status){
        return Db::name('pay')->where($where)->update(array(
            'pay_type'=>$type,
            'pay_time'=>getStrtime(),
            'pay_status'=>$status
        ));
    }
}