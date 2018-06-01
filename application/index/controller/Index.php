<?php
namespace app\index\controller;

use app\index\module\article;
use app\index\module\order;
use app\index\module\pay;
use app\index\module\user;
use app\index\module\city;
use app\index\module\Jpush;
use app\api\module\sms;
use think\Cache;
use think\Db;
use think\Request;

class Index extends Action
{
    protected $city;
    protected $redis;
    protected $deposit;
    public function _initialize()
    {
        $this->city = new city();
         $options = [
            // 缓存类型为File
            'type' => 'redis',
            'prefix' => ''
        ];
        $this->redis = Cache::connect($options);//连接redi

        $this->deposit = Db::name('user')->where(['user_id' => $this->uid])->value('deposit');

    }
    public function check_sms($mobile,$ip)//检测请求次数
    {
       $res = Db::name('check')->where('mobile',$mobile)->select();//获取当前手机号所有请求记录
       if(count($res) > 10){
        return 'FAIL';
       }
       $res_ip = Db::name('check')->where('ip',$ip)->select();//获取当前ip所有请求记录
       if(count($res_ip) > 10){
        return 'FAIL';
       }
    }
    public function clear_recode(){//清楚24小时之前的记录
        $time=time()-24*3600;//获取24小时之前的时间戳  如果添加时间《 24小时之前的，此纪录删除，说明已过24H
        Db::name('check')->where('get_time','<',$time)->delete();
    }
    public function admin(){
        return 'admin';
    }
    public function test(){
        $this->redis->set('3123', 113,5);
    }
    public function test1111(){
          echo '我是猪';
    }
    public function get_banner(){
       $list = Db::name('banner')->select();
        self::AjaxReturn($list);
    }
    public function get_banner_new(){
       $list = Db::name('banner_list')->where('isdelete','neq',1)->select();
        self::AjaxReturn($list);
    }
    public function get_banner_content(){
        $id = input('post.id');
       $list = Db::name('banner_list')->where('id',$id)->select();
       // $list['content'] = htmlspecialchars($list['content']);
       echo json_encode($list);
        // self::AjaxReturn($list);
    }
    public function qcode_4DZpLslB(){//加盟商验证码
        $mobile = input('mobile');
        $this->clear_recode();//删除24小时之前的记录
        $request = Request::instance();//实例化
        $ip = $request->ip();
        $get_num = $this->check_sms($mobile,$ip);//添加请求记录
        if($get_num == 'FAIL'){
            self::AjaxReturn('验证码发送失败',0,0);
        }
        $rands = rand(1000,9999);
        $sms = new sms();
        $data = [
            'template_code' => 'SMS_127153066',
            'json_string_param' => ["code" =>$rands],
            'phone' =>$mobile,
            'sign'=>'无维科技'
        ];
        if($sms->send($data)){
                $data=[
                    'ip' => $ip,
                    'mobile' => $mobile,
                    'get_time' => time(),
                ];
                Db::name('check')->insert($data);
                $this->redis->set($mobile, $rands,300);
                self::AjaxReturn('验证码发送成功');
            }else{
                self::AjaxReturn('验证码发送失败','0');
            }
    }
    public function join(){//加盟
        $data['mobile'] = input('tel');
        $data['name'] = input('name');
        $code = input('code');//短信验证码
        if($this->redis->get($data['mobile']) != $code){
            self::AjaxReturn('验证码错误',session('qcode'));
            return;
        }
        $mo = Db::name('join')->where(['mobile'=>$data['mobile']])->find();//查询有没有重复提交
        if(empty($mo)){
            $res = Db::name('join')->insert($data);
            if($res){
                self::AjaxReturn('提交成功',1);
            }
        }else{
            self::AjaxReturn('重复提交',0);
        }

    }
    //首页文章
    public function indexArticle(){
        $limit = input('limit');
        $page = input('page');
        $allount = Db::name('article')->select();
        $count = count($allount);
        $list = Db::name('article')
            ->where('art_index=1')
            ->limit($page,$limit)
            ->select();
        self::AjaxReturn($list,$count);
    }
     public function indexArticle_page(){//重写文章分页
        $limit = input('limit');
        $page = input('page');
        $allount = Db::name('article')->select();
        $count = count($allount);
        $list = Db::name('article')
            ->where('art_index=1')
            ->limit($limit)
            ->order('art_time DESC')
            ->select();
        self::AjaxReturn($list,$count);
    }
    public function indexArticle_page_new(){//重写文章分页
        $limit = input('limit');
        $page = input('page');
        $allount = Db::name('article_list')->where('isdelete','neq',1)->select();
        $count = count($allount);
        $list = Db::name('article_list')
            ->where('isdelete','neq',1)
            ->limit($limit)
            ->order('art_time DESC')
            ->select();
        self::AjaxReturn($list,$count);
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
            $insert['car_ass_id'] = input('car_ass_id');
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
     public function findcarnew($excelData=null){
        if (config('system_up.findcard') == true) {
            self::AjaxReturnError('系统升级中....', 0);
        }

        if ($this->deposit !=2 && $excelData == null && config('depositButton')) {
            self::AjaxReturnError('您还没有交押金，请先交押金。', 0);
        }

        if ($excelData == null) {
            $card_number = input('card_number').input('card_allnumber');
            $isFindCar = Db::name('findcard')->where('card_number', $card_number)->field('find_id')->find();
        }

        if ($isFindCar && !input('find_id')) {
            self::AjaxReturnError('该委单已经存在，不能重复添加，请联系客服', 0);
        }

        if(self::$repost || $excelData != null){
        	$type = input('type');//判断是否签约用户
        	if(!$type){
        		$type = 1;
        	}
            // $check = $this->check_token($this->uid,$this->token);
            // if($check == 'fail'){//token验证失败
            //     self::AjaxReturn('系统繁忙,请稍候再试','',-1);
            // }
            $insert['card_brand'] = input('card_brand');
            $insert['card_model'] = input('card_model');
            $insert['card_number'] = input('card_number').input('card_allnumber');
            $insert['card_color'] = input('card_color');
            $insert['card_contract'] = input('card_contract');
            $insert['card_city'] = input('card_city');
            $insert['card_addtime'] = getStrtime();
            $insert['card_uid'] = $this->uid;
            $insert['car_hash'] = md5(input('card_number'));
            $insert['card_img1'] = input('allcard_img1');//绿本抵押图片
            $insert['card_img2'] = input('allcard_img2');//抵押借款合同
            $insert['car_ass_id'] = input('car_ass_id');

            if (config('findcarAudit')) {
                $insert['car_status'] = -1;
            } else {
                $insert['car_status'] = 1;
            }

            //更新数据
            if (input('find_id')) {
                $res = Db::name('findcard')->where(['find_id'=>input('find_id')])->update($insert);
                if ($res) {
                    self::AjaxReturn($res,'提交成功',1);
                }else {
                    self::AjaxReturn('','提交失败',0);
                }
            }

            if ($excelData != null) {//phpexcel条件过滤
                $insert = $excelData;
            }
            if ($excelData == null) {//phpexcel条件过滤
                Db::startTrans();//启动事务
            }
            $res = Db::name('findcard')->insertGetId($insert);
            if($res) {
                $order = new order();
                $order = $order->add(2,450,1,$type);
                if($order) {
                    $update = Db::name('findcard')
                        ->where(['find_id'=>$res])
                        ->update(['card_order'=>$order]);
                    if ($excelData == null) {//phpexcel条件过滤
                        if($update){
                            Db::commit();
                            self::AjaxReturn($res,'提交成功',$order);
                        }else{
                            Db::rollback();
                            self::AjaxReturn('','订单更新失败',0);
                        }
                    }
                }else{
                    if ($excelData == null) {//phpexcel条件过滤
                        Db::rollback();
                    }
                    self::AjaxReturn('','订单生成失败',-1);
                }
            }else{
                if ($excelData == null) {//phpexcel条件过滤
                    Db::rollback();
                }
                self::AjaxReturn('','提交失败',0);
            }
        }
    }

    public function findcarSave()
    {
        $findId = input('find_id');

        if (!$findId) {
            self::AjaxReturn('','订单错误',0);
        }

        $findData = Db::name('findcard')->where('find_id', $findId)->find();

        if ($findData) {
            self::AjaxReturn($findData,'获取数据成功',1);
        } else {
            self::AjaxReturn('','数据错误',0);
        }

    }

    public function findcarnew_safe(){//验证token
        if(self::$repost){
            $type = input('type');//判断是否签约用户
            if(!$type){
                $type = 1;
            }
            $check = $this->check_token($this->uid,$this->token);
            if($check == 'fail'){//token验证失败
                self::AjaxReturn('系统繁忙,请稍候再试','',-1);
            }
            $insert['card_brand'] = input('card_brand');
            $insert['card_model'] = input('card_model');
            $insert['card_number'] = input('card_number').input('card_allnumber');
            $insert['card_color'] = input('card_color');
            $insert['card_contract'] = input('card_contract');
            $insert['card_city'] = input('card_city');
            $insert['card_addtime'] = getStrtime();
            $insert['card_uid'] = $this->uid;
            $insert['car_hash'] = md5(input('card_number'));
            $insert['card_img1'] = input('allcard_img1');//绿本抵押图片
            $insert['card_img2'] = input('allcard_img2');//抵押借款合同
            $insert['car_status'] = 1;
            $insert['car_ass_id'] = input('car_ass_id');
           Db::startTrans();//启动事务
            $res = Db::name('findcard')->insertGetId($insert);
            if($res) {
                $order = new order();
                $order = $order->add(2,450,1,$type);
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
        // if(session('qccode') != $qcode){
        //     //self::AjaxReturn('您输入的短信验证码不正确','',0);
        // }
        if($this->redis->get($mobile) != $qcode){
        	self::AjaxReturn('验证码错误','',0);
            return;
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
        if(input('jpush')){//把用户当前设备jpushid和用户uid绑定
            $info = Db::name('user')->where(['jpush'=>input('jpush')])->update(['jpush' =>'']);
            Db::name('user')->where(['user_mobile'=>$nickname])->update(['jpush' => input('jpush')]);
                // $jpush = new Jpush();
                // $jpush->notifyAllUser(input('jpush'),'车辆已找到');//极光
        }
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
    public function login_safe(){//安全登录验证
        // $token = '{05B433F7-E29A-940E-2CB4-0C79F22F157E}';//验证token
        // $token = md5($token);
        // if($tokens != $_POST['token']){
        //     self::AjaxReturn('系统繁忙,请稍候再试');
        //     return;
        // }
        $nickname = input('username');
        $password = input('password');
        // $info = Db::name('user')->where(['user_mobile'=>$nickname])->find();
        $info = user::start()->login($nickname,$password);
        if($info == 'SUCCESS'){//登陆成功
            $num = rand(100000,999999);
            $time = time();
            $uid = session('userinfo')['user_id'];
            $token = $num.$time.$uid;//拼接token
            $data = session('userinfo');
            $data['token'] = sha1($token);
            $this->redis->set($uid,$data['token'],100000);//把token存储在服务端
            self::AjaxReturn('登陆成功',$data);//token连同用户信息传到前台
        }else if($info == 'FAIL'){
            self::AjaxReturn('用户名或者密码错误','',0);
        }else if($info == 'FAIL01'){
            self::AjaxReturn('用户不存在','',0);
        }
        // if(!empty($info)){
        //     if($info['user_password'] == md5(sha1($password))){
        //         session('userinfo',$info);
        //         self::AjaxReturn('登陆成功',$info);
        //     }else{
        //         self::AjaxReturn('用户名或者密码错误','',0);
        //     }
        // }else{
        //     self::AjaxReturn('用户不存在','',0);
        // }
    }
    //忘记密码
    public function forget(){
        $qcode = input('qcode');
        $mobile = input('user_mobile');
        $pwd = input('user_password');
        if($this->redis->get($mobile) != $qcode){
        	self::AjaxReturn('验证码错误','',0);
            return;
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
        $userAnth = Db::name('user')->where(['user_id'=>$this->uid])->value('user_anthen');

        $type = input('type');
        if(!$type) {
            self::AjaxReturn('','出现错误,请重试',0);
        }

        //企业用户
        if($type == 'enterprise'){
            $is = Db::name('terprise')->where(['enuser_uid'=>$this->uid])->field('en_id')->find();

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
                'enuser_uid'=>$this->uid,
                'en_time'=> getStrtime(),
            );
            if ($userAnth == -2 || $is) {
                $re = Db::name('terprise')->where(['enuser_uid' => $this->uid])->update($insert);
            } else {
                $re = Db::name('terprise')->insert($insert);
            }
            if($re){
                //修改用户认证状态
                $userAuthen = config('certificationAudit') ? 4 : 2;
                user::start()->editAuth($this->uid,$userAuthen);
                self::AjaxReturn('成功','',1);
            }else{
                self::AjaxReturn('保存失败','',0);
            }
        }else{
            $is = Db::name('personal')->where(['per_uid'=>$this->uid])->field('per_id')->find();

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
                'per_uid'=>$this->uid,
                'per_addtime'=> getStrtime(),
            );

            if ($userAnth == -1 || $is) {
                $re = Db::name('personal')->where(['per_uid' => $this->uid])->update($insert);
            } else {
                $re = Db::name('personal')->insert($insert);
            }
            if($re){
                $userAuthen = config('certificationAudit') ? 3 : 1;
                //修改用户认证状态
                $rz = user::start()->editAuth($this->uid,$userAuthen);
                if($rz){
                    self::AjaxReturn('保存成功','',1);
                }else{
                    self::AjaxReturn('保存失败','',0);
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
     public function article_new(){
        $id = input('id');
        $switch = input('swt');
        if($switch == 'content'){
            $list = Db::name('article_list')->where(['id'=>$id])
                // ->join('acate','np_acate.cate_id=np_article.art_cate')
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
    //用户相关 企业认证审核机制 2018-4-11
    //enterAuth表示入口权限，locationHred跳转权限
    public function user_new(){
        $uid = input('uid');
        $sw = input('sw');
        if($sw == 'info') {
            $info = Db::name('user')
                ->where(['user_id'=>$uid])
                ->field('user_nickname,user_anthen,user_header,user_mobile,company_fail_reason,personal_fail_reason')
                ->find();

            if ($info['user_anthen'] == 0 || $info['user_anthen'] == -1 || $info['user_anthen'] == 3) {
                $info['enterAuth'] = false;
            } else {
                $info['enterAuth'] = true;
            }

            $auth = [];
            if($info['user_anthen'] == 0){
                $auth['showButton'] = '未认证';
                $auth['showButtonCenter'] = '未认证';
                $auth['locationHref'] = true;
            }

            if ($info['user_anthen'] == 1 || $info['user_anthen'] == 3 ){
                $auth = Db::name('personal')
                    ->where(['per_uid'=>$uid])
                    ->find();

                if ($info['user_anthen'] == 1) {
                    $auth['showButton'] = '升级企业认证';
                    $auth['showButtonCenter'] = '个人已认证';
                    $auth['locationHref'] = true;
                }

                if ($info['user_anthen'] == 3) {
                    $auth['showButton'] = '个人认证审核中';
                    $auth['showButtonCenter'] = '个人认证审核中';
                    $auth['locationHref'] = false;
                }

            }

            if ($info['user_anthen'] == 2 || $info['user_anthen'] == 4){
                $auth = Db::name('terprise')
                    ->where(['enuser_uid'=>$uid])
                    ->find();

                if ($info['user_anthen'] == 2) {
                    $auth['showButton'] = false;
                    $auth['showButtonCenter'] = '企业已认证';
                    $auth['locationHref'] = false;
                }

                if ($info['user_anthen'] == 4) {
                    $auth['showButton'] = '企业认证审核中';
                    $auth['showButtonCenter'] = '企业认证审核中';
                    $auth['locationHref'] = false;
                    $personalAuth = Db::name('personal')
                    ->where(['per_uid'=>$uid])
                    ->find();
                    $info['enterAuth'] = $personalAuth ? true : false ;
                }
            }

            if ($info['user_anthen'] == -1) {
                $auth = Db::name('personal')
                    ->where(['per_uid'=>$uid])
                    ->find();
                $auth['showButton'] = '重新审核';
                $auth['showButtonCenter'] = '个人认证审核未通过';
                $auth['locationHref'] = true;
                $auth['reason'] = json_decode($info['personal_fail_reason']);
            }

            if ($info['user_anthen'] == -2) {
                $auth = Db::name('terprise')
                    ->where(['enuser_uid'=>$uid])
                    ->find();
                $personalAuth = Db::name('personal')
                    ->where(['per_uid'=>$uid])
                    ->find();
                $info['enterAuth'] = $personalAuth ? true : false ;
                $auth['showButton'] = '重新审核';
                $auth['showButtonCenter'] = '企业认证审核未通过';
                $auth['locationHref'] = true;
                $auth['reason'] = json_decode($info['company_fail_reason']);
            }

            $info['auth'] = $auth;
            self::AjaxReturn($info);
        }
    }

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
                    ->find();
            }elseif ($info['user_anthen'] == 1){
                $auth = Db::name('personal')
                    ->where(['per_uid'=>$uid])
                    ->find();
            };
            $info['auth'] = $auth;
            self::AjaxReturn($info);
        }
    }

    public function userTeamList ()
    {
        $uid = input('uid');

        if ($uid) {
            $userList = User::myTeamUsers($uid);

            if ($userList) {
                self::AjaxReturn($userList, '获取团队列表成功');
            } else {
                self::AjaxReturn($userList, '获取团队列表失败', 0);
            }
        }
    }

    /**
     * [assignOrderToUser 大区经理分配订单]
     * @Author   jfeng
     * @DateTime 2018-01-16T09:38:43+0800
     * @param    single_order   int   订单id
     * @param    single_uid     int   大区经理id
     * @param    use_id         int   用户id
     * @return   boole
     */
    public function assignOrderToUser ()
    {
        $orderId = input('single_order');
        $singleUid = input('single_uid');
        $uid = input('user_id');

        if ($singleUid && $orderId) {
            $map['single_uid'] = $singleUid;
            $map['single_order'] = $orderId;
            $res = Db::name('single')->where($map)->update(['assign_id' => $uid]);
            // echo json_encode($res);
            if ($res) {
                Db::name('exorder')->where('ex_oid', $orderId)->update(['ex_status' => 2]);
                self::AjaxReturn($res, '订单分配成功', 1);
            } else {
                self::AjaxReturn($res, '订单分配失败', 0);
            }
        } else {
            self::AjaxReturn([], '数据错误', 0);
        }

}

    /**
     * [proxyUserBelogUsers 获取大区经理所有的用户]
     * @param    int    user_id   大区经理id
     * @return
     */
    public function proxyUserBelogUsers ()
    {
        $uid = input('user_id');

        if ($uid) {
            $users = User::where('relation_id', $uid)->select();

            $users = Db::name('user')
                ->join('np_personal','np_personal.per_uid=np_user.user_id','left')
                ->join('np_terprise','np_terprise.enuser_uid=np_user.user_id','left')
                ->where('relation_id', $uid)
                ->select();
            if (count($users) > 0) {
                self::AjaxReturn($users, '成功获取用户');
            } else {
                self::AjaxReturn($users, '暂时没有用户', 0);
            }
        } else {
             self::AjaxReturn([], '参数错误', 0);
        }
    }

    /**
     * [proxyUserBelogUsers 普通用户订单列表]
     * @param    int    user_id   用户id
     * @param    int    status    订单状态 （选填 1执行中，2交接中，3已完成）
     * @return
     */
    public function userOrderList ()
    {
        $uid = input('user_id');
        $status = input('status') ?: 1;
        $where['assign_id'] = $uid;
        $where['ex_status'] = $status;
        $order = input('order_by') ==1 ? 'DESC' : 'ASC';

        $list = $this->getOrderDetails($where, $order);

        if (count($list) > 0) {
            self::AjaxReturn($list, '获取订单列表成功');
        } else {
            self::AjaxReturn($list, '暂无订单', 0);
        }

    }

    public function statisticsOrderByUser ()
    {
        $userId   = input('user_id');
        $userRole = input('role');
        $map      = [];
        //$map['ex_status'] = 3;  //完成的订单

        if ($userRole == 1) {
            $map['single_uid'] = $userId;
        } else {
            $map['assign_id'] = $userId;
        }

        $orderData = $this->getOrderDetails($map);
        $orderData['total_num'] = count($orderData) ?: 0 ;
        $orderData['order_num_status1'] = 0;
        $orderData['order_num_status2'] = 0;
        $orderData['order_num_status4'] = 0;
        $orderData['total_money'] = 0;
        foreach ($orderData as $order => $data) {
            if ($data['ex_status'] == 4) {
                $orderData['total_money'] += $data['ex_money'];
                $orderData['order_num_status4']++;
            }

            if ($data['ex_status'] == 2) {
                $orderData['order_num_status2']++;
            }

            if ($data['ex_status'] == 1) {
                $orderData['order_num_status1']++;
            }
        }

        if (count($orderData) > 0) {
            self::AjaxReturn($orderData, '获取订单列表成功');
        } else {
            self::AjaxReturn($orderData, '暂无订单', 0);
        }

    }

    private function getOrderDetails ($where, $order = 'ASC')
    {
        $list = Db::name('single')
            ->join('np_exorder','np_exorder.ex_oid=np_single.single_order','left')
            ->join('np_findcard','np_findcard.find_id=np_exorder.ex_fid','left')
            ->where($where)
            ->order(['ex_addtime' => $order])
            ->select();

        return $list;
    }

    public function isDeposit()
    {
        if (!config('depositButton')) {
            return self::AjaxReturn(2, '已经开启免押金设置', 1);
        }

        $uid = input('uid');

        $deposit = Db::name('user')->where(['user_id' => $uid])->value('deposit');

        if (config('system_up.findcard') == true) {
            $deposit['deposit'] = 2;
            return self::AjaxReturn($deposit, '系统升级中', 1);
        }

        if ($deposit != 2) {
            return self::AjaxReturnError('您还没有交押金，请先交押金。', $deposit);
        }

        return self::AjaxReturn($deposit, '已交押金用户', 1);

    }

}
