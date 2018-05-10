<?php
/**
 * Created by PhpStorm.
 * User: iyahe@qq.com (天明)
 * Date: 2017/11/25/025
 * Time: 16:26
 */
namespace app\api\controller;
use app\index\controller\Action;
use app\index\module;
use think\Db;
use app\api\validate;
use EasyWeChat\Payment\Order;

class H5pay extends Action
{
    public function index(){
        $config = require_once root.'/config/wechat.php';
        $params = new \Yurun\PaySDK\Weixin\Params\PublicParams;
        $params->appID = $config['appid'];
        $params->mch_id = $config['mch_id'];
        $params->key = $config['key'];
        // SDK实例化，传入公共配置
        $pay = new \Yurun\PaySDK\Weixin\SDK($params);
        // 支付接口
        $request = new \Yurun\PaySDK\Weixin\H5\Params\Pay\Request;
        $request->body = 'test'; // 商品描述
        $request->out_trade_no = 'test' . mt_rand(10000000,99999999); // 订单号
        $request->total_fee = 1; // 订单总金额，单位为：分
        $request->spbill_create_ip = '127.0.0.1'; // 客户端ip，必须传正确的用户ip，否则会报错
        $request->notify_url = $config['pay_notify_url']; // 异步通知地址
        $request->scene_info = new \Yurun\PaySDK\Weixin\H5\Params\SceneInfo;
        $request->scene_info->type = 'Wap'; // 可选值：IOS、Android、Wap
        $request->sub_mch_id = $config['sub_mch_id'];
        // 下面参数根据type不同而不同
        $request->scene_info->wap_url = 'https://baidu.com';
        $request->scene_info->wap_name = 'test';
        // 调用接口
        $result = $pay->execute($request);
        p($result);
        if($pay->checkResult()) {
            // 跳转支付界面
            header('Location: ' . $result['mweb_url']);
        } else {
            p($pay->getErrorCode() . ':' . $pay->getError());
        }
    }
}