<?php
/**
 * Created by PhpStorm.
 * User: iyahe@qq.com (天明)
 * Date: 2017/7/26
 * Time: 14:30
 */

namespace app\index\controller;

use think\Controller;

class Action extends Controller
{
    public static $repost;
    public $uid;
    public $limit = 1000;
    public function __construct()
    {
        $this->uid = input('uid');
        if($_POST) self::$repost = true;
        parent::__construct();
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
    public static function logger($str,$name='',$lev=3,$name='info'){
        if(!is_array($str)){
            $inputData = $str;
        }else{
            $inputData = json_encode($str,JSON_UNESCAPED_UNICODE);
        }
        $insert = '['.$name.']['.date('Y-m-d H:i:s',time()).']'.PHP_EOL.'-------------------------------------'.PHP_EOL.$inputData.PHP_EOL.'-------------------------------------'.PHP_EOL;
        file_put_contents($_SERVER['DOCUMENT_ROOT'].'/public/log/info.log',$insert,FILE_APPEND);
    }
}
