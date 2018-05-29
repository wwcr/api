<?php
/**
 * Created by PhpStorm.
 * User: iyahe@qq.com (天明)
 * Date: 2017/7/26
 * Time: 9:55
 */
namespace app\index\controller;

use app\index\module\article;
use app\index\module\city;
use app\index\module\login;
use app\index\module\user;
use think\Db;

class Admin extends Action
{
    protected $city;

    public function _initialize()
    {
        $this->city = new city();
    }

    public function index()
    {
        return view('./admin/index.html');
    }

    public function admin()
    {
        return 'admin/admin';
    }

    public function login()
    {
        $user = input('username');
        $pwd = input('password');
        $res = login::start()->Adminlogin($user, $pwd);
        if (is_array($res)) {
            self::AjaxReturn($res, '登陆成功');
        } else {
            self::AjaxReturn($res, '登陆失败,' . $res, 0);
        }
    }

    //新增分类
    public function addcate()
    {
        $name = input('name');
        $sort = input('sort', 0);
        if (!$name) {
            self::AjaxReturn('', '分类名称不能为空', 0);
        } else {
            $rr = Db::name('acate')->insertGetId(array(
                'cate_name' => $name,
                'soft' => $sort,
                'cate_addtime' => time(),
            ));
            self::AjaxReturn('', '新增成功', $rr);
        }
    }

    //查找分类
    public function getcate()
    {
        $list = Db::name('acate')->select();
        foreach ($list as $k => $v) {
            $list[$k]['cate_addtime'] = date('Y-m-d H:i:s', $v['cate_addtime']);
        }
        self::AjaxReturn($list);
    }

    //新增文章
    public function addArticle()
    {
        $title = input('name');
        $desc = input('desc');
        $content = input('content');
        $thumb = input('thumb');
        $cate = input('cate');
        $editID = input('editId');
        $index = input('index');
        if (strlen($title) <= 3) {
            self::AjaxReturn('文章标题太短啦', '', 0);
        }
        if (!$cate) {
            self::AjaxReturn('文章分类不能为空', '', 0);
        }
        if (strlen($content) <= 10) {
            self::AjaxReturn('文章内容太短了', '', 0);
        }
        $rr = article::start()->add($title, $desc, $content, $cate, $thumb, $index, $editID);
        if ($rr) {
            self::AjaxReturn('操作成功');
        } else {
            self::AjaxReturn('操作失败', '', 0);
        }
    }

    //获取文章
    public function getArticle()
    {
        $switch = input('switch');
        if ($switch) {
            if ($switch == 'once') {
                $list = article::start()->once(input('id'));
                self::AjaxReturn($list);
            }
        } else {
            $list = article::start()->get([], 10);
            self::AjaxReturn($list);
        }
    }

    public function deleteArticle()//删除文章
    {
       $id = input('id');
   		$res = Db::name('article')->where('article_id',$id)->delete();
   		echo json_encode($res);
    }
    //用户列表
    public function user()
    {
        $switch = input('switch');
        $list = [];
        $uid = input('uuid');
        $where = [];
        if ($uid > 0) {
            $where = ['user_id' => $uid];
        }
        if ($switch == 'list') {
            $where['role'] = ['neq', 1];
            $list = Db::name('user')
                ->where($where)
                ->join('terprise', 'np_terprise.enuser_uid=np_user.user_id', 'left')
                ->paginate(10);
        } elseif ($switch == 'terprise') {
            $list = Db::name('user')
                ->where($where)
                ->join('terprise', 'np_terprise.enuser_uid=np_user.user_id')
                ->paginate(10);
        } elseif ($switch == 'userAddList') {
            $where['role'] = 1;
            $list = Db::name('user')
                ->where($where)
                ->join('terprise', 'np_terprise.enuser_uid=np_user.user_id', 'left')
                ->paginate(10);
        }
        self::AjaxReturn($list);
    }

    //车辆找车相关接口
    public function findcar()
    {
        $switch = input('switch');
        $list = [];
        if ($switch == 'list') {
            $list = Db::name('findcard')
                ->join('user', 'np_user.user_id=np_findcard.card_uid')
                ->order('find_id DESC')
                ->paginate(10);
        }
        self::AjaxReturn($list);
    }

    //车辆看护表相关接口
    public function guard()
    {
        $switch = input('switch');
        $list = [];
        if ($switch == 'list') {
            $list = Db::name('nurse')
                ->join('user', 'np_user.user_id=np_nurse.card_uid')
                ->order('nurse_id DESC')
                ->paginate(10);
        }
        self::AjaxReturn($list);
    }

    //订单相关接口
    public function order()
    {
        $switch = input('switch');
        $list = [];
        if ($switch == 'list') {
            $order = intval(input('order'));
            $where = [];
            if ($order > 0) {
                $where = ['order_number' => $order];
            }
            $list = Db::name('order')
                ->join('user', 'np_user.user_id=np_order.order_uid')
                ->join('pay', 'np_pay.pay_order=np_order.order_number')
                ->where($where)
                ->order('order_id DESC')
                ->paginate(10);
        }
        self::AjaxReturn($list);
    }

    public function transaction()
    {
        $switch = input('switch');
        $list = [];
        if ($switch == 'list') {
            $list = Db::name('order')
                ->join('user', 'np_user.user_id=np_order.order_uid')
                ->order('order_id DESC')
                ->paginate(10);
        }
        self::AjaxReturn($list);
    }

    //服务相关
    public function server()
    {
        $sw = input('sw');
        if ($sw == 'add') {
            $insert = array(
                'server_title' => input('name'),
                'service_text' => input('content'),
                'service_addtime' => getStrtime(),
                'product_id' => input('cate'),
            );
            $res = Db::name('service')->insertGetId($insert);
            if ($res) {
                self::AjaxReturn('添加成功');
            } else {
                self::AjaxReturn('添加失败', '', 0);
            }
        } elseif ($sw == 'list') {
            $list = Db::name('service')->paginate(10);
            self::AjaxReturn($list);
        }
    }

    public function cardata()
    {
        $switch = input('switch');
        $keyword = input('keyword');

        if ($switch == 'list') {
            $limit = input('limit', 10);
            if (!$keyword) {
                $list = Db::name('cardata')->order('car_id DESC')->paginate($limit);
            } else {
                $where['car_card']  = ['like',"%$keyword%"];
                $list = Db::name('cardata')->where($where)->order('car_id DESC')->paginate($limit);
            }

            self::AjaxReturn($list);
        }
    }

    /**
     *  后台添加区域代理用户
     */
    public function addUser()
    {
        $userData = input();
        $phone = $userData['phone'];
        $password = $userData['password'];
        $nickname = $userData['nickname'];
        $province_id = $userData['province_id'];
        $proxyCity[$province_id] = $userData['city_id'];
        $proxyCity = serialize($proxyCity);
        $role = 1;

        $user = User::add($phone, $nickname, $this->getpwd($password), $proxyCity, $role);

        if ($user > 0) {
            self::AjaxReturn('添加成功');
        } else {
            self::AjaxReturn($user, '', 0);
        }
    }

    /**
     *
     * 获取省级列表
     * @return [json] [about provinces json]
     */
    public function provinceList()
    {
        $switch = input('switch');

        if ($switch == 'province') {
            $provinces = $this->city->selectProvinces();

            if ($provinces) {
                self::AjaxReturn($provinces);
            } else {
                self::AjaxReturn('获取省级列表失败');
            }

        }

    }

    public function cityList()
    {
        $province_id = input('province_id');
        $citys = $this->city->selectCity($province_id);

        if ($citys) {
            self::AjaxReturn($citys, '获取城市列表成功');
        } else {
            self::AjaxReturn($citys, '获取市级列表失败', 0);
        }
    }

    public function getProvinceId()
    {
        $province = input('province');
        $provinceId = $this->city->getIDByProvinceName($province);

        if ($provinceId) {
            self::AjaxReturn($provinceId, '获取省份id成功');
        } else {
            self::AjaxReturn($provinceId, '获取省份id失败', 0);
        }
    }
    public function getRelationUsers()
    {
        $userId = input('user_id');
        $user = new User();
        $proxyUser = $user->where('user_id', $userId)->find()->toArray();
        $perRelationUsers = [];
        $comRelationUsers = [];

        $proxyCity = implode(',', array_values(unserialize($proxyUser['proxy_city']))[0]);
        $map = [];
        $map['role'] = 0;
        $map['user_type'] = ['>', 1];
        $map['user_anthen'] = ['>', 0];
        $map['relation_id'] = null;
        $map['city_id'] = ['in', $proxyCity];
        $perRelationUsers = Db::name('user')
            ->join('personal', 'np_user.user_id=np_personal.per_uid')
            ->order('user_id DESC')
            ->where($map)
            ->field('user_id, user_nickname, user_mobile, user_regtime, per_address, proxy_city, city_id, user_anthen')
            ->select();
        $comRelationUsers = Db::name('user')
            ->join('terprise', 'np_user.user_id=np_terprise.enuser_uid')
            ->order('user_id DESC')
            ->where($map)
            ->field('user_id, user_nickname, user_mobile, user_regtime, enuser_address, proxy_city, city_id, user_anthen')
            ->select();
        $perRelationUsers = $this->formatArrayKeyData($perRelationUsers) ?: [];
        $comRelationUsers = $this->formatArrayKeyData($comRelationUsers) ?: [];
        $relationUsers = array_merge($perRelationUsers, $comRelationUsers);

        if ($relationUsers && count($relationUsers) > 0) {
            self::AjaxReturn($relationUsers, '获取关联用户成功');
        } else {
            self::AjaxReturn($relationUsers, '该区域暂时没有匹配的用户', 0);
        }

    }

    public function addRelationUsers()
    {
        $data = input();
        $userData = $data['selectedUsers'];
        $uid = $data['user_id'];

        if (count($userData) > 0) {
            foreach ($userData as $key => $user) {
                $result = User::where('user_id', $user['user_id'])->update(['relation_id' => $uid]);
            }
            self::AjaxReturn('添加成功');
        } else {
            self::AjaxReturn('暂无数据添加');
        }
    }

    protected function formatArrayKeyData($array)
    {
        if (is_array($array) && count($array) > 0) {
            $formatArray = [];
            foreach ($array as $key => $value) {
                $formatArray[$value['user_id']] = $value;
                if ($value['per_address']) {
                    $formatArray[$value['user_id']]['address'] = $value['per_address'];
                }
                if ($value['enuser_address']) {
                    $formatArray[$value['user_id']]['address'] = $value['enuser_address'];
                }

                switch ($value['user_anthen']) {
                    case 1:
                        $formatArray[$value['user_id']]['anthen_type'] = '个人认证';
                        break;
                    case 2:
                        $formatArray[$value['user_id']]['anthen_type'] = '企业认证';
                        break;
                    default:
                        $formatArray[$value['user_id']]['anthen_type'] = '未认证';
                        break;
                }
            }

            return $formatArray;
        }
    }

    public function setUserSignTime()
    {
        $data = input();
        $user_id = $data['user_id'];
        $signStartTime = $data['sign_start_time'];
        $signEndTime = $data['sign_end_time'];

        $result = User::editSignUser($user_id, $signStartTime, $signEndTime);

        if ($result) {
            self::AjaxReturn($result, '签约成功', 1);
        } else {
            self::AjaxReturn($result, '签约失败', 0);
        }
    }

}
