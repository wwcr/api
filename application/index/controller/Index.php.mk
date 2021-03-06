<?php
namespace app\index\controller;

use app\index\module\article;
use app\index\module\order;
use app\index\module\pay;
use app\index\module\user;
use app\index\module\city;
use think\Db;

class Index extends Action
{
    protected $city;

    public function _initialize()
    {
        $this->city = new city();
    }
    public function index()
    {
        return view('./index/index.html');
    }
    public function admin(){
        return 'admin';
    }
    public function test(){
       echo 11113123;
    }
    //首页文章
    public function indexArticle(){
        $limit = input('limit',6);
        $list = article::start()->get(['art_index'=>1],$limit);
        self::AjaxReturn($list);
    }
    //新增车辆看护服务
    public function carnurse(){
        if(self::$repost){
            $insert['card_brand'] = input('card_brand');
            $insert['card_model'] = input('card_model');
            $insert['card_number'] = input('card_number');
            $insert['card_color'] = input('card_color');
            $insert['car_hash'] = md5(input('card_number'));
            if(strlen($insert['card_number']) < 7){
                self::AjaxReturn('车牌号有误,请检查','',0);
            }
            $insert['card_addtime'] = getStrtime();
            $insert['card_uid'] = 1;
            Db::startTrans();//启动事务
            $res = Db::name('nurse')->insertGetId($insert);
            if($res){
                $orderModel = new order();
                $order = $orderModel->add($this->uid,580,1);
                if($order){
                    $update = Db::name('nurse')
                        ->where(['nurse_id'=>$res])
                        ->update(['card_order'=>$order]);
                    if($update){
                        Db::commit();
                        self::AjaxReturn('提交成功','',$order);
                    }else{
                        Db::rollback();
                        self::AjaxReturn('提交失败','',-1);
                    }
                }else{
                    Db::rollback();
                    self::AjaxReturn('订单生成失败','',-1);
                }
            }else{
                Db::rollback();
                self::AjaxReturn('提交失败','',0);
            }
        }
    }
    //创建车辆看护订单
    public function payOrder(){
        $order = input('order');
        $payType = input('type'); //支付方式
        $serviceType = input('serviceType');
        $server = input('server','');//用户勾选的服务项目
        $price = input('price');
        if(!$order){
            self::AjaxReturn('订单号有误,支付失败','',0);
        }
        if(!$serviceType){
            self::AjaxReturn('服务类型有误,请重试','',0);
        }
        if(!is_numeric($price)){
            self::AjaxReturn('订单价格有误,请检查','',0);
        }
        //寻车服务
        $ias = Db::name('pay')->where(['pay_order'=>$order])->find();
        if(!empty($ias)){
            if($ias['pay_status'] == 2){
                self::AjaxReturn('此订单您已经支付过了,不能重复支付','',0);
            }else if ($ias['pay_status'] == 3){
                self::AjaxReturn('此订单已结完成,不能重复支付','',0);
            }
        }
        Db::startTrans();//启动事务
        if($serviceType == 2 && $price){
            $orderModel = new order();
            if(intval($price) <= 0){
                self::AjaxReturn('支付失败,订单价格不能少于0元','',0);
            }
            $result = $orderModel->changePrice($order,$price);
            if(!$result) {
                Db::rollback();
                self::AjaxReturn('支付失败,订单价格生成失败','',0);
            }
        }
        if($server){
            //寻车服务
            if($serviceType == 2){
                $updateServer = Db::name('findcard')
                    ->where(['card_order'=>$order])
                    ->update(['card_server'=>$server]);
            }else{
                $updateServer = Db::name('nurse')
                    ->where(['card_order'=>$order])
                    ->update(['card_server'=>$server]);
            }
            if(!$updateServer){
                Db::rollback();
                self::AjaxReturn('服务状态更新失败','',0);
            }
        }
        $pay = new pay();
        $result = $pay->orderComplete(['pay_order'=>$order],$payType,2);
        if($result){
            Db::commit();
            self::AjaxReturn('支付成功');
        }else{
            Db::rollback();
            self::AjaxReturn('支付失败','',0);
        }
    }
    //新增找车服务
    public function findcar(){
        if(self::$repost){
            $insert['card_brand'] = input('card_brand');
            $insert['card_model'] = input('card_model');
            $insert['card_number'] = input('card_number');
            $insert['card_color'] = input('card_color');
            $insert['card_contract'] = input('card_contract');
            $insert['card_city'] = input('card_city');
            $insert['card_addtime'] = getStrtime();
            $insert['card_uid'] = $this->uid;
            $insert['car_hash'] = md5(input('card_number'));
            $insert['card_img1'] = input('card_img1');//绿本抵押图片
            $insert['card_img2'] = input('card_img2');//抵押借款合同
            $insert['car_status'] = 1;
           Db::startTrans();//启动事务
            $res = Db::name('findcard')->insertGetId($insert);
            if($res) {
                $order = new order();
                $order = $order->add(2,500,1);
                if($order) {
                    $update = Db::name('findcard')
                        ->where(['find_id'=>$res])
                        ->update(['card_order'=>$order]);
                    if($update){
                        Db::commit();
                        self::AjaxReturn('提交成功',$res,$order);
                    }else{
                        Db::rollback();
                        self::AjaxReturn('订单更新失败','',0);
                    }
                }else{
                    Db::rollback();
                    self::AjaxReturn('订单生成失败','',-1);
                }
            }else{
                Db::rollback();
                self::AjaxReturn('提交失败','',0);
            }
        }
    }
    //注册新用户
    public function Register(){
        $nickname = input('user_username');
        $password = input('user_password');
        $mobile = input('user_mobile');
        $qcode = input('qcode');
        if(session('qccode') != $qcode){
            //self::AjaxReturn('您输入的短信验证码不正确','',0);
        }
        if(strlen($nickname) <=0 || mb_strlen($nickname) >=10){
            self::AjaxReturn('昵称不能为空或者必须小于10个汉字','',0);
        }
        $rr = user::start()->add($mobile,$nickname,$this->getpwd($password));
        if(is_numeric($rr)) {
            $info = user::start()->get($rr);
            session('userinfo',$info);
            self::AjaxReturn('注册成功',$info);
        } else{
            self::AjaxReturn('注册失败',$rr,0);
        }
    }
    //用户登陆
    public function login(){
        $nickname = input('username');
        $password = input('password');
        $info = Db::name('user')->where(['user_mobile'=>$nickname])->find();
        if(!empty($info)){
            if($info['user_password'] == md5(sha1($password))){
                session('userinfo',$info);
                self::AjaxReturn('登陆成功',$info);
            }else{
                self::AjaxReturn('用户名或者密码错误','',0);
            }
        }else{
            self::AjaxReturn('用户不存在','',0);
        }
    }
    //忘记密码
    public function forget(){
        $qcode = input('qcode');
        $mobile = input('user_mobile');
        $pwd = input('user_password');
        if($qcode != session('qcode')){
            self::AjaxReturn('验证码有误','',0);
        }
        $info = Db::name('user')->where(['user_mobile'=>$mobile])->find();
        if(empty($info)){
            self::AjaxReturn('用户不存在','',0);
        }else{
            $pwd = md5(sha1($pwd));
            if($info['user_password'] == $pwd){
                self::AjaxReturn('您输入的密码和之前的一致,修改失败','',0);
            }
            $rr = Db::name('user')
                ->where(['user_id'=>$info['user_id']])
                ->update(['user_password'=>$pwd]);
            if($rr){
                self::AjaxReturn('密码修改成功,请使用新密码登陆');
            }else{
                self::AjaxReturn('密码修改失败','',0);
            }
        }
    }
    //身份选择
    public function Metype(){
        $id = input('id');
        $re = user::start()->Metype($id,$this->uid);
        self::AjaxReturn('','',$re);
    }
    //提交认证
    public function Authentication(){
        $is = Db::name('terprise')->where(['enuser_uid'=>$this->uid])->field('en_id')->find();
        $type = input('type');
        if(!$type) {
            self::AjaxReturn('出现错误,请重试','',0);
        }
        if(!empty($is)) {
            self::AjaxReturn('系统检测到您已经认证过了,不能重复认证','',0);
        }
        //企业用户
        Db::startTrans();//启动事务
        if($type == 'enterprise'){
            $insert = array(
                'en_name'=>input('en_name'),
                'en_regmoney'=>input('en_regmoney'),
                'en_person_name'=>input('en_person_name'),
                'en_person_idcard'=>input('en_person_idcard'),
                'en_contact'=>input('en_contact'),
                'enuser_name'=>input('enuser_name'),
                'enuser_idcard'=>input('enuser_idcard'),
                'enuser_contact'=>input('enuser_contact'),
                'enuser_city_id'=>input('enuser_city_id'),
                'enuser_address'=>input('enuser_address'),
                'city_id'=>input('city_id'),
                'legal_idcard_up'=>input('legal_idcard_up'),
                'legal_idcard_down'=>input('legal_idcard_down'),
                'duty_idcard_up'=>input('duty_idcard_up'),
                'duty_idcard_down'=>input('duty_idcard_down'),
                'license_card'=>input('license_card'),
                'enuser_uid'=>$this->uid
            );
            $re = Db::name('terprise')->insert($insert);
            if($re){
                //修改用户认证状态
                user::start()->editAuth($this->uid,2);
                Db::commit();
                self::AjaxReturn('认证成功','',1);
            }else{
                Db::rollback();
                self::AjaxReturn('保存失败','',0);
            }
        }else{
            $insert = array(
                'per_name'=>input('per_name'),
                'per_iphone'=>input('per_iphone'),
                'per_workunit'=>input('per_workunit'),
                'per_worker'=>input('per_worker'),
                'per_address'=>input('per_address'),
                'city_id'=>input('city_id'),
                'per_region'=>input('per_region'),
                'per_idcard'=>input('per_idcard'),
                'per_idcard_up'=>input('per_idcard_up'),
                'per_idcard_down'=>input('per_idcard_down'),
                'personal_with_card'=>input('personal_with_card'),
                'per_uid'=>$this->uid
            );
            $re = Db::name('personal')->insert($insert);
            if($re){
                //修改用户认证状态
                $rz = user::start()->editAuth($this->uid,1);
                if($rz){
                    Db::commit();
                    self::AjaxReturn('认证成功','',1);
                }else{
                    Db::rollback();
                    self::AjaxReturn('认证失败','',0);
                }
            }else{
                self::AjaxReturn('保存失败','',0);
            }
        }
    }
    //文章先关
    public function article(){
        $id = input('id');
        $switch = input('swt');
        if($switch == 'content'){
            $list = Db::name('article')->where(['article_id'=>$id])
                ->join('acate','np_acate.cate_id=np_article.art_cate')
                ->find();
            self::AjaxReturn($list);
        }
    }
    //服务相关
    public function server(){
        $type = input('type',1);//1看护,2寻车
        $status = input('status');
        $sw = input('sw');
        $limit = input('limit',6);
        if($sw == 'content'){
            $list = order::start()->getlist([
                'order_type'=>$type,
                'pay_status'=>$status,
                'order_uid'=>$this->uid
            ],$limit,$type);
            self::AjaxReturn($list);
        }elseif ($sw == 'list'){
            $id = input('id');
            $sid = input('sid');
            if ($sid == 1) {
                $info = Db::name('nurse')
                    ->where(['nurse_id'=>$sid])
                    ->field('card_number,card_addtime')
                    ->order('card_addtime DESC')
                    ->find();
            }else{
                $info = Db::name('findcard')
                    ->where(['find_id'=>$sid])
                    ->field('card_number,card_addtime')
                    ->order('card_addtime DESC')
                    ->find();
            }
            self::AjaxReturn($info);
        }elseif ($sw == 'get'){
            $id = input('type');
            $list = Db::name('service')->where(['product_id'=>$id])->select();
            self::AjaxReturn($list);
        }
    }
    //用户相关
    public function user(){
        $uid = input('uid');
        $sw = input('sw');
        if($sw == 'info') {
            $info = Db::name('user')
                ->where(['user_id'=>$uid])
                ->field('user_nickname,user_anthen,user_header,user_mobile')
                ->find();
            $auth = [];
            if($info['user_anthen'] == 2){
                $auth = Db::name('terprise')
                    ->where(['enuser_uid'=>$uid])
                    ->field('en_person_name,en_name')
                    ->find();
            }elseif ($info['user_anthen'] == 1){
                $auth = Db::name('personal')
                    ->where(['per_uid'=>$uid])
                    ->field('per_workunit,per_worker')
                    ->find();
            };
            $info['auth'] = $auth;
            self::AjaxReturn($info);
        }
    }

}
