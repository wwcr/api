<?php
namespace app\api\controller;

use app\api\validate;
use app\index\controller\Action;
use think\Db;
use think\session\driver\Redis;

class Crontab extends Action
{
    protected $redis;

    public function __construct()
    {
        parent::__construct();

        $this->redis = new \Redis();
        $this->redis->connect(config('redis.host'), config('redis.hostport'));

    }

    /**
     * 报警车辆存在24小时内没有支付订单定时任务
     *
     * ********开启押金模式*******
     * 扣除该用户的押金 deposit = 3
     * 取消该用户订单   recycle = 0
     * 显示第一个被找到车的位置权限 pay_status = 2
     * *****************************
     *
     * ********未开启押金模式*******
     * 取消该用户订单   recycle = 0
     * *****************************
     * @Author   jfeng
     * @DateTime 2018-04-04T16:21:52+0800
     */
    public function checkPayOvertime()
    {
        //查找所有时间超过24小时的未支付的报警车辆
        $where = array(
            'pay_status' => 1,
            'car_status' => 2,
            'recycle'    => 1,
            'car_addtime' => ['<', time()-24*60*60],
        );

        $lists = Db::name('findcard')
            ->join('np_pay','np_pay.pay_order=np_findcard.card_order','left')
            ->join('np_cardata','np_cardata.car_id=np_findcard.card_cardata','left')
            ->where($where)
            ->field('find_id, pay_status, car_status, car_addtime, card_uid, car_id, card_order')
            ->order('car_addtime', 'ASC')
            ->select();
        if (empty($lists)) {
            return ;
        }

        //取出用户集合和订单集合
        $userIds = '';
        $findIds = '';

        foreach ($lists as $key => $list) {
            $users[] = $list['card_uid'];
            $finds[] = $list['find_id'];
        }

        $userIds = implode(',', array_unique($users));
        $findIds = implode(',', array_unique($finds));

        if (config('depositButton')) {

            $this->depositModeFlow($userIds, $findIds, $where);

        } else {

            $this->notDepositModeFlow($userIds);
        }

    }

    //开启押金模式流程
    private function depositModeFlow($userIds, $findIds, $where)
    {
        //扣除押金、取消订单
        Db::name('user')->where('user_id in (' . $userIds . ')')->update(['deposit' => 3]);
        $result = Db::name('findcard')->where(['card_uid' =>['in', $userIds], 'car_status' => 1, 'recycle' => 1])->update(['recycle' => 2]);

        //显示每个用户第一个被找到的车辆信息
        if ($result) {
            $userGroupLists = Db::name('findcard')
                ->join('np_pay','np_pay.pay_order=np_findcard.card_order','left')
                ->join('np_cardata','np_cardata.car_id=np_findcard.card_cardata','left')
                ->where($where)
                ->field('find_id, pay_status, car_status, car_addtime, card_uid, car_id, card_order')
                ->order('car_addtime', 'ASC')
                ->group('card_uid')
                ->select();
            foreach ($userGroupLists as $userList) {
                $fids[] = $userList['find_id'];
                $payOrders[] = $userList['card_order'];
            }

            $payOrders = implode(',', array_unique($payOrders));
            $fids = implode(',', array_unique($fids));

            Db::name('findcard')->where('find_id in (' . $findIds . ')')->update(['car_status' => 3, 'recycle' => 1]);
            Db::name('pay')->where('pay_order in (' . $payOrders . ')')->update(['pay_status' => 2]);
        }
    }

    //非押金模式流程
    private function notDepositModeFlow($userIds)
    {
        //取消订单
        $result = Db::name('findcard')->where(['card_uid' =>['in', $userIds], 'car_status' => 1, 'recycle' => 1])->update(['recycle' => 0]);
    }

    //备份 2018-4-25
    public function checkPayOvertime_old()
    {
        //查找所有时间超过24小时的未支付的报警车辆
        $where = array(
            'pay_status' => 1,
            'car_status' => 2,
            'recycle'    => 1,
            'car_addtime' => ['<', time()-24*60*60],
        );

        $lists = Db::name('findcard')
            ->join('np_pay','np_pay.pay_order=np_findcard.card_order','left')
            ->join('np_cardata','np_cardata.car_id=np_findcard.card_cardata','left')
            ->where($where)
            ->field('find_id, pay_status, car_status, car_addtime, card_uid, car_id, card_order')
            ->order('car_addtime', 'ASC')
            ->select();
        if (empty($lists)) {
            return ;
        }
        // var_dump($lists);die;

        //取出用户集合和订单集合
        $userIds = '';
        $findIds = '';

        foreach ($lists as $key => $list) {
            $users[] = $list['card_uid'];
            $finds[] = $list['find_id'];
        }

        $userIds = implode(',', array_unique($users));
        $findIds = implode(',', array_unique($finds));

        //扣除押金、取消订单
        Db::name('user')->where('user_id in (' . $userIds . ')')->update(['deposit' => 3]);
        $result = Db::name('findcard')->where(['card_uid' =>['in', $userIds], 'car_status' => 1, 'recycle' => 1])->update(['recycle' => 0]);

        //显示每个用户第一个被找到的车辆信息
        if ($result) {
            $userGroupLists = Db::name('findcard')
                ->join('np_pay','np_pay.pay_order=np_findcard.card_order','left')
                ->join('np_cardata','np_cardata.car_id=np_findcard.card_cardata','left')
                ->where($where)
                ->field('find_id, pay_status, car_status, car_addtime, card_uid, car_id, card_order')
                ->order('car_addtime', 'ASC')
                ->group('card_uid')
                ->select();
            foreach ($userGroupLists as $userList) {
                $fids[] = $userList['find_id'];
                $payOrders[] = $userList['card_order'];
            }

            $payOrders = implode(',', array_unique($payOrders));
            $fids = implode(',', array_unique($fids));

            Db::name('findcard')->where('find_id in (' . $findIds . ')')->update(['car_status' => 3, 'recycle' => 1]);
            Db::name('pay')->where('pay_order in (' . $payOrders . ')')->update(['pay_status' => 2]);
        }

    }




}
