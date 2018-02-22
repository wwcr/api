<?php
/**
 * Created by PhpStorm.
 * User: iyahe@qq.com (天明)
 * Date: 2017/10/23
 * Time: 14:14
 */
namespace app\api\controller;

use app\index\controller\Action;
use think\Db;
use think\Validate;
use \think\Cookie;
use \think\Session;

class Message extends Action
{
    public function index()
    {}

    //创建消息
    public function create()
    {
        $data = input();
        $roule = [
            'content' => 'require',
            // 'send_to_usermobile' => 'require',
        ];
        $message = [
            'content.require' => '内容不能为空',
            // 'send_to_usermobile.require' => '收件账号不能为空',
        ];
        $validate = new Validate($roule, $message);
        $result = $validate->check($data);

        if (!$result) {
            self::AjaxReturnError($validate->getError());
        } else {
            $userMobile = input('send_to_usermobile');
            //账号存在时给该账号发消息，账号为空时给全员发布消息
            if ($userMobile) {
                $uid = Db::name('user')->where('user_mobile', $userMobile)->value('user_id');
                if (!$uid) {
                    self::AjaxReturnError('您输入的账号不存在，请检查后重新输入！');
                }
            } else {
                $map = [];
                $map['user_status'] = 1;
                $uid = Db::name('user')->where($map)->column('user_id');
            }

            $adminData = session('user');
            $data = array(
                'from_uid' => $adminData['ad_id'] ?: 0,
                'uid' => $uid,
                'content' => input('content'),
                'to_usermobile' => $userMobile,
                'from_deleted' => 0,
                'date' => time(),
            );
            $r = Db::name('message')->insertGetId($data);

            if (!$r) {
                self::AjaxReturnError('消息发送失败，请稍后再试');
            }

            if (count($uid) > 1) {
                foreach ($uid as $id) {
                    $insertOne = array(
                        'mid' => $r,
                        'to_uid' => $id,
                        'is_readed' => 0,
                        'is_deleted' => 1,
                    );

                    Db::name('mesreceiver')->insert($insertOne);
                }

            } else {
                $insertOne = array(
                    'mid' => $r,
                    'to_uid' => $uid,
                    'is_readed' => 0,
                    'is_deleted' => 1,
                );

                Db::name('mesreceiver')->insert($insertOne);
            }

            self::returnLuck($r);
        }
    }
    public function listd_page()//消息列表分页重写
    {
        $limit = input('limit');
        // $admin = input('admin', '');
        $field = '';
        $where = [];
        $where['from_deleted'] = 0;

        if (input('switch') == 'admin') {
            $list = Db::name('message')
                ->join('np_user', 'np_user.user_id=np_message.uid', 'left')
                ->where($where)
                ->field($field)
                ->order('date DESC')
                ->select();
                self::ajaxReturnSuccess('ok', $list);
        }

        if (input('switch') == 'user') {
            $user_id = input('user_id');

            if (!$user_id) {
                self::AjaxReturnError('获取用户失败');
            }

            $where['is_deleted'] = 1;
            $where['to_uid'] = $user_id;
             $allcount = Db::name('mesreceiver')
                ->join('np_message', 'np_mesreceiver.mid=np_message.mid', 'left')
                ->where($where)
                ->field($field)
                ->select();
        $count = count($allcount);
            $list = Db::name('mesreceiver')
                ->join('np_message', 'np_mesreceiver.mid=np_message.mid', 'left')
                ->where($where)
                ->field($field)
                ->limit($limit)
                ->order('date DESC')
                ->select();
                self::AjaxReturn($list,$count);
        }

        
    }

    //消息列表
    public function listd()
    {
        $limit = input('limit', 12);
        // $admin = input('admin', '');
        $field = '';
        $where = [];
        $where['from_deleted'] = 0;

        if (input('switch') == 'admin') {
            $list = Db::name('message')
                ->join('np_user', 'np_user.user_id=np_message.uid', 'left')
                ->where($where)
                ->field($field)
                ->order('date DESC')
                ->select();
        }

        if (input('switch') == 'user') {
            $user_id = input('user_id');

            if (!$user_id) {
                self::AjaxReturnError('获取用户失败');
            }

            $where['is_deleted'] = 1;
            $where['to_uid'] = $user_id;

            $list = Db::name('mesreceiver')
                ->join('np_message', 'np_mesreceiver.mid=np_message.mid', 'left')
                ->where($where)
                ->field($field)
                ->order('date DESC')
                ->select();
        }

        self::ajaxReturnSuccess('ok', $list);
    }

    //消息标为已读
    public function content()
    {
        $rid = input('rid');
        $mid = input('mid');

        $messageData = Db::name('message')->where('mid', $mid)->find();

        if (!$messageData) {
            self::AjaxReturnError('该消息已被删除');
        }

        Db::name('mesreceiver')->where('rid', $rid)->update(['is_readed' => 1]);

        self::ajaxReturnSuccess('ok', $messageData);

    }

    public function deleteMessage()
    {
        $mid = input('mid');
        $switch = input('switch');

        if ($mid) {
            $where['mid'] = $mid;
            $table = 'message';
            $update['from_deleted'] = 1;
        }


        if ($switch == 'user_delete') {
            $rid = input('rid');
            $where['rid'] = $rid;
            $table = 'mesreceiver';
            $update['is_deleted'] = 0;

        }

        $result = Db::name($table)->where($where)->update($update);

        if ($result) {
            self::AjaxReturn($result, '删除成功', 1);
        } else {
            self::AjaxReturnError('删除失败',0);

        }
    }
}
