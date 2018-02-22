<?php
/**
 * Created by PhpStorm.
 * User: iyahe@qq.com (天明)
 * Date: 2017/7/26
 * Time: 17:05
 */
namespace app\index\controller;
use app\api\module\sms;
use think\Db;

/** 工具类
 * Class Toolure
 * @package app\index\controller
 */
class Toolure extends Action
{
    public function index(){
        $act = input('act','test');
        return $this->$act();
    }
    public function test(){
        echo 'test';
    }
    //普通的图片上传
    public function upload()
    {
        $fileName = $_SERVER['DOCUMENT_ROOT'];
        if($_FILES){
            self::logger($_FILES,'提交的post流');
            $ext = explode('/',$_FILES['file']['type']);
            $exter = $ext[1];
            if($ext[1] == 'octet-stream'){
                $exter = 'png';
            }
            self::logger($ext,'后缀');
            $path = '/wwcr/public/uplaod/'.md5(time()).'-'.md5($_FILES['file']['name']).'.'.$exter;
            $r = move_uploaded_file($_FILES['file']["tmp_name"],$fileName.$path);
            if($r){
                self::logger($path,'路径');
                self::AjaxReturn($path,'上传成功');
            }else{
                self::AjaxReturn($path,'上传失败',0);
            }
        }else{
            //base64形式的图片上传
            $formFile = input('imgStr');
            if($formFile){
                $formFile = explode(',', $formFile);
                $ext = '.jpg';
                $img = base64_decode($formFile[1]);//切割字符串得到图片base64编码
                if ($formFile) {
                    $path = '/wwcr/public/uplaod/'.sha1(time()).'-'.uniqid().$ext;
                    $put = file_put_contents($fileName.$path, $img);//返回的是字节数
                    if($put){
                        self::AjaxReturn($path,'上传成功');
                    }else{
                        self::AjaxReturn('','上传失败',0);
                    }
                }
            }else{
                self::AjaxReturn('提交失败','',0);
            }
        }
    }
       public function upload_contract()
    {
            $fileName = $_SERVER['DOCUMENT_ROOT'];
            self::logger($_FILES,'提交的post流');
            $ext = explode('/',$_FILES['image']['type']);
            $exter = $ext[1];
            if($ext[1] == 'octet-stream'){
                $exter = 'png';
            }
            self::logger($ext,'后缀');
            $path = '/wwcr/public/uplaod/'.md5(time()).'-'.md5($_FILES['file']['name']).'.'.$exter;
            $r = move_uploaded_file($_FILES['image']["tmp_name"],$fileName.$path);
            // echo $fileName.$path;
     //        if($r){
                $per=0.3;
                $filename= $fileName.$path;
                echo $filename;
                list($width, $height)=getimagesize($filename);
                $n_w=$width*$per;
                $n_h=$height*$per;
                $new=imagecreatetruecolor($n_w, $n_h);
                $img=imagecreatefrompng($filename);
                //copy部分图像并调整
                imagecopyresized($new, $img,0, 0,0, 0,$n_w, $n_h, $width, $height);
                //图像输出新图片、另存为
                imagepng($new, '111.png');
     //            $dst_path= '12.png';//大图
     //            $src_path = 'pic1.png';
     //            $dst = imagecreatefromstring(file_get_contents($dst_path));
     //            $src = imagecreatefromstring(file_get_contents($src_path));
     //            list($src_w, $src_h) = getimagesize($src_path);
     //            list($dst_w, $dst_h, $dst_type) = getimagesize($dst_path);
     //            $copy_x = $src_w*0.3;
     //            $copy_y = $dst_h - $src_h*2;
     //            imagecopymerge($dst, $src, $copy_x, $copy_y, 0, 0, $src_w, $src_h, 100);
     //            $new_addr = uniqid('show', true).'.png'; 
     //            imagepng($dst,$new_addr);
     //            imagedestroy($dst);
     //            imagedestroy($src);
                   // self::AjaxReturn($path,'上传成功');
                    imagedestroy($new);
                    imagedestroy($img);
     //            self::logger($path,'路径');
                
     //        }else{
     //            self::AjaxReturn($path,'上传失败',0);
     // }
 }
    //发送验证码
    public function qcode(){
        $mobile = input('user_mobile');
        $rands = rand(1000,9999);
        $sms = new sms();
        $data = [
            'template_code' => 'SMS_107025019',
            'json_string_param' => ["code" =>$rands],
            'phone' =>'13879144915',
            'sign'=>'无维科技'
        ];
        $mo = Db::name('user')->where(['user_mobile'=>$mobile])->find();
        if(empty($mo)){
            if($sms->send($data)){
                session('qcode',$rands);
                self::AjaxReturn('验证码发送成功',$rands);
            }else{
                self::AjaxReturnError('验证码发送失败');
            }
        }else{
            self::AjaxReturn('验证码发送失败,您已经注册过了','',0);
        }
    }
    public function car(){
        $data = [
            ['c'=>'北京市','j'=>'京'],
            ['c'=>'天津市','j'=>'津'],
            ['c'=>'河北省','j'=>'冀'],
            ['c'=>'山西省','j'=>'晋'],
            ['c'=>'内蒙古自治区','j'=>'蒙'],
            ['c'=>'辽宁省','j'=>'辽'],
            ['c'=>'吉林省','j'=>'吉'],
            ['c'=>'黑龙江省','j'=>'冀'],
            ['c'=>'上海市','j'=>'沪'],
            ['c'=>'江苏省','j'=>'苏'],
            ['c'=>'浙江省','j'=>'浙'],
            ['c'=>'安徽省','j'=>'皖'],
            ['c'=>'福建省','j'=>'闽'],
            ['c'=>'江西省','j'=>'赣'],
            ['c'=>'山东省','j'=>'鲁'],
            ['c'=>'福建省','j'=>'闽'],
            ['c'=>'河南省','j'=>'豫'],
            ['c'=>'湖北省','j'=>'鄂'],
            ['c'=>'湖南省','j'=>'湘'],
            ['c'=>'广东省','j'=>'粤'],
            ['c'=>'广西壮族自治区','j'=>'桂'],
            ['c'=>'海南省','j'=>'琼'],
            ['c'=>'四川省','j'=>'川'],
            ['c'=>'贵州省','j'=>'贵'],
            ['c'=>'云南省','j'=>'云'],
            ['c'=>'重庆市','j'=>'渝'],
            ['c'=>'西藏自治区','j'=>'藏'],
            ['c'=>'陕西省','j'=>'陕'],
            ['c'=>'甘肃省','j'=>'甘'],
            ['c'=>'青海省','j'=>'青'],
            ['c'=>'宁夏回族自治区','j'=>'宁'],
            ['c'=>'新疆维吾尔自治区','j'=>'新'],
            ['c'=>'香港特别行政区','j'=>'港'],
            ['c'=>'澳门特别行政区','j'=>'澳'],
            ['c'=>'台湾省','j'=>'台'],
        ];
        return json($data);
    }
}