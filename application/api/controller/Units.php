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
    public function imgstr(){
        if(!is_dir($this->path)) mk_dirs($this->path);
        $imgstr = input('imgstr');
        $ext = input('ext','jpg');
        if($imgstr){
            $formFile = explode(',', $imgstr);
            $img = base64_decode($formFile[1]);
            $path = $this->path.md5(time()).'.'.$ext;
            $len = file_put_contents($path,$img);
            if($len) self::AjaxReturn('/'.$path,'上传成功');
        }
    }
    public function fromdata(){}
}