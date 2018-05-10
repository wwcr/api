<?php
/**
 * Created by PhpStorm.
 * User: iyahe@qq.com (天明)
 * Date: 2017/10/24/024
 * Time: 21:36
 */
namespace app\api\controller;
use app\index\controller\Action;
use app\index\module;
use think\Db;
use app\api\validate;

/** 订单控制器
 * Class Order
 * @package app\api\controller
 */
class Order extends Action
{
    public $leave;
    public function __construct()
    {
        parent::__construct();
    }

    public function index(){}
    public function test111($id,$mk){
        echo $id;
        echo $mk;
        echo 'order';
    }
    /** 获取订单等级
     * @param $money 金额
     * @return int 返回等级
     */
    public function makeleavel($money){
        if(!is_numeric($money)) return false;
        $leavel = config('leavel');
        for ($i = count($leavel)-1; $i >= 0; $i--) {
            if($money >= $leavel[$i][0] && $money <= $leavel[$i][1]){
                return $i;
                break;
            }
        }
    }
    public function query_find(){
        $id = input('post.id');
        $order = Db::name('findcard')
                   ->where('find_id',$id)
                   ->find();
        self::AjaxReturn($order);
    }
    //创建可执行订单
    public function terrace(){
        $data = input();
        // $type = input('post.type');
        $type = input('type');//是否是签约用户
        if(!$data['ex_fid']){
            self::ajaxReturnError('寻车服务ID获取失败,请返回');
        }
        if(self::$repost){
            $find_id = Db::name('findcard')->where('find_id',$data['ex_fid'])->field('find_id')->find();
            if(empty($find_id)){
                self::ajaxReturnError('抱歉,当前Id对应的找车服务未找到,请返回上级页面重试');
            }
            $isExt = Db::name('exorder')->where('ex_fid',$data['ex_fid'])->field('ex_oid')->find();
            if(!empty($isExt)){
                self::ajaxReturnError('当前车辆已经发布过了,不能重复发送',$isExt);
            }
            $msg = validate\Order::terrace($data);
           if($msg === true){
               unset($data['uid']);
               unset($data['token']);
               unset($data['type']);
               $data['ex_addtime'] = getStrtime();
               $data['ex_uid'] = $this->uid;
               $data['ex_update'] = getStrtime();
               $data['ex_money'] = intval($data['ex_money']);
               $data['ex_term_day'] = intval($data['ex_term']);
               $data['ex_term'] = time() + (60*60*24* intval($data['ex_term']));//截止时间戳
               $data['ex_leavel'] = $this->makeleavel($data['ex_money']);
               $data['ex_status'] = 0; //初始化未执行
               Db::startTrans();//启动事务
               //创建订单
               $order = module\order::start()->add(3,$data['ex_money'],$this->uid,$type);
               if($order){
                   $data['ex_order'] = $order;
                   $reult = Db::name('exorder')->insertGetId($data);
                   if($reult){
                       Db::commit();
                       // return $order;
                       self::ajaxReturnSuccess('提交成功',$order);
                   }else{
                       Db::rollback();
                       self::ajaxReturnError('提交失败');
                   }
               }else{
                   Db::rollback();
                   self::ajaxReturnError('订单创建失败');
               }
           }else{
               self::ajaxReturnError($msg);
           }
        }
    }
    public function listd_page(){//执行大厅分页重写
        $limit = input('limit');
        $leavel = input('leavel',0);
        $debug = input('debug');
        $order_by = 2;
        //$where = ['ex_leavel'=>$leavel,'pay_status'=>2];
        $where = ['pay_status'=>2,'ex_status'=>0];
        if($debug){
            //$where = ['pay_status'=>2];
        }
        if($order_by == 2 && $leavel == 0){//金额倒叙
            $order = 'ex_money DESC';
        }else if($order_by == 1 && $leavel == 0){
            $order = 'ex_money ASC';
        }else if($order_by == 2 && $leavel == 1){
            $order = 'ex_addtime DESC';
        }else if($order_by == 1 && $leavel == 1){
            $order = 'ex_addtime ASC';
        }else if($order_by == 2 && $leavel == 2){
           $order = 'ex_gps DESC';
        }else{
           $order = 'ex_gps ASC';
        }
        $allcount = Db::name('exorder')
            ->join('single','np_single.single_id=np_exorder.ex_oid','left')
            ->join('np_pay','np_pay.pay_order=np_exorder.ex_order','left')
            ->where($where)
            // ->field('ex_order,ex_uid,ex_update',true)
            ->order($order)
            ->select();
        $count = count($allcount);//总条数
        $list =  Db::name('exorder')
            ->join('single','np_single.single_id=np_exorder.ex_oid','left')
            ->join('np_pay','np_pay.pay_order=np_exorder.ex_order','left')
            ->where($where)
            // ->field('ex_order,ex_uid,ex_update',true)
            ->order($order)
            ->limit($limit)
            ->select();
        self::AjaxReturn($list,$count);
    }
    //执行订单列表
    public function listd(){
        $limit = input('limit',$this->limit);
        $leavel = input('leavel',0);
        $debug = input('debug');
        $order_by = input('order_by');
        //$where = ['ex_leavel'=>$leavel,'pay_status'=>2];
        $where = ['pay_status'=>2,'ex_status'=>0];
        if($debug){
            //$where = ['pay_status'=>2];
        }
        if($order_by == 2 && $leavel == 0){//金额倒叙
            $order = 'ex_money DESC';
        }else if($order_by == 1 && $leavel == 0){
            $order = 'ex_money ASC';
        }else if($order_by == 2 && $leavel == 1){
            $order = 'ex_addtime DESC';
        }else if($order_by == 1 && $leavel == 1){
            $order = 'ex_addtime ASC';
        }else if($order_by == 2 && $leavel == 2){
           $order = 'ex_gps DESC';
        }else{
           $order = 'ex_gps ASC';
        }
        $list =  Db::name('exorder')
            ->join('single','np_single.single_id=np_exorder.ex_oid','left')
            ->join('np_pay','np_pay.pay_order=np_exorder.ex_order','left')
            ->where($where)
            // ->field('ex_order,ex_uid,ex_update',true)
            ->order($order)
            ->paginate($limit);
        self::AjaxReturn($list);
    }
    //订单详情
    public function orderDetails(){
        $id = input('id');
        $info = Db::name('exorder')
            ->join('np_single','np_single.single_order=np_exorder.ex_oid','left')
            ->join('np_findcard','np_findcard.find_id=np_exorder.ex_fid','left')
            ->where('ex_oid',$id)->find();
        self::AjaxReturn($info);
    }
    //抢单
    public function single(){
        if(self::$repost){
            $oid = input('oid');
            if(!$oid) return self::AjaxReturnError('抢单失败...,订单获取失败');
            Db::startTrans();//启动事务
            $exorder = Db::name('exorder')->where(['ex_oid'=>$oid])->field('ex_uid')->find();
            if($exorder['ex_uid'] == $this->uid)
            {
                self::AjaxReturnError('抱歉,自己不能抢自己的单子...');
            }
            $order = Db::name('single')->where('single_order',$oid)->find();
            if(!empty($order)){
                self::AjaxReturnError('抱歉你来晚了,订单已经被抢走了...');
            }
            $insert = [
                'single_order'=>$oid,
                'single_addtime'=>getStrtime(),
                'single_uid'=>$this->uid
            ];
            $rrr =  Db::name('single')->insertGetId($insert);
            if($rrr){
                //当前订单修改为执行中
                $uop = Db::name('exorder')->where(['ex_oid'=>$oid])->update(['ex_status'=>1]);
                if($uop){
                    Db::commit();
                    self::AjaxReturnSuccess('抢单成功');
                }
            }
            Db::rollback();
            self::AjaxReturnError('抢单失败');
        }
    }
    //付款中的订单
        public function status_page(){
        $page = input('page');
        $limit = input('limit');
        $type = input('status',1);
        $order_by = input('order_by');//排序 1正序  2 倒叙
         if($order_by == 1){
            $order = 'card_addtime DESC';
        }else{
            $order = 'card_addtime ASC';
        }
        $allcount = Db::name('findcard')//获取总条数
            ->join('np_pay','np_pay.pay_order=np_findcard.card_order')
            ->where(['pay_status'=>$type,'card_uid'=>$this->uid])
            ->order($order)
            ->select();
        $count = count($allcount);
        $list = Db::name('findcard')
            ->join('np_pay','np_pay.pay_order=np_findcard.card_order')
            ->where(['pay_status'=>$type,'card_uid'=>$this->uid])
            ->order($order)
            // ->order('card_addtime DESC')
            // ->paginate($limit);
            ->limit($limit)
            ->select();
        self::AjaxReturn($list,$count);
    }
     public function status(){
        $limit = input('limit');
        $type = input('status',1);
        $list = Db::name('findcard')
            ->join('np_pay','np_pay.pay_order=np_findcard.card_order')
            ->where(['pay_status'=>$type,'card_uid'=>$this->uid])
            ->order('card_addtime DESC')
            ->paginate($limit);
        self::AjaxReturn($list);
    }
    //判断当前找车服务是否已经发布寻车订单
    public function Isterrace(){
        // $id = 232;
        $id = input('id');
        $list = Db::name('exorder')->where(['ex_fid'=>$id])->field('ex_addtime,ex_oid')->find();
        $list1 = Db::name('findcard')->where(['find_id'=>$id])->field('car_ass_id')->find();
        $data = [$list,$list1['car_ass_id']];
        self::AjaxReturn($data);
    }
    public function getPayorder(){
        $order = input('order');
        if($order){
            $orderData = Db::name('order')->where(['order_number'=>$order])->find();
            if($orderData){
                self::AjaxReturn($orderData);
            }else{
                self::AjaxReturnError('订单获取失败');
            }
        }
    }
    public function payorder(){
        $order = input('order');
        $price = input('price');
        $payType = input('type'); //支付方式
        if($order && $price && $payType >= 0){
            Db::startTrans();//启动事务
            $ias = Db::name('pay')->where(['pay_order'=>$order])->find();
            if(!empty($ias)){
                if($ias['pay_status'] == 2){
                    self::AjaxReturn('此订单您已经支付过了,不能重复支付','',0);
                }else if ($ias['pay_status'] == 3){
                    self::AjaxReturn('此订单已结完成,不能重复支付','',0);
                }
            }
            $pay = new module\pay();
            $result = $pay->orderComplete(['pay_order'=>$order],$payType,2);
            if($result){
                Db::commit();
                self::AjaxReturn('支付成功');
            }else{
                Db::rollback();
                self::AjaxReturn('支付失败','',0);
            }
        }
    }
    public function order_payinfo(){//查询该订单是否支付完成
    	$order = input('order');
    	 $info = Db::name('pay')->where(['pay_order'=>$order])->find();
    	 if(!empty($info)){
    	 	 if($info['pay_status'] == 2){
                    self::AjaxReturn('支付成功','',2);
                }else if ($info['pay_status'] == 3){
                    self::AjaxReturn('此订单已结完成,不能重复支付','',0);
                }else{
                	self::AjaxReturn('该订单未支付','',-1);
                }
    	 }
    }
}