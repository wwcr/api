<?php
/**
 * Created by PhpStorm.
 * User: iyahe@qq.com (天明)
 * Date: 2017/7/26
 * Time: 14:30
 */

namespace app\index\controller;

use think\Controller;
use \think\Request;
use think\Db;

class Action extends Controller
{
    public static $repost;
    public $uid;
    public $token;
    public $limit = 1000;
    public function __construct()
    {
        $this->uid = input('uid');
        $currentToken = $this->token = input('token');
        $configToken = config('apiToken');
        $request = Request::instance();
        $controller  = $request->controller();
        $action  = $request->action();
        $tokenSwitch = $configToken['switch'];
        $c = in_array($controller, $configToken['controller']);
        $a = in_array($action, $configToken['action']);

        if ($tokenSwitch && $c && !$a) {
            if ($this->uid) {
                $user_token = Db::name('user')->where('user_id', input('uid'))->value('user_token');
                $tokens = $configToken['value'] . '_' . $user_token;
            } else {
                $tokens = $configToken['value'];
            }

            if ($tokens != $currentToken) {
                self::AjaxReturn('','权限不足',0);
            }
        }

        if($_POST) self::$repost = true;
        parent::__construct();
    }

/*.........*/
    public function proxy_get($uid){//连接代理的单独服务器
        $res = Db::name('proxydb')->where('uid',$uid)->find();
        if($res){
            return $this->proxy_db($res['user'],$res['pas'],$res['ip'],$res['db']);
        }
    }
    public function proxy_db($user,$pas,$ip,$db){//连接代理的单独服务器
        $res = Db::connect('mysql://'.$user.':'.$pas.'@'.$ip.'/'.$db.'#utf8');
        return $res;
    }
    /** 返回状态到客户端
     * @param array  $data
     * @param string $info
     * @param int    $code
     */
    public static function AjaxReturn($data=[],$info='',$code=1){
       $data = [
           'info'=>$data,
           'msg'=>$info,
           'code'=>$code
       ];
       ajaxReturn($data);
    }
    public static function ajaxReturnError($msg='',$data=[]){
       self::AjaxReturn($data,$msg,0);
    }
    public static function ajaxReturnSuccess($msg,$data=[]){
       self::AjaxReturn($data,$msg,1);
    }
    public static function returnLuck($msg){
        if($msg){
            self::AjaxReturn('操作成功',$msg,1);
        }else{
            self::AjaxReturn('操作失败',$msg,0);
        }
    }
    public function getpwd($str){
        return md5(sha1($str));
    }
    public static function logger($str,$name='',$lev=3){
        if(!is_array($str)){
            $inputData = $str;
        }else{
            $inputData = json_encode($str,JSON_UNESCAPED_UNICODE);
        }
        $insert = '['.$name.']['.date('Y-m-d H:i:s',time()).']'.PHP_EOL.'-------------------------------------'.PHP_EOL.$inputData.PHP_EOL.'-------------------------------------'.PHP_EOL;
        file_put_contents($_SERVER['DOCUMENT_ROOT'].'/public/log/info.log',$insert,FILE_APPEND);
    }
    public function check_token($uid,$token){//检测token
        if($this->redis->get($uid) != $token){
            return 'fail';
        }else{
            return 'success';
        }
    }
}
