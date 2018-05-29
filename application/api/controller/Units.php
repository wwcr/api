<?php
/**
 * Created by PhpStorm.
 * User: iyahe@qq.com (天明)
 * Date: 2017/10/22/022
 * Time: 22:37
 */
namespace app\api\controller;
use app\index\controller\Action;
use app\index\module\article;
use app\index\module\login;
use app\index\module\user;
use think\Db;
use OSS\Core\OssException;
use OSS\OssClient;
use think\Config;

class Units extends Action
{
    private $path;
    public function __construct()
    {
        parent::__construct();
        $this->path = 'public_up/upload/' . date('y').'/'.date('m').'/';
    }

    public function index(){
        echo 'units';
    }
    // public function imgstr(){
    //     if(!is_dir($this->path)) mk_dirs($this->path);
    //     $imgstr = input('imgstr');
    //     $ext = input('ext','jpg');
    //     if($imgstr){
    //         $formFile = explode(',', $imgstr);
    //         $img = base64_decode($formFile[1]);
    //         $path = $this->path.md5(time()).'.'.$ext;
    //         $len = file_put_contents($path,$img);
    //         if($len) self::AjaxReturn('/'.$path,'上传成功');
    //     }
    // }
    public function imgstr()
    {   
        if(!is_dir($this->path)) mk_dirs($this->path);
        $imgstr = input('imgstr');
        $ext = input('ext','jpg');
        if($imgstr){
            $formFile = explode(',', $imgstr);
            $img = base64_decode($formFile[1]);
            $path = $this->path.md5(time()).'.'.$ext;
            file_put_contents($path,$img);
        // 尝试执行
        try {
            $config = config('aliyun_oss'); //获取Oss的配置
            // var_dump($config);die();
            //实例化对象 将配置传入
            $ossClient = new OssClient($config['KeyId'], $config['KeySecret'], $config['Endpoint']);
            //这里是有sha1加密 生成文件名 之后连接上后缀
            // $fileName = sha1(date('YmdHis', time()) . uniqid()) . '.' . $resResult->type();
            //执行阿里云上传 
            $result = $ossClient->uploadFile($config['Bucket'], $path, $path);
            if($result['info']['url']){
                // unlink($path);先不删除
                self::AjaxReturn('/'.$path,'上传成功');
            }

        } catch (OssException $e) {
            return $e->getMessage();
        }
        //将结果输出
    }
}
    public function fromdata(){}
}