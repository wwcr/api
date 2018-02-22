<?php
/**
 * Created by PhpStorm.
 * User: iyahe@qq.com (天明)
 * Date: 2017/10/27/027
 * Time: 10:38
 */

namespace app\api\module;

use think\Controller;
use think\Db;
use Aliyun\Core\Config as AliyunConfig;
use Aliyun\Core\Profile\DefaultProfile;
use Aliyun\Core\DefaultAcsClient;
use Aliyun\Api\Sms\Request\V20170525\SendSmsRequest;
use Aliyun\Api\Sms\Request\V20170525\QuerySendDetailsRequest;
use think\Model;

class sms extends Model
{
    public function index()
    {
    }

    public function send($config)
    {
        // 阿里云Access Key ID和Access Key Secret 从 https://ak-console.aliyun.com 获取
        $appSecret = 'tIm9L6PtHpNMc2deHlZeOW0MkLBm6g';
        $appKey = 'LTAI9j2zeBlfipgh';
        // 短信签名 详见：https://dysms.console.aliyun.com/dysms.htm?spm=5176.2020520001.1001.3.psXEEJ#/sign
        $signName = $config['sign'];
        // 短信模板Code https://dysms.console.aliyun.com/dysms.htm?spm=5176.2020520001.1001.3.psXEEJ#/template
        $template_code = $config['template_code'];// '你的短信模版CODE';
        // 短信中的替换变量json字符串
        $json_string_param = json_encode($config['json_string_param']);// '你的短信变量替换字符串';
        // 接收短信的手机号码
        $phone = $config['phone'];//'接收短信的手机号码';
        // 初始化阿里云config
        AliyunConfig::load();
        // 初始化用户Profile实例
        $profile = DefaultProfile::getProfile("cn-hangzhou", $appKey, $appSecret);
        DefaultProfile::addEndpoint("cn-hangzhou", "cn-hangzhou", "Dysmsapi", "dysmsapi.aliyuncs.com");
        $acsClient = new DefaultAcsClient($profile);
        // 初始化SendSmsRequest实例用于设置发送短信的参数
        $request = new SendSmsRequest();
        // 必填，设置短信接收号码
        $request->setPhoneNumbers($phone);
        // 必填，设置签名名称
        $request->setSignName($signName);
        // 必填，设置模板CODE
        $request->setTemplateCode($template_code);
        // 可选，设置模板参数
        if (!empty($json_string_param)) {
            $request->setTemplateParam($json_string_param);
        }
        // 发起请求
        $acsResponse = $acsClient->getAcsResponse($request);
        // 默认返回stdClass，通过返回值的Code属性来判断发送成功与否
        if ($acsResponse && strtolower($acsResponse->Code) == 'ok') {
            return true;
        }
        return $acsResponse->Message;
    }
}