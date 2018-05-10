<?php
namespace app\api\controller;

use app\api\validate;
use app\index\controller\Action;
use think\Db;
use think\session\driver\Redis;

class Nurse extends Action
{
    protected $redis;

    public function __construct()
    {
        parent::__construct();

        $this->redis = new \Redis();
        $this->redis->connect(config('redis.host'), config('redis.hostport'));

    }

    /**
     * 看护订单列表
     * @Author   jfeng
     * @DateTime 2018-03-15T09:37:25+0800
     * @param   $switch(string) user表示普通用户，manager表示管理员
     * @param   $uid(int) 当前用户id
     * @param   $status(int) 状态 4（待看护）、5（看护中）、6（交接中）、7（已完成）
     * @param   $order(string) desc降序  asc升序
     * @param   $limit(int) 瀑布流显示数量
     * @return  json
     */
    public function nurseList()
    {
        $switch = input('switch');
        $status = input('status');

        $where = array(
            'car_status' => $status,
        );

        $order = "nurse_time " . input('order');

        $limit = input('limit');

        if ($status < 4) {
            self::AjaxReturnError('非法的状态');
        }

        //看护中状态大于24小时后变为交接中状态
        if ($status == 5 || $status == 6) {

            if ($switch == 'user') {
                $upwhere['card_uid'] = input('uid');
            }

            $upwhere['car_status'] = 5;
            $upwhere['nurse_time'] = ['<', time() - 24 * 60 * 60];

            Db::name('findcard')->where($upwhere)->update(['car_status' => 6]);
        }

        // 普通用户列表
        if ($switch == 'user') {
            $where['card_uid'] = input('uid');

            $count = Db::name('findcard')
                ->where($where)
                ->count('find_id');

            $list = Db::name('findcard')
                ->join('nurse', 'np_findcard.find_id=np_nurse.fid', 'left')
                ->where($where)
                ->order($order)
                ->limit($limit)
                ->select();

            foreach ($list as $key => $value) {
                $list[$key]['nurse_time'] = date('Y-m-d H:i:s', $value['nurse_time']);
            }
        }

        // 大区经理列表(通过地区筛选)
        if ($switch == 'manager' && config('managerCityProxy')) {
            $uid = input('uid');

            $proxyCity = Db::name('user')->where('user_id', $uid)->value('proxy_city');

            if (!$proxyCity) {
                self::AjaxReturnError('您还没有代理城市，请联系管理员');
            }

            $proxyCity = unserialize($proxyCity);

            $cityWhere = "car_status = " . $status . ' AND (';

            foreach (current($proxyCity) as $city) {
                $cityName = Db::name('city')->where('id', $city)->value('name');
                $cityWhere .= "instr(car_location, '$cityName') > 0 OR "; //instr效率高于like
                // $cityWhere .= "(car_location like '%$cityName%' ) OR ";
            }

            $cityWhere = rtrim($cityWhere, 'OR ');

            $cityWhere .= ")";

            $count = Db::name('findcard')
                ->join('cardata', 'np_findcard.card_cardata=np_cardata.car_id', 'left')
                ->where($cityWhere)
                ->count('find_id');
            // echo $count;die;

            $list = Db::name('findcard')
                ->join('cardata', 'np_findcard.card_cardata=np_cardata.car_id', 'left')
                ->join('nurse', 'np_findcard.find_id=np_nurse.fid', 'left')
                ->where($cityWhere)
                ->order($order)
                ->limit($limit)
                // ->fetchSql()
                ->select();

            foreach ($list as $key => $value) {
                $list[$key]['nurse_time'] = date('Y-m-d H:i:s', $value['nurse_time']);
            }
        }

        // 大区经理列表(通过识别机筛选)
        if ($switch == 'manager' && !config('managerCityProxy')) {
            $uid = input('uid');

            //获取该大区经理的识别机
            $machines = Db::name('machine')
                ->where('uid', $uid)
                ->column('id');

            if (!$machines) {
                self::AjaxReturnError('您还没有识别机！');
            }

            $machine = @implode(',', $machines);

            //查找这些识别机下需要看护的车辆
            $where['machine_id'] = ['in', $machine];

            $count = Db::name('findcard')
                ->join('cardata', 'np_findcard.card_cardata=np_cardata.car_id', 'left')
                ->where($where)
                ->count('find_id');

            $list = Db::name('findcard')
                ->join('cardata', 'np_findcard.card_cardata=np_cardata.car_id', 'left')
                ->join('nurse', 'np_findcard.find_id=np_nurse.fid', 'left')
                ->where($where)
                ->order($order)
                ->limit($limit)
                // ->fetchSql()
                ->select();

            foreach ($list as $key => $value) {
                $list[$key]['nurse_time'] = date('Y-m-d H:i:s', $value['nurse_time']);
            }
        }

        $data = [
            'list' => $list,
            'count' => $count,
        ];

        $msg = $count > 0 ? '获取数据列表成功' : '暂无数据';

        self::AjaxReturn($data, $msg);

    }

    /**
     * 生成、查看验证码
     * @Author   jfeng
     * @DateTime 2018-03-15T11:35:23+0800
     * @param    int $find_id 看护订单id
     * @param    int $uid 用户id
     * @return   $code
     */
    public function productCode()
    {
        $uid = input('uid');
        $findId = input('find_id');

        $findCardData = Db::name('findcard')->where('find_id', $findId)->field('car_status, card_uid')->find();

        if ($findCardData['card_uid'] != $uid) {
            self::AjaxReturnError('您没有权限访问该订单');
        }

        if ($findCardData['car_status'] < 4) {
            self::AjaxReturnError('非法的状态');
        }

        $code = rand(100000, 999999);
        $prefix = config('redis.prefix');
        $codeKey = $prefix . 'find_id' . $findId;
        $codeValue = [
            'code' => $code,
            'find_id' => $findId,
            'uid' => $uid,
        ];

        $findCarKey = $this->redis->exists($codeKey);

        if ($findCarKey) {
            $code = $this->redis->hGet($codeKey, 'code');
        } else {
            $this->redis->hmset($codeKey, $codeValue);
        }

        self::AjaxReturn($code, '验证码获取成功');

    }

    /**
     * 匹配验证码
     * @Author   jfeng
     * @DateTime 2018-03-15T15:33:09+0800
     * @param    int $find_id 订单id
     * @param    int $code 验证码
     * @return   json
     */
    public function matchCode()
    {
        $findId = input('find_id');
        $code = input('code');
        $prefix = config('redis.prefix');
        $codeKey = $prefix . 'find_id' . $findId;

        $findCardData = Db::name('findcard')->where('find_id', $findId)->field('car_status')->find();

        if ($findCardData['car_status'] < 5 || $findCardData['car_status'] > 6) {
            self::AjaxReturnError('非法的状态');
        }

        $findCarKey = $this->redis->exists($codeKey);

        if (!$findCarKey) {
            self::AjaxReturnError('该订单还没有验证码，请联系管理员');
        }

        $systemCodeData = $this->redis->hGetAll($codeKey);

        if ($systemCodeData['code'] == $code) {
            Db::name('findcard')->where('find_id', $findId)->update(['car_status' => 7]);
        } else {
            self::AjaxReturnError('您输入的验证码匹配不成功');
        }

        self::AjaxReturn($code, '验证码匹配成功,订单交接完成');

    }

    /**
     * 看护车辆
     * @Author   jfeng
     * @DateTime 2018-03-15T17:18:29+0800
     * @param    int $find_id 订单id
     * @param    int $uid 大区经理id
     * @param    string $nurse_name 看护人姓名
     * @param    string $nurse_phone 看护人电话
     * @return   json
     */
    public function nurseCar()
    {
        $findId = input('find_id');
        $name = input('nurse_name');
        $phone = input('nurse_phone');

        $findCardData = Db::name('findcard')->where('find_id', $findId)->field('car_status, card_uid, card_number')->find();

        if ($findCardData['car_status'] != 4) {

            self::AjaxReturnError('非法的状态');

        }

        if (strlen(trim($name)) == 0) {

            self::AjaxReturnError('姓名不能为空');

        }

        if (!preg_match("/^1[34578]{1}\d{9}$/", $phone)) {

            self::AjaxReturnError('手机号不正确，请重新填写');

        }

        $data = [
            'fid' => $findId,
            'nurse_name' => $name,
            'nurse_phone' => $phone,
            'card_addtime' => time(),
        ];

        $result = Db::name('nurse')->insert($data);

        if ($result) {

            Db::name('findcard')->where('find_id', $findId)->update(['car_status' => 5]);
            $content .= "<p>您的车辆 " . $findCardData['card_number'] . '正在看护中。</p>';
            $content .= "<p>看护人员：" . $name . "</p>";
            $content .= "<p>联系方式：" . $phone . "</p>";
            $content .= "<p>看护时间：" . date('Y年m月d日 H:i:s', $data['card_addtime']) . "</p>";
            $content .= "<p>请尽快联系看护人员！</p>";

            $this->sendMessage(input('uid'), $findCardData['card_uid'], $content);

            self::AjaxReturn($result, '添加成功');

        } else {

            self::AjaxReturnError('添加失败');

        }

    }

    //给用户发送消息
    private function sendMessage($fromUid, $uid, $content)
    {
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

    /**
     * 看护订单详情
     * @Author   jfeng
     * @DateTime 2018-03-15T17:35:19+0800
     * @param    int $find_id 订单id
     * @return   json
     */
    public function nurseCarDetail()
    {
        $findId = input('find_id');

        $data = Db::name('findcard')
            ->join('nurse', 'np_findcard.find_id=np_nurse.fid', 'left')
            ->where('np_findcard.find_id', $findId)
            ->find();

        if (!$data) {
            self::AjaxReturnError('没有该订单');
        }

        self::AjaxReturn($data, '成功获取订单详情');

    }

    public function redisSetTest()
    {
        $codeValue = 45454545;
        $this->redis->sAdd('settest', $codeValue);

        var_dump($this->redis->SMEMBERS('settest')) ;
    }

    //查询该订单是否支付完成
    public function paystatus()
    {
        $findId = input('find_id');
        $switch = input('switch');
        $user_id = input('user_id');
        if ($switch == 'nurse') {
            $info = Db::name('findcard')->where(['find_id'=>$findId])->find();

            if(!empty($info)){
                if($info['car_status'] == 4){
                    self::AjaxReturn('支付成功','',2);
                }else if ($info['pay_status'] > 4){
                    self::AjaxReturn('此订单已结完成,不能重复支付','',0);
                }else{
                    self::AjaxReturn('该订单未支付','',-1);
                }
            }
        };
        if ($switch == 'deposit') {
            $info = Db::name('user')->where(['user_id'=>$user_id])->find();

            if(!empty($info)){
                if($info['deposit'] == 2){
                    self::AjaxReturn('支付成功','',2);
                }else{
                    self::AjaxReturn('该订单未支付','',-1);
                }
            }
        }
    }

}
