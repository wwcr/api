<?php
/**
 * Created by PhpStorm.
 * User: iyahe@qq.com (天明)
 * Date: 2017/11/24/024
 * Time: 22:03
 */
namespace app\api\controller;
use app\index\controller\Action;
use app\index\module;
use think\Db;
use app\api\validate;
use EasyWeChat\Payment\Order;
use tool\wechat\Wechat;
use tool\curl\Curl;
use EasyWeChat\Foundation\Application;
use think\Controller;
class Pay extends Action
{
    public function __construct()
    {
        parent::__construct();
    }
    public function mkorder(){
        // $payment = new Application();
        $app = new Application();
        $payment = $app->payment;
        $attributes = [
            'trade_type'       => 'APP', // JSAPI，NATIVE，APP...
            'body'             => 'iPad mini 16G 白色',
            'detail'           => 'iPad mini 16G 白色',
            'out_trade_no'     => '121775250120140703323dasdasd3368018',
            'total_fee'        => 5388, // 单位：分
            'notify_url'       => 'http://baidu.com/order-notify', // 支付结果通知网址，如果不设置则会使用配置里的默认地址
            // 'openid'           => '当前用户的 openid', // trade_type=JSAPI，此参数必传，用户在商户appid下的唯一标识，
            // 'mch_id' => "1494581762",
        ];
        $order = new Order($attributes);
        $result = $payment->prepare($order);
        var_dump($result);
        if ($result->return_code == 'SUCCESS' && $result->result_code == 'SUCCESS'){
            $prepayId = $result->prepay_id;
            $config = $payment->configForJSSDKPayment($prepayId); // 返回数组

        }
    }
    public function index(){
        echo 'pay';
    }
    public function notify(){
        p($_REQUEST);
    }
    public function get_order_price($order){
        $order = new module\order();
        $res = $order->get_price($order);
        return $res[0]['order_price'];
    }

    //参数nurse存在时为看护支付形式，不存在时为订单支付形式
    public function wechat_pay(){//微信支付
         // echo 11111;
        $pay = new Wechat();
        $type = 1;
        // $out_trade_no = input('post.order');
        if (input('post.order')) {
            $out_trade_no = input('post.order'). '_' . uniqid(); //查看寻车订单详细位置时传的参数
        }

        if (input('post.find_id')) {
            $out_trade_no = input('post.find_id'). '_' . uniqid(); //看护订单,看护支付时传的参数
        }

        if (input('post.user_id')) {
            $out_trade_no = input('post.user_id') . '_' . uniqid(); //用户交押金时传的参数
        }

        $total_fee = input('post.price')*100;
        // file_put_contents(dirname(__FILE__).'/wechattest.txt', input('post.switch'));
        // $res = Db::name('test')->insert(input('post.switch'));
        // $res = $pay->unifiedOrder(3133454536456131313);//统一下单
        $switch = input('post.switch');
        if ($switch == 'nurse') {
            // $total_fee = 5000*100;
            $res = $pay->unifiedOrder($out_trade_no,$type,$total_fee,'nurse');//统一下单
        } else if ($switch == 'deposit') {
            // echo "<script> alert('系统升级中...'); </script>";die;
            $res = $pay->unifiedOrder($out_trade_no,$type,$total_fee,'deposit');//统一下单
        } else {
            $res = $pay->unifiedOrder($out_trade_no,$type,$total_fee);//统一下单

        }

        $res['mweb_url'] = $res['mweb_url'];

        // $type = input('type');
        // if ($type == 'findcar') {
            // $res['mweb_url'] = $res['mweb_url'] . "&redirect_url=http%3A%2F%2Fh5.wwcrpt.com%2F%23%2Fcarnurse%2Fsuccess";
        // } else {
            // $res['mweb_url'] = $res['mweb_url'] . "&redirect_url=http%3A%2F%2Fh5.wwcrpt.com%2F%23%2Fcarnurse%2Fsuccess";
        // }


        // file_put_contents(dirname(__FILE__).'/wechatdeposit.txt', $res['mweb_url'], true);

        echo json_encode($res);

    }


    public function wechat_pay_new(){//微信支
        // $type = input('post.type');
        $type = 2;
        $pay = new Wechat();
        if (input('post.order')) {
            $out_trade_no = input('post.order'). '_' . uniqid(); //查看寻车订单详细位置时传的参数
        }

        if (input('post.find_id')) {
            $out_trade_no = input('post.find_id'). '_' . uniqid(); //看护订单,看护支付时传的参数
        }

        if (input('post.user_id')) {
            $out_trade_no = input('post.user_id') . '_' . uniqid(); //用户交押金时传的参数
        }

        $total_fee = input('post.price')*100;
        $switch = input('post.switch');

        if ($switch == 'nurse') {
            $total_fee = 5000*100;
            $res = $pay->unifiedOrder($out_trade_no,$type,$total_fee,'nurse');//统一下单
        } else if ($switch == 'deposit') {
            $res = $pay->unifiedOrder($out_trade_no,$type,$total_fee,'deposit');//统一下单
        } else {
            $res = $pay->unifiedOrder($out_trade_no,$type,$total_fee);//统一下单

        }

        if($type == 1){//H5支付
            // $res = $pay->unifiedOrder($out_trade_no,$type);//统一下单
            $res['mweb_url'] = $res['mweb_url'];
            // file_put_contents(dirname(__FILE__).'/wechattest.txt', $res['mweb_url']);

            echo json_encode($res);
        }else{ //调用APP支付
            // $res = $pay->unifiedOrder($out_trade_no,$type);
            if($res['return_code'] == 'SUCCESS' && $res['result_code'] == 'SUCCESS'){//调用成功
                 $data = array(
            'appid'                =>    $res['appid'],
            'partnerid'            =>    $res['mch_id'],
            'prepayid'            =>    $res['prepay_id'],
            'noncestr'        =>    $res['nonce_str'],
            'timestamp'            =>    time(),
            'package'        =>    'Sign=WXPay',
            );
                    // 拼装数据进行第三次签名
            $data['sign'] = $pay->MakeSign($data);        // 获取签名
            echo json_encode($data);
            }
        }

    }
    public function get_payinfo(){
    	$postStr = file_get_contents('php://input');
    	 $obj = (array)simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
    	 // $out_trade_no = $msg['out_trade_no'];//订单号
    	 $res = json_encode($obj);
        $data = ['data' =>$res];
        $pay = new Wechat();
    	$res = Db::name('test')->insert($data);
        if ($obj) {
        $data = array(
        'appid'                =>    $obj['appid'],
        "fee_type" => $obj['fee_type'],
        "is_subscribe" => $obj['is_subscribe'],
        'mch_id'            =>    $obj['mch_id'],
        'nonce_str'            =>    $obj['nonce_str'],
        'result_code'        =>    $obj['result_code'],
        'openid'            =>    $obj['openid'],
        'trade_type'        =>    $obj['trade_type'],
        'bank_type'            =>    $obj['bank_type'],
        'total_fee'            =>    $obj['total_fee'],
        'cash_fee'            =>    $obj['cash_fee'],
        'transaction_id'    =>    $obj['transaction_id'],
        'out_trade_no'        =>    $obj['out_trade_no'],
        'time_end'            =>    $obj['time_end'],
        "return_code" => $obj['return_code'],
        );
                // 拼装数据进行第三次签名
        $sign = $pay->MakeSign($data);        // 获取签名
        // var_dump($sign);
        $sign1 = ['data' =>$sign];
    	$res = Db::name('test')->insert($sign1);
    // /** 将签名得到的sign值和微信传过来的sign值进行比对，如果一致，则证明数据是微信返回的。 */
        if ($sign == $obj['sign']) {
        	$sign1 = ['data' =>$sign];
    	$res = Db::name('test')->insert($sign1);
            $reply = "<xml>
                        <return_code><![CDATA[SUCCESS]]></return_code>
                        <return_msg><![CDATA[OK]]></return_msg>
                    </xml>";
            // echo $reply;      // 向微信后台返回结果。
            $this->query_order($obj['out_trade_no'],$reply);
            exit;
            }
        }
    }

    public function get_payinfo_nurse(){
        $postStr = file_get_contents('php://input');
         $obj = (array)simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
         // $out_trade_no = $msg['out_trade_no'];//订单号
         $res = json_encode($obj);
        $data = ['data' =>$res];
        $pay = new Wechat();
        $res = Db::name('test')->insert($data);
        if ($obj) {
        $data = array(
        'appid'                =>    $obj['appid'],
        "fee_type" => $obj['fee_type'],
        "is_subscribe" => $obj['is_subscribe'],
        'mch_id'            =>    $obj['mch_id'],
        'nonce_str'            =>    $obj['nonce_str'],
        'result_code'        =>    $obj['result_code'],
        'openid'            =>    $obj['openid'],
        'trade_type'        =>    $obj['trade_type'],
        'bank_type'            =>    $obj['bank_type'],
        'total_fee'            =>    $obj['total_fee'],
        'cash_fee'            =>    $obj['cash_fee'],
        'transaction_id'    =>    $obj['transaction_id'],
        'out_trade_no'        =>    $obj['out_trade_no'],
        'time_end'            =>    $obj['time_end'],
        "return_code" => $obj['return_code'],
        );
                // 拼装数据进行第三次签名
        $sign = $pay->MakeSign($data);        // 获取签名
        // var_dump($sign);
        $sign1 = ['data' =>$sign];
        $res = Db::name('test')->insert($sign1);
    // /** 将签名得到的sign值和微信传过来的sign值进行比对，如果一致，则证明数据是微信返回的。 */
        if ($sign == $obj['sign']) {
            $sign1 = ['data' =>$sign];
        $res = Db::name('test')->insert($sign1);
            $reply = "<xml>
                        <return_code><![CDATA[SUCCESS]]></return_code>
                        <return_msg><![CDATA[OK]]></return_msg>
                    </xml>";
            // echo $reply;      // 向微信后台返回结果。
            $this->query_order($obj['out_trade_no'],$reply,'nurse');
            exit;
            }
        }
    }

    public function get_payinfo_deposit(){
        $postStr = file_get_contents('php://input');
         $obj = (array)simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
         // $out_trade_no = $msg['out_trade_no'];//订单号
         $res = json_encode($obj);
        $data = ['data' =>$res];
        $pay = new Wechat();
        $res = Db::name('test')->insert($data);
        if ($obj) {
        $data = array(
        'appid'                =>    $obj['appid'],
        "fee_type" => $obj['fee_type'],
        "is_subscribe" => $obj['is_subscribe'],
        'mch_id'            =>    $obj['mch_id'],
        'nonce_str'            =>    $obj['nonce_str'],
        'result_code'        =>    $obj['result_code'],
        'openid'            =>    $obj['openid'],
        'trade_type'        =>    $obj['trade_type'],
        'bank_type'            =>    $obj['bank_type'],
        'total_fee'            =>    $obj['total_fee'],
        'cash_fee'            =>    $obj['cash_fee'],
        'transaction_id'    =>    $obj['transaction_id'],
        'out_trade_no'        =>    $obj['out_trade_no'],
        'time_end'            =>    $obj['time_end'],
        "return_code" => $obj['return_code'],
        );
                // 拼装数据进行第三次签名
        $sign = $pay->MakeSign($data);        // 获取签名
        // var_dump($sign);
        $sign1 = ['data' =>$sign];
        // file_put_contents(dirname(__FILE__).'/wechatdeposit2.txt', $obj['out_trade_no'], true);

        $res = Db::name('test')->insert($sign1);
    // /** 将签名得到的sign值和微信传过来的sign值进行比对，如果一致，则证明数据是微信返回的。 */
        if ($sign == $obj['sign']) {
            $sign1 = ['data' =>$sign];
        $res = Db::name('test')->insert($sign1);
            $reply = "<xml>
                        <return_code><![CDATA[SUCCESS]]></return_code>
                        <return_msg><![CDATA[OK]]></return_msg>
                    </xml>";
            // echo $reply;      // 向微信后台返回结果。
            $this->query_order($obj['out_trade_no'],$reply,'deposit');
            exit;
            }
        }
    }
    public function query_order($order,$reply,$switch){//查询订单
    	 // $sign1 = ['data' =>$switch];
    	 // $res = Db::name('test')->insert($sign1);
         $out_trade_no = $order;
         $pay = new Wechat();
         $res = $pay->orderQuery($out_trade_no);
         if($res['return_code'] == 'SUCCESS' && $res['result_code'] == 'SUCCESS'){
                if ($switch == 'nurse' ) {
                    $out_trade_no = strchr($out_trade_no, '_', true);
                    if($res['trade_state'] == 'SUCCESS'){//该订单交易成功
                        Db::startTrans();
                    try{
                        $res = Db::name('findcard')->where('find_id',$out_trade_no)->update(['car_status' => 4, 'nurse_time' => time()]);//支付成功
                        // 提交事务
                        Db::commit();
                        echo $reply; //支付成功同时数据库修改成功 向微信返回结果
                     }catch (\Exception $e) {
                        // 回滚事务
                        Db::rollback();
                        }
                    }else if($res['trade_state'] == 'PAYERROR'){
                           Db::startTrans();
                    try{
                        Db::name('findcard')->where('find_id',$out_trade_no)->update(['car_status' => -1, 'nurse_time' => time()]);//支付失败
                        // 提交事务
                        Db::commit();
                     }catch (\Exception $e) {
                        // 回滚事务
                        Db::rollback();
                        }
                    }

                } else if($switch == 'deposit'){

                    $out_trade_no = strchr($out_trade_no, '_', true);
                    file_put_contents(dirname(__FILE__).'/wechatdeposit3.txt', $res['trade_state'], true);
                    if($res['trade_state'] == 'SUCCESS'){//该订单交易成功
                        Db::startTrans();
                    try{
                        $res = Db::name('user')->where('user_id',$out_trade_no)->update(['deposit' => 2, 'deposit_addtime' => time()]);//支付成功
                        // 提交事务
                        Db::commit();
                        echo $reply; //支付成功同时数据库修改成功 向微信返回结果
                     }catch (\Exception $e) {
                        // 回滚事务
                        Db::rollback();
                        }
                    }else if($res['trade_state'] == 'PAYERROR'){
                           Db::startTrans();
                    try{
                        $res = Db::name('user')->where('user_id',$out_trade_no)->update(['deposit' => -1, 'deposit_addtime' => time()]);//支付失败
                        // 提交事务
                        Db::commit();
                     }catch (\Exception $e) {
                        // 回滚事务
                        Db::rollback();
                        }
                    }

                } else {
                    $out_trade_no = strchr($out_trade_no, '_', true);
                    if($res['trade_state'] == 'SUCCESS'){//该订单交易成功
                        Db::startTrans();
                    try{
                        $res = Db::name('pay')->where('pay_order',$out_trade_no)->update(['pay_status' => 2, 'pay_time' => time()]);//支付成功
                        $res = Db::name('findcard')->where('card_order',$out_trade_no)->update(['car_status' => 3]);//订单支付完成状态
                        // 提交事务
                        Db::commit();
                        echo $reply; //支付成功同时数据库修改成功 向微信返回结果
                     }catch (\Exception $e) {
                        // 回滚事务
                        Db::rollback();
                        }
                    }else if($res['trade_state'] == 'PAYERROR'){
                           Db::startTrans();
                    try{
                        Db::name('pay')->where('pay_order',$out_trade_no)->update('pay_status',-1);//支付失败
                        // 提交事务
                        Db::commit();
                     }catch (\Exception $e) {
                        // 回滚事务
                        Db::rollback();
                        }
                    }

                }
         }
    }
    public function query_ordertest(){//查询订单
        $out_trade_no = 140;
        $pay = new Wechat();
        $res = $pay->orderQuery($out_trade_no);
        var_dump($res);
    }

    //支付成功后给大区经理发送
    public function sendMessageToManager($findId)
    {
        //获取订单的城市

        //查找该城市的大区经理

        //给大区经理发送消息
    }

}