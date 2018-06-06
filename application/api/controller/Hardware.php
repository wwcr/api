<?php
/**
 * Created by PhpStorm.
 * User: iyahe@qq.com (天明)
 * Date: 2017/11/27
 * Time: 14:38
 */
namespace app\api\controller;

use app\index\controller\Action;
use think\Db;

/** 硬件外部接口
 * Class Hardware
 * @package app\api\controller
 */
class Hardware extends Action
{
    public function index()
    {
        echo '硬件';
    }

    public function add()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // file_put_contents(dirname(__FILE__).'/wechattest.txt', serialize(input()) );
            $machineId = input('machine_id') ? input('machine_id') : -1;
            $insert = array(
                'car_card' => input('card'), //车牌
                'car_location' => input('location'), //地址
                'car_photo' => input('photo'), //照片
                'machine_id' => $machineId, //设备id
            );

            $validate = ['请检查车牌号', '请检查地址', '请检查照片'];
            $num = -1;
            foreach ($insert as $k => $v) {
                $num++;
                if (!$insert[$k]) self::AjaxReturn($validate[$num], '', 0);
            }
            $insert['car_hash'] = md5(input('card'));
            $insert['car_addtime'] = getStrtime();
            $insert['car_mark'] = input('mark', '');
            self::logger($insert,'硬件insert',4);
            $isCardata = Db::name('cardata')
                ->where(['car_hash' => $insert['car_hash']])
                ->order('car_id DESC')
                ->find();
            //5分钟内不重复
            if (empty($isCardata)) {
                $this->insertData($insert);
            } else {
                $time = strtotime($isCardata['car_addtime']);
                if ((time() - $time) <= 300) {
                    self::AjaxReturn('该车牌已经添加成功');
                } else {
                    $this->insertData($insert);
                }
            }
        } else {
            self::AjaxReturn('请求失败', '', 0);
        }
    }

    public function add_new()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // file_put_contents(dirname(__FILE__).'/wechattest.txt', serialize(input()) );
            $machineId = input('machine_id') ? input('machine_id') : -1;
            $insert = array(
                'car_card' => input('card'), //车牌
                'car_location' => input('location'), //地址
                'car_photo' => input('photo'), //照片
                'province' => input('province'), //省级
                'city' => input('city'), //市级
                'longitude' => input('longitude'), //经度
                'latitude' => input('latitude'), //纬度
                'machine_id' => $machineId, //设备id
            );

            $validate = ['请检查车牌号', '请检查地址', '请检查照片', '请检查省份', '请检查城市', '请检查经度', '请检查纬度'];
            $num = -1;
            foreach ($insert as $k => $v) {
                $num++;
                if (!$insert[$k]) self::AjaxReturn($validate[$num], '', 0);
            }
            $insert['car_hash'] = md5(input('card'));
            $insert['car_addtime'] = getStrtime();
            $insert['car_mark'] = input('mark', '');
            self::logger($insert,'硬件insert',4);
            $isCardata = Db::name('cardata')
                ->where(['car_hash' => $insert['car_hash']])
                ->order('car_id DESC')
                ->find();
            //3个月内不重复添加 3*30*24*60*60
            if (empty($isCardata)) {
                $data = $this->insertData_new($insert);
                if ($data) {
                    self::AjaxReturn($data, '该车辆是在寻车辆，请完成以下操作。');
                } else {
                    self::AjaxReturn('', '添加失败', 0);
                }
            } else {
                $time = strtotime($isCardata['car_addtime']);
                $timeFormat = time() - $time;
                $maxTime    = 86400;  //24小时
                $findCard = Db::name('findcard')->where('card_number', input('card'))->find();

                // 24小时内 && 没有委单
                if ($timeFormat <= $maxTime && !$findCard) {
                    self::AjaxReturn('', '该车牌已经添加成功');
                }

                // 24小时内 && 存在取消的委单
                if ($timeFormat <= $maxTime && $findCard['recycle'] ==0) {
                    self::AjaxReturn('', '该车牌已经添加成功');
                }

                // 有委单但不是查找中状态
                if ($findCard && $findCard['car_status'] != 1) {
                    self::AjaxReturn('', '该车牌已经添加成功');
                }

                $data = $this->insertData_new($insert);

                if ($data) {
                    self::AjaxReturn($data, '该车辆是在寻车辆，请完成以下操作。');
                } else {
                    self::AjaxReturn('', '添加失败', 0);
                }

                // (一天之内 &&  委单已取消) && 委单已找到）
                // $timeFormat = time() - $time;
                // if (($timeFormat <= 86400 && (!$findCard || $findCard['recycle'] ==0)) || $findCard['car_status'] >1) {
                //     // $data = $this->insertData_new($insert);
                //     self::AjaxReturn('', '该车牌已经添加成功');
                // } else {
                //     $data = $this->insertData_new($insert);
                //     if ($data) {
                //         self::AjaxReturn($data, '该车辆是在寻车辆，请完成以下操作。');
                //     } else {
                //         self::AjaxReturn('', '添加失败', 0);
                //     }
                // }
            }
        } else {
            self::AjaxReturn('', '请求失败', 0);
        }
    }

    public function confirmAndMatchCard()
    {
        $carId = input('car_id');
        $validateCard = input('validate_card');
        $gps = input('gps');
        $carImg = input('car_img');

        if (is_array($carImg)) {
            $carImg = json_encode($carImg);
        }

        $data = Db::name('cardata')
            ->join('findcard', 'np_findcard.card_cardata=np_cardata.car_id', 'left')
            ->join('user', 'np_findcard.card_uid=np_user.user_id')
            ->where('np_cardata.car_id', $carId)
            ->field('user_mobile, car_card')
            ->find();

        $insertData = array(
            'validate_card' => $validateCard, //校验车牌
            'car_card' => $data['car_card'], //扫描识别的车牌
            'car_gps' => $gps, //gps号码
            'car_img' => $carImg,
            'user_mobile' => $data['user_mobile']
        );

        $math = new Matching();
        $result = $math->updateCardData($carId, $insertData);

        if ($result) {
            self::AjaxReturn('', $result);
        } else {
            self::AjaxReturn('', '操作失败', 0);
        }
    }

    //当前找到的车辆详情
    public function findCurrentCardDetail()
    {
        $carId = input('car_id');

        $data = Db::name('cardata')
            ->join('findcard', 'np_findcard.card_cardata=np_cardata.car_id', 'left')
            ->join('user', 'np_findcard.card_uid=np_user.user_id')
            ->where('np_cardata.car_id', $carId)
            ->field('card_number, card_brand, card_color')
            ->find();

        if ($data) {
            self::AjaxReturn($data, '数据获取成功');
        } else {
            self::AjaxReturn($data, '数据获取失败', 0);
        }
    }

    public function insertData($insert)
    {
        $carId = Db::name('cardata')->insertGetId($insert);
        if ($carId) {
            //更新匹配
            $math = new Matching($insert);
            $result = $math->inits($insert['car_card'], $carId, $insert);
            //车牌匹配, 立刻返回结果
            self::logger($result,'更新匹配',4);
            self::AjaxReturn($carId, '该车辆是在寻车辆，请完成以下操作。', 1);
        } else {
            self::AjaxReturn('添加失败', '', 0);
        }
    }
    public function test(){
        $uid = Db::name('machine')->where('id',99)->value('uid');//获取UID
        var_dump($uid);
        //  $user_proxy = self::proxy_get($uid);//判断用户是否有自己的服务器，有就连接
        //  if($user_proxy){///
        //         $insert['car_id'] = $car_id;
        //         $r1 = $user_proxy->name('np_cardata')->insertGetId($insert);//获取ID
        // }
    }
    public function insertData_new($insert)
    {
         $carId = Db::name('cardata')->insertGetId($insert);
         $uid = Db::name('machine')->where('id',$insert['machine_id'])->value('uid');//获取UID
         $user_proxy = self::proxy_get($uid);//判断用户是否有自己的服务器，有就连接
         if($user_proxy){///
                $insert['car_id'] = $car_id;
                $r1 = $user_proxy->name('np_cardata')->insertGetId($insert);//获取ID
        }
        if ($carId) {
            //更新匹配
            $math = new Matching($insert);
            $result = $math->inits_new($insert['car_card'], $carId, $insert,$uid);
            //车牌匹配, 立刻返回结果
            $data['carId'] = $carId;
            $data['isFound'] = $result ? true : false;
            // self::AjaxReturn($carId, '添加成功', 1);
            return $data;
        } else {
            // self::AjaxReturn('添加失败', '', 0);
            return 0;
        }
    }

    //设备详情
    public function machineInfotest()
    {
        $id = input('id'); //设备主键id
        $formatData = [];

        $info = Db::name('machine')->where('id', $id)->field('password', true)->find();
        $formatData['data'] = $info;

        if (!$info) {
            self::AjaxReturn($info, '设备错误', 0);
        }

        // if ($info['is_login'] == 0) {
        //     self::AjaxReturnError('您还未登录请登录');
        // }

        $carData = Db::name('cardata')->where('machine_id', $info['id'])->order('car_addtime desc')->select();
        $formatData['car_data']['list'] = $carData;

        $weekTime = time() - (7 * 24 * 60 * 60);
        $monthTime = time() - (30 * 24 * 60 *60);
        $today = strtotime(date('Y-m-d',time())); //今天0点时间

        foreach ($carData as $key => $data) {
            $formatData['car_data']['car_card_list'][] = $data['car_id'];
            if(strtotime($data['car_addtime']) >= $weekTime) {
                $formatData['car_data']['week_list'][] = $data;
                $formatData['car_data']['car_card_weeklist'][] = $data['car_id'];
            }

            if(strtotime($data['car_addtime']) >= $monthTime) {
                $formatData['car_data']['month_list'][] = $data;
                $formatData['car_data']['car_card_monthlist'][] = $data['car_id'];
            }

            if(strtotime($data['car_addtime']) >= $today) {
                $formatData['car_data']['today_list'][] = $data;
                $formatData['car_data']['car_card_todaylist'][] = $data['car_id'];
            }
        }

        $formatData['car_data']['total_num'] = count($formatData['car_data']['list']); //拍摄总数
        $formatData['car_data']['today_num']  = count($formatData['car_data']['today_list']); //当天拍摄总数
        $formatData['car_data']['week_num']  = count($formatData['car_data']['week_list']); //近7天拍摄总数
        $formatData['car_data']['month_num'] = count($formatData['car_data']['month_list']); //近30天拍摄总数

        $formatData['car_data']['total_find_num'] = $this->findCarStatistics($formatData['car_data']['car_card_list']); //报警总数
        $formatData['car_data']['today_find_num']  = $this->findCarStatistics($formatData['car_data']['car_card_todaylist']); //当天报警数
        $formatData['car_data']['week_find_num']  = $this->findCarStatistics($formatData['car_data']['car_card_weeklist']); //近7天报警数
        $formatData['car_data']['month_find_num'] = $this->findCarStatistics($formatData['car_data']['car_card_monthlist']); //近30天报警数
        unset($formatData['car_data']['list']);
        unset($formatData['car_data']['today_list']);
        unset($formatData['car_data']['week_list']);
        unset($formatData['car_data']['month_list']);
        unset($formatData['car_data']['car_card_list']);
        unset($formatData['car_data']['car_card_todaylist']);
        unset($formatData['car_data']['car_card_weeklist']);
        unset($formatData['car_data']['car_card_monthlist']);
        self::AjaxReturn( $formatData, '数据获取成功', 1);
    }

    private function findCarStatistics($car_cards = array())
    {
        $cards = array_unique($car_cards);
        $count = 0;

        foreach ($cards as $key => $card) {
            // $findcard = Db::name('findcard')->where('card_number',$card)->field('card_number')->find();
            $findcard = Db::name('findcard')->where('card_cardata',$card)->field('card_number')->find();


            if ($findcard) {
                $count++;
            }
        }

        return $count;
    }
     public function machineInfo()
    {
        $id = input('id'); //设备主键id
        $formatData = [];

        $info = Db::name('machine')->where('id', $id)->field('password', true)->find();
        $formatData['data'] = $info;

        if (!$info) {
            self::AjaxReturn($info, '设备错误', 0);
        }

        // if ($info['is_login'] == 0) {
        //     self::AjaxReturnError('您还未登录请登录');
        // }

        $carData = Db::name('cardata')
        ->join('findcard','np_findcard.card_cardata=np_cardata.car_id','left')
        ->where('machine_id', $info['id'])
        ->field('np_cardata.*,np_findcard.find_id')//我不知道需要哪些字段
        ->order('car_addtime desc')
        ->select();
        $formatData['car_data']['list'] = $carData;

        $weekTime = time() - (7 * 24 * 60 * 60);
        $monthTime = time() - (30 * 24 * 60 *60);
        $today = strtotime(date('Y-m-d',time())); //今天0点时间
        $count = 0;
        $total_find_num = 0;
        $today_find_num = 0;
        $week_find_num = 0;
        $month_find_num = 0;
        foreach ($carData as $key => $data) {
            $formatData['car_data']['car_card_list'][] = $data['car_id'];
            if($data['find_id']){
                $total_find_num++;
            }
            if(strtotime($data['car_addtime']) >= $weekTime) {
                $formatData['car_data']['week_list'][] = $data;
                $formatData['car_data']['car_card_weeklist'][] = $data['car_id'];
                 if($data['find_id']){
                $week_find_num++;
            }
            }

            if(strtotime($data['car_addtime']) >= $monthTime) {
                $formatData['car_data']['month_list'][] = $data;
                $formatData['car_data']['car_card_monthlist'][] = $data['car_id'];
                 if($data['find_id']){
                $month_find_num++;
            }
            }

            if(strtotime($data['car_addtime']) >= $today) {
                $formatData['car_data']['today_list'][] = $data;
                $formatData['car_data']['car_card_todaylist'][] = $data['car_id'];
                 if($data['find_id']){
                    $today_find_num++;
            }
            }
        }

        $formatData['car_data']['total_num'] = count($formatData['car_data']['list']); //拍摄总数
        $formatData['car_data']['today_num']  = count($formatData['car_data']['today_list']); //当天拍摄总数
        $formatData['car_data']['week_num']  = count($formatData['car_data']['week_list']); //近7天拍摄总数
        $formatData['car_data']['month_num'] = count($formatData['car_data']['month_list']); //近30天拍摄总数

        $formatData['car_data']['total_find_num'] = $total_find_num; //报警总数
        $formatData['car_data']['today_find_num']  = $today_find_num; //当天报警数
        $formatData['car_data']['week_find_num']  = $week_find_num; //近7天报警数
        $formatData['car_data']['month_find_num'] = $month_find_num; //近30天报警数
        unset($formatData['car_data']['list']);
        unset($formatData['car_data']['today_list']);
        unset($formatData['car_data']['week_list']);
        unset($formatData['car_data']['month_list']);
        unset($formatData['car_data']['car_card_list']);
        unset($formatData['car_data']['car_card_todaylist']);
        unset($formatData['car_data']['car_card_weeklist']);
        unset($formatData['car_data']['car_card_monthlist']);
        self::AjaxReturn( $formatData, '数据获取成功', 1);
    }
     private function findCarStatisticstest($car_cards = array())
    {
        $cards = array_unique($car_cards);
        $count = 0;

        foreach ($cards as $key => $card) {
            // $findcard = Db::name('findcard')->where('card_number',$card)->field('card_number')->find();
            $findcard = Db::name('findcard')->where('card_cardata',$card)->field('card_number')->find();


            if ($findcard) {
                $count++;
            }
        }

        return $count;
    }
    public function loginMachine()
    {
        $account  = input('account');
        $password = input('password');

        if (!$account) {
            self::AjaxReturn( $account, '账号不能为空', 400);
        }

        if (!$password) {
            self::AjaxReturn( $password, '密码不能为空', 401);
        }

        $info = Db::name('machine')->where('account', $account)->find();

        if (!$info) {
            self::AjaxReturn('','该账号不存在', 402);
        }

        if ($info['status'] == 0) {
            self::AjaxReturn( '','该账号已经失效', 403);
        }

        if ($info['is_login'] == 1) {
            self::AjaxReturn( '','该账号已经登录不能重复登录！', 404);
        }

        if ($info['password'] == md5(sha1($password))) {

            Db::name('machine')->where('id', $info['id'])->update(['is_login' => 1]);

            session_start();
            $info['is_login'] = 1;
            // $_SESSION['machine'] = $info;
            session('machine', $info);

            // self::AjaxReturn($_SESSION['machine'], '登录成功', 1);
            self::AjaxReturn(session('machine'), '登录成功', 1);

        } else {

            self::AjaxReturn('', '账号或密码错误', 0);

        }

    }

    public function signOut()
    {
        session_start();
        // $id = $_SESSION['machine']['id'];
        // $id = session('machine')['id'];
        $id = input('mid');
        $result = Db::name('machine')->where('id',$id)->update(['is_login' => 0]);

        if ($result) {
            // $_SESSION['machine'] = null;
            session('machine', null);

            self::AjaxReturn($id, '退出成功', 1);

        } else {
            self::AjaxReturn($id, '退出失败', 0);
        }

    }

    public function editMachinePwd()
    {
        session_start();
        // $id = $_SESSION['machine']['id'];
        // $id = session('machine')['id'];
        $id = input('mid');
        $oldPwd = input('oldpwd');
        $newPwd1 = input('newpwd1');
        $newPwd2 = input('newpwd2');

        $info = Db::name('machine')->where('id', $id)->find();

        if (!$info) {

            self::AjaxReturn('', '账号有误', 0);

        }

        if ($newPwd1 != $newPwd2) {

            self::AjaxReturn('', '两次输入的新密码不一致', 0);

        }

        if ($info['password'] == md5(sha1($oldPwd))) {

            $newPwd = md5(sha1($newPwd1));

            $result = Db::name('machine')->where('id', $id)->update(['password' => $newPwd, 'is_login' => 0]);

            if ($result) {
                // $_SESSION['machine'] = null;
                session('machine', null);
                self::AjaxReturn($result, '修改成功', 1);
            } else {
                self::AjaxReturn($result, '修改失败', 0);
            }

        } else {

            self::AjaxReturn('', '旧密码错误', 0);

        }

    }

}
