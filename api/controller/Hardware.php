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
            $machineId = input('machine_id') ? input('machine_id') : -1;
            $validateCard = input('validate_card');
            $gps = input('gps');

            // if (trim($validateCard) == '') {
            //     self::AjaxReturn('车牌号不合法', '', 0);
            // }

            $insert = array(
                'car_card' => input('card'), //车牌
                'car_location' => input('location'), //地址
                'car_photo' => input('photo'), //照片
                'validate_card' => $validateCard, //校验车牌
                'machine_id' => $machineId, //设备id
                'car_gps' => $gps, //gps号码
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

            // if ($validateCard == input('card')) {
            //     $insert['type'] = 1;
            // } else {
            //     $insert['type'] = 2;
            // }
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

    public function insertData($insert)
    {
        $result = Db::name('cardata')->insertGetId($insert);
        if ($result) {
            //更新匹配
            $math = new Matching($insert);
            $result = $math->inits($insert['car_card'], $result, $insert);
            //车牌匹配, 立刻返回结果
            self::logger($result,'更新匹配',4);
            self::AjaxReturn('添加成功', $result);
        } else {
            self::AjaxReturn('添加失败', '', 0);
        }
    }

    //设备详情
    public function machineInfo()
    {
        $id = input('id'); //设备主键id
        $formatData = [];

        $info = Db::name('machine')->where('id', $id)->field('password', true)->find();
        $formatData['data'] = $info;

        if (!$info) {
            self::AjaxReturn($info, '设备错误', 0);
        }

        $carData = Db::name('cardata')->where('machine_id', $info['id'])->select();
        $formatData['car_data']['list'] = $carData;

        $weekTime = time() - (7 * 24 * 60 * 60);
        $monthTime = time() - (30 * 24 * 60 *60);

        foreach ($carData as $key => $data) {
            $formatData['car_data']['car_card_list'][] = $data['car_card'];
            if(strtotime($data['car_addtime']) >= $weekTime) {
                $formatData['car_data']['week_list'][] = $data;
                $formatData['car_data']['car_card_weeklist'][] = $data['car_card'];
            }

            if(strtotime($data['car_addtime']) >= $monthTime) {
                $formatData['car_data']['month_list'][] = $data;
                $formatData['car_data']['car_card_monthlist'][] = $data['car_card'];
            }
        }

        $formatData['car_data']['total_num'] = count($formatData['car_data']['list']); //拍摄总数
        $formatData['car_data']['week_num']  = count($formatData['car_data']['week_list']); //近7天拍摄总数
        $formatData['car_data']['month_num'] = count($formatData['car_data']['month_list']); //近30天拍摄总数

        $formatData['car_data']['total_find_num'] = $this->findCarStatistics($formatData['car_data']['car_card_list']); //报警总数
        $formatData['car_data']['week_find_num']  = $this->findCarStatistics($formatData['car_data']['car_card_weeklist']); //近7天报警数
        $formatData['car_data']['month_find_num'] = $this->findCarStatistics($formatData['car_data']['car_card_monthlist']); //近30天报警数

        self::AjaxReturn( $formatData, '数据获取成功', 1);
    }

    private function findCarStatistics($car_cards = array())
    {
        $cards = array_unique($car_cards);
        $count = 0;

        foreach ($cards as $key => $card) {
            $findcard = Db::name('findcard')->where('card_number',$card)->field('card_number')->find();

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

            self::AjaxReturn('账号或密码错误', 0);

        }

    }

    public function signOut()
    {
        session_start();
        // $id = $_SESSION['machine']['id'];
        $id = session('machine')['id'];

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
        $id = session('machine')['id'];
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