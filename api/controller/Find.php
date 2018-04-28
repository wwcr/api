<?php
/**
 * Created by PhpStorm.
 * User: iyahe@qq.com (天明)
 * Date: 2017/10/25
 * Time: 14:16
 */
namespace app\api\controller;
use app\index\controller\Action;
use app\index\module;
use think\Db;
use app\api\validate;

class Find extends Action
{
    public function index(){}
    //找车服务详情
    public function details(){
        $id = input('id');
        // $id = 7;
        $list = Db::name('exorder')
            ->join('findcard','np_findcard.find_id=np_exorder.ex_fid')
            ->where(['ex_oid'=>$id])
            ->find();
        self::AjaxReturn($list);
        // var_dump($list);
    }
    //找车列表
    public function listd_page(){
        $limit = input('limit');
        $page = input('page');
        $status = input('status');
        $order_by = input('post.order_by');
        // echo $order_by;
        // $where = ['car_status'=>$status,'card_uid'=>$this->uid, 'recycle'=>1];
        $where = ['car_status'=>$status,'card_uid'=>$this->uid];
        if($status ==3){
            $where = 'car_status >= 2 and card_uid='.$this->uid;
        }
        if($order_by == 1){
            $order = 'card_addtime DESC';
        }else{
            $order = 'card_addtime ASC';
        }
        $allcount = Db::name('findcard')//总条数
            ->join('np_pay','np_pay.pay_order=np_findcard.card_order','left')
            ->where($where)
            ->order($order)
            ->select();
        $count = count($allcount);
        $list = Db::name('findcard')
            ->join('np_pay','np_pay.pay_order=np_findcard.card_order','left')
            ->where($where)
            ->order($order)
            // ->fetchSql()
            // ->paginate($limit);
            ->limit($limit)
            // ->fetchSql()
            ->select();

        self::AjaxReturn($list,$count);
    }

    //取消寻车订单
    public function cancleFindCard()
    {
        $findId = input('find_id');
        $uid = input('uid');

        $data = Db::name('findcard')->where('find_id', $findId)->find();

        if ($data['car_status'] >=2) {
            self::AjaxReturnError('该车辆已经找到，不能取消委单');
        }

        if ($data['card_uid'] != $uid) {
            self::AjaxReturnError('权限不足，无法取消');
        }

        $result = Db::name('findcard')->where('find_id', $findId)->delete();

        if ($result) {
            self::AjaxReturn('','取消成功', 1);
        } else {
            self::AjaxReturnError('操作失败，请重试');
        }
    }

    public function listd(){
        $limit = input('limit',10);
        $status = input('status');
        $where = ['car_status'=>$status,'card_uid'=>$this->uid,'pay_status'=>2];
        if($status ==3){
            $where = 'car_status >= 2 and card_uid='.$this->uid;
        }
        $list = Db::name('findcard')
            //->join('exorder','np_exorder.ex_fid=np_findcard.find_id','left')
            ->join('np_pay','np_pay.pay_order=np_findcard.card_order','left')
            ->where($where)
            ->order('card_addtime DESC')
            ->paginate($limit);
        self::AjaxReturn($list);
    }
    // 删除过期未付款的订单
    public function listd_delete(){
        $order = input('order_no');//时间过60分钟
        $info = Db::name('pay')->where('pay_order',$order)->find();
        if($info['pay_status'] == -1 || $info['pay_status'] == 1){
        $res = Db::name('findcard')->where('card_order',$order)->delete();
        $res1 = Db::name('pay')->where('pay_order',$order)->delete();
        if($res && $res1){
            self::AjaxReturn('订单已删除');
        }else{
            self::AjaxReturn('系统繁忙');
        }
        }else{
            self::AjaxReturn('订单已支付');
        }
    }
    public function findOrderlist_page(){
        $currentID = input('currentID');
         $order_by = input('post.order_by');
         $limit = input('limit');
        //2执行方,1委托方
        if($currentID == 2){
            $this->findOrderOwn_page($order_by);
        }else{
            $status = input('status');
            $vip = input('vip');
            $where= ['ex_status'=>$status,'card_uid'=>$this->uid];
            if($status == 1){
                //0 标识查找所有执行中的数据
                if($vip == 1){
                    $where= '(ex_status=2 or ex_status = 1) and ex_uid ='.$this->uid;
                }else{
                    $where= '(ex_status=2 or ex_status = 1) and card_uid ='.$this->uid;
                }



            }
            if($status == 2){
                //1 查找未接单的
                if($vip == 1){
                    $where= ['ex_status'=>0,'ex_uid'=>$this->uid];
                }else{
                    $where= ['ex_status'=>0,'card_uid'=>$this->uid];
                }
            }
            // if($status == 0){
            //     //0 标识查找所有执行中的数据
            //     $where= ['ex_status'=>0,'card_uid'=>$this->uid];
            // }
            // if($status == 1){
            //     //1 查找未接单的
            //     $where= ['ex_status'=>1,'card_uid'=>$this->uid];
            // }
            if($status ==3){
                //查找已经
                if($vip == 1){
                    $where= '(ex_status = 4 or ex_status = 3) and ex_uid='.$this->uid;
                }else{
                $where= '(ex_status = 4 or ex_status = 3) and card_uid='.$this->uid;
            }
            }
            if($order_by == 1){
                 $order = 'ex_addtime';
                 $order_one = 'desc';
            }else{
                $order = 'ex_addtime';
                $order_one = 'asc';
            }
            $types = 'left';
        $allcount = Db::name('single')
            ->join('np_exorder','np_exorder.ex_oid=np_single.single_order','right')
            ->join('np_findcard','np_findcard.find_id=np_exorder.ex_fid','right')
            ->where($where)
            ->order($order,$order_one)
            // ->fetchSql()
            // ->limit($limit)
            ->select();
        $count = count($allcount);//总条数
            // $list = Db::name('findcard')
            //     ->join('np_exorder','np_exorder.ex_fid=np_findcard.find_id',$types)
            //     ->join('np_single','np_single.single_order = np_exorder.ex_order',$types)
            //     ->where($where)
            //     ->order($order)
            //     ->fetchSql()
            //     ->paginate($this->limit);
            $list = Db::name('single')
            ->join('np_exorder','np_exorder.ex_oid=np_single.single_order','right')
            ->join('np_findcard','np_findcard.find_id=np_exorder.ex_fid','right')
            ->where($where)
            ->order($order,$order_one)
            // ->fetchSql()
            ->limit($limit)
            ->select();
            self::AjaxReturn($list,$count);
        }
    }
    //我发布的正在寻找的
    public function findOrderlist(){
        $currentID = input('currentID');
         $order_by = input('post.order_by');
        //2执行方,1委托方
        if($currentID == 2){
            $this->findOrderOwn($order_by);
        }else{
            $status = input('status');
            $vip = input('vip');
            $where= ['ex_status'=>$status,'card_uid'=>$this->uid];
            if($status == 1){
                //0 标识查找所有执行中的数据
                if($vip == 1){
                    $where= '(ex_status=2 or ex_status = 1) and ex_uid ='.$this->uid;
                }else{
                    $where= '(ex_status=2 or ex_status = 1) and card_uid ='.$this->uid;
                }



            }
            if($status == 2){
                //1 查找未接单的
                if($vip == 1){
                    $where= ['ex_status'=>0,'ex_uid'=>$this->uid];
                }else{
                    $where= ['ex_status'=>0,'card_uid'=>$this->uid];
                }
            }
            // if($status == 0){
            //     //0 标识查找所有执行中的数据
            //     $where= ['ex_status'=>0,'card_uid'=>$this->uid];
            // }
            // if($status == 1){
            //     //1 查找未接单的
            //     $where= ['ex_status'=>1,'card_uid'=>$this->uid];
            // }
            if($status ==3){
                //查找已经
                if($vip == 1){
                    $where= '(ex_status = 4 or ex_status = 3) and ex_uid='.$this->uid;
                }else{
                $where= '(ex_status = 4 or ex_status = 3) and card_uid='.$this->uid;
            }
            }
            if($order_by == 1){
                 $order = 'ex_addtime';
                 $order_one = 'desc';
            }else{
                $order = 'ex_addtime';
                $order_one = 'asc';
            }
            $types = 'left';
            // $list = Db::name('findcard')
            //     ->join('np_exorder','np_exorder.ex_fid=np_findcard.find_id',$types)
            //     ->join('np_single','np_single.single_order = np_exorder.ex_order',$types)
            //     ->where($where)
            //     ->order($order)
            //     ->fetchSql()
            //     ->paginate($this->limit);
            $list = Db::name('single')
            ->join('np_exorder','np_exorder.ex_oid=np_single.single_order','right')
            ->join('np_findcard','np_findcard.find_id=np_exorder.ex_fid','right')
            ->where($where)
            ->order($order,$order_one)
            // ->fetchSql()
            ->paginate($this->limit);
            self::AjaxReturn($list);
        }
    }
    public function findOrderOwn_page($order_by = 1){//重写分页
        $status = input('status');
        // echo json_encode($status);
        $vip = input('vip');//判断该用户是否为大区经理
        // $status = intval($status)+1;
        // $where['single_uid'] = $this->uid;
        $limit = input('limit');
        if($vip == 1){
            $where['single_uid'] = $this->uid;
            $where['ex_status'] = $status;
            // $where= 'ex_status='.$status.'and single_uid ='.$this->uid;
            if($status == 3){
                $where= 'ex_status = 4 and single_uid ='.$this->uid;
            }
            // $where['assign_id'] = null;
        }else{
            $where['assign_id'] = $this->uid;
            $where['ex_status'] = $status+1;
        }
         if($order_by == 1){
                 $order = 'ex_addtime';
                 $order_one = 'desc';
            }else{
                $order = 'ex_addtime';
                $order_one = 'asc';
            }
        // if ($status == 1) {
        //     $where['assign_id'] = null;
        // } else {
        //     $where['assign_id'] = ['>',0];
        // }
        $allcount = $list = Db::name('single')
            ->join('np_exorder','np_exorder.ex_oid=np_single.single_order','left')
            ->join('np_findcard','np_findcard.find_id=np_exorder.ex_fid','left')
            ->where($where)
            // ->fetchSql()
            ->order($order,$order_one)
            ->limit($limit)
            ->select();
        $count = count($allcount);
        $list = Db::name('single')
            ->join('np_exorder','np_exorder.ex_oid=np_single.single_order','left')
            ->join('np_findcard','np_findcard.find_id=np_exorder.ex_fid','left')
            ->where($where)
            // ->fetchSql()
            ->order($order,$order_one)
            ->limit($limit)
            ->select();
        self::AjaxReturn($list,$count);
    }
    //获取我抢到的单子中正在执行的
    public function findOrderOwn($order_by = 1){
        $status = input('status');
        // echo json_encode($status);
        $vip = input('vip');//判断该用户是否为大区经理
        // $status = intval($status)+1;
        // $where['single_uid'] = $this->uid;

        if($vip == 1){
            $where['single_uid'] = $this->uid;
            $where['ex_status'] = $status;
            // $where= 'ex_status='.$status.'and single_uid ='.$this->uid;
            if($status == 3){
                $where= 'ex_status = 4 and single_uid ='.$this->uid;
            }
            // $where['assign_id'] = null;
        }else{
            $where['assign_id'] = $this->uid;
            $where['ex_status'] = $status+1;
        }
         if($order_by == 1){
                 $order = 'ex_addtime';
                 $order_one = 'desc';
            }else{
                $order = 'ex_addtime';
                $order_one = 'asc';
            }
        // if ($status == 1) {
        //     $where['assign_id'] = null;
        // } else {
        //     $where['assign_id'] = ['>',0];
        // }

        $list = Db::name('single')
            ->join('np_exorder','np_exorder.ex_oid=np_single.single_order','left')
            ->join('np_findcard','np_findcard.find_id=np_exorder.ex_fid','left')
            ->where($where)
            // ->fetchSql()
            ->order($order,$order_one)
            ->paginate($this->limit);
        self::AjaxReturn($list);
    }
    //执行方,确认订单();
    public function okay(){
        $orderID = input('orderID','');
        $type = input('type');
        $card_order = input('card_order');
        if($orderID){
            Db::startTrans();//启动事务
            //1表示委托方,2表示执行方
            if(input('currentID') == 1){
                $type = 4; //确认完成
                $content = '订单已经完成，请去回收管理中查看订单';
                $orderData = Db::name('single')->where('single_order', $orderID)->find();
                $uid = $orderData['assign_id'];
            }else{
                $type = 3; //车辆回库
                $content = '您的车辆已回库，请联系客服010-84685465办理交接手续。';
                $orderData = Db::name('exorder')->where('ex_oid', $orderID)->find();
                $uid = $orderData['ex_uid'];
            }
            $rr = Db::name('exorder')
                ->where(['ex_oid'=>$orderID])
                ->update(['ex_status'=>$type]);

            if($rr){
                Db::commit();

                $data = array(
                    'from_uid' => 0,//订单完成系统消息
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
                self::AjaxReturnSuccess('已确认');


            }else{
                Db::rollback();
                self::AjaxReturnError('订单有误,确认失败');
            }
        }
    }
    //委托方确认订单
    public function okayer(){
        $orderID = input('orderID','');

        if($orderID){
            Db::startTrans();//启动事务
            $ff = Db::name('findcard')->where(['card_order'=>$orderID,'card_uid'=>$this->uid])
                ->update(['car_status'=>2]); //已找到
            if($ff){
                Db::commit();
                self::AjaxReturnSuccess('已确认');
            }else{
                Db::rollback();
                self::AjaxReturnError('确认失败');
            }
        }
    }
    public function search(){
        $limit = input('limit',10);
        $status = input('status',1);
        $keyword = input('keyword','');
        $where = [];
        $where['card_number'] = array('like','%'.$keyword.'%');
        $where['car_status'] = $status;
        $list = Db::name('findcard')
            ->where($where)
            ->order('card_addtime DESC')
            ->paginate($limit);
        self::AjaxReturn($list);
    }
    //确认找车订单
    public function okayFind(){
        $id = input('id');
        $status = input('status',1);
        // 改状态为3,表示已经确认
        $rr = Db::name('findcard')->where(['find_id'=>$id])->update(['car_status'=>$status]);
        if($rr){
            self::AjaxReturnSuccess('已确认');
        }
        self::AjaxReturnError('确认失败');
    }
    public function serverDetails(){
        $id = input('id');
        $list = Db::name('findcard')->where(['find_id'=>$id])->find();
        self::AjaxReturnSuccess('ok',$list);
    }
}