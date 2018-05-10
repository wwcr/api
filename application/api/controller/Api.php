<?php
/**
 * Created by PhpStorm.
 * User: iyahe@qq.com (天明)
 * Date: 2017/7/26
 * Time: 14:21
 */
namespace app\index\controller;

use think\Db;
use tool\curl\Curl;
use tool\wechat\Wechat;
/** api 数据交互中心
 * Class Api
 * @package app\index\controller
 */
class Api extends Action
{   
    private $appkey = '743a9232f73942a9fa5a6645c3f1877e';
    private $wz_appkey = 'f99efbffea18d69350538e27f02a4a09'; //申请的全国违章查询APPKEY
    public function index()
    {
        p(4);die;
        $mod = input('mod','Index');
        $act = input('act','test');
        if($mod === 'Index') {
            $module = new Index();
        }else if ($mod === 'Admin') {
            $module = new Admin();
        }else if ($mod === 'Api'){
            $module = new ApiCenter();
        }else{
            $module = new Toolure();
        }
        return $module->$act();
    }

    public function test()
    {    
      // $curl = new Curl();
      // $curl->test();
      // echo  11111;
        // $pay = new Wechat();
        // // var_dump($pay);
        // $pay->test;
      echo 111;
    }

    public function car_pinggu()
    {   
      header("Access-Control-Allow-Origin:*");
        header("Access-Control-Allow-Headers:Origin, X-Requested-With, Content-Type, Accept,USER_ID,TOKEN");
        header("Access-Control-Allow-Methods:HEAD, GET, POST, DELETE, PUT, OPTIONS");
        // $curl = new Curl();
        $pro_result = $this->province();
        $brand_result = $this->brand();
        $this->assign('province',$pro_result);
        $this->assign('brand',$brand_result);
        return $this->fetch('index');
    }
    public function province(){//获取车辆评估省份
        $curl = new Curl();
//如果需要设置允许所有域名发起的跨域请求，可以使用通配符 *  
header("Access-Control-Allow-Origin:*");
header("Access-Control-Allow-Headers:Origin, X-Requested-With, Content-Type, Accept,USER_ID,TOKEN");
header("Access-Control-Allow-Methods:HEAD, GET, POST, DELETE, PUT, OPTIONS"); 
        $url = "http://v.juhe.cn/usedcar/province";
        $params = array(
      "key" => $this->appkey,//应用APPKEY(应用详细页查询)
      "dtype" => "",//返回数据的格式,xml或json，默认json
      "method" => "",//固定值：getAllCity
        );
        $paramstring = http_build_query($params);
        $content = $curl->juhecurl($url,$paramstring);
        $result = json_decode($content,true);
        // var_dump($result);
        if($result){
            if($result['error_code']=='0'){
                echo json_encode($result);
            }else{
                echo $result['error_code'].":".$result['reason'];
            }
        }else{
            echo "请求失败";
        }
    }
    
    public function city(){
        header("Access-Control-Allow-Origin:*");
        header("Access-Control-Allow-Headers:Origin, X-Requested-With, Content-Type, Accept,USER_ID,TOKEN");
        header("Access-Control-Allow-Methods:HEAD, GET, POST, DELETE, PUT, OPTIONS");
        $curl = new Curl();
        $pid = input('post.id');
        $url = "http://v.juhe.cn/usedcar/city";
        $params = array(
      "key" => $this->appkey,//应用APPKEY(应用详细页查询)
      "dtype" => "",//返回数据的格式,xml或json，默认json
      "province" => $pid,//省份id(从“二手车价值评估/省份列表”接口获取)
        );
        $paramstring = http_build_query($params);
        $content = $curl->juhecurl($url,$paramstring);
        $result = json_decode($content,true);
        if($result){
            if($result['error_code']=='0'){
                echo json_encode($result['result']);
            }else{
                echo $result['error_code'].":".$result['reason'];
            }
        }else{
            echo "请求失败";
        }
    }
    public function brand($type = 1){
        $curl = new Curl();
       header("Access-Control-Allow-Origin:*");
        header("Access-Control-Allow-Headers:Origin, X-Requested-With, Content-Type, Accept,USER_ID,TOKEN");
        header("Access-Control-Allow-Methods:HEAD, GET, POST, DELETE, PUT, OPTIONS");   
        $car_type = $type == 1 ? 'passenger' : 'commercial';
        $url = "http://v.juhe.cn/usedcar/brand";
        $params = array(
      "key" => $this->appkey,//应用APPKEY(应用详细页查询)
      "dtype" => "",//返回数据的格式,xml或json，默认json
      "vehicle" =>$car_type,//省份id(从“二手车价值评估/省份列表”接口获取)
        );
        $paramstring = http_build_query($params);
        $content = $curl->juhecurl($url,$paramstring);
        $result = json_decode($content,true);
        if($result){
            if($result['error_code']=='0'){
                // var_dump($result['result']['A']);
                // return $result;
                $brand_result = array();
                foreach ($result['result'] as $key => $value) {

                    foreach ($value as $key1 => $value1) {
                        $brand_result[] = $value1;
                    }
                }
                echo json_encode($brand_result);
            }else{
                echo $result['error_code'].":".$result['reason'];
            }
        }else{
            echo "请求失败";
        }
    }
     public function series(){//车系
        $curl = new Curl();
        header("Access-Control-Allow-Origin:*");
        header("Access-Control-Allow-Headers:Origin, X-Requested-With, Content-Type, Accept,USER_ID,TOKEN");
        header("Access-Control-Allow-Methods:HEAD, GET, POST, DELETE, PUT, OPTIONS");
        // $bid = 2000417;
        $bid = input('post.id');
        // echo $bid;
        $url = "http://v.juhe.cn/usedcar/series";
        $params = array(
      "key" => $this->appkey,//应用APPKEY(应用详细页查询)
      "dtype" => "",//返回数据的格式,xml或json，默认json
      "method" => "",//固定值：getCarSeriesList
      "brand" => $bid,//品牌标识，可以通过车三百品牌数据接口拿回所有的品牌信息，从而提取品牌标识。
        );
        $paramstring = http_build_query($params);
        $content = $curl->juhecurl($url,$paramstring);
        $result = json_decode($content,true);
        if($result){
            if($result['error_code']=='0'){
                // echo json_encode($result['result']);
                $series_result = array();
                foreach ($result['result'] as $key => $value) {
                    
                    foreach ($value as $key1 => $value1) {
                        // var_dump($value1['xilie']);
                        foreach ($value1['xilie'] as $key2 => $value2) {
                            $series_result[] = $value2;
                        }
                    }
                }
                echo json_encode($series_result);
            }else{
                echo $result['error_code'].":".$result['reason'];
            }
        }else{
            echo "请求失败";
        }
     }
     public function car_list(){//车型列表
        $curl = new Curl();
        header("Access-Control-Allow-Origin:*");
        header("Access-Control-Allow-Headers:Origin, X-Requested-With, Content-Type, Accept,USER_ID,TOKEN");
        header("Access-Control-Allow-Methods:HEAD, GET, POST, DELETE, PUT, OPTIONS");
        $url = "http://v.juhe.cn/usedcar/car";
        // $sid = 20000227;
        $sid = input('post.id');
         $params = array(
          "key" => $this->appkey,//应用APPKEY(应用详细页查询)
              "dtype" => "",//返回数据的格式,xml或json，默认json
              "method" => "",//固定值：getCarModelList
              "series" => $sid,//车系标识，可以通过车三百车系数据接口拿回车系信息，从而提前车系标识。
        );
        $paramstring = http_build_query($params);
        $content = $curl->juhecurl($url,$paramstring);
        $result = json_decode($content,true);
        if($result){
            if($result['error_code']=='0'){
                // print_r($result);
                $car_result = array();
               foreach ($result['result'] as $key => $value) {
                   foreach ($value as $key1=> $value1) {
                      foreach($value1['chexing_list'] as $key2 => $value2){
                        $car_result[] = $value2;
                      }
                   }
               }
               echo json_encode($car_result);
            }else{
                echo $result['error_code'].":".$result['reason'];
            }
        }else{
            echo "请求失败";
        }
     }
     public function car_year(){//车型列表
        header("Access-Control-Allow-Origin:*");
        header("Access-Control-Allow-Headers:Origin, X-Requested-With, Content-Type, Accept,USER_ID,TOKEN");
        header("Access-Control-Allow-Methods:HEAD, GET, POST, DELETE, PUT, OPTIONS");
        $curl = new Curl();
        $url = "http://v.juhe.cn/usedcar/year";
        $cid = input('post.id');
        // $cid = 1090486;
         $params = array(
          "key" => $this->appkey,//应用APPKEY(应用详细页查询)
              "dtype" => "",//返回数据的格式,xml或json，默认json
              "method" => "",//固定值：getCarModelList
              "car" => $cid,//    车型id(从“二手车价值评估/车型列表”接口获取)
        );
        $paramstring = http_build_query($params);
        $content = $curl->juhecurl($url,$paramstring);
        $result = json_decode($content,true);
        if($result){
            if($result['error_code']=='0'){
                echo json_encode($result);
            }else{
                echo $result['error_code'].":".$result['reason'];
            }
        }else{
            echo "请求失败";
        }
     }
     public function assess(){//车辆估值
        header("Access-Control-Allow-Origin:*");
        header("Access-Control-Allow-Headers:Origin, X-Requested-With, Content-Type, Accept,USER_ID,TOKEN");
        header("Access-Control-Allow-Methods:HEAD, GET, POST, DELETE, PUT, OPTIONS");
        $curl = new Curl();
        $url = "http://v.juhe.cn/usedcar/assess";
        $city = input('post.city');
         $params = array(
          "key" => $this->appkey,//应用APPKEY(应用详细页查询)
          "dtype" => "",//返回数据的格式,xml或json，默认jsont
          "carstatus" => 2,// 车况
          "purpose" =>1 ,// 车辆用途: 1自用 2公务商用 3营运
          "city" => isset($city) ? $city : 1,// 城市标识（从“二手车价值评估/城市列表”接口获取)
          "province" => input('post.province'),//省份标识（从“二手车价值评估/省份列表”接口获取)
          "car" => input('post.car'),//    车型id(从“二手车价值评估/车型列表”接口获取)
          "useddate" => input('post.useddate'),//  待估车辆的启用年份（格式：yyyy）
          "useddateMonth" => input('post.useddateMonth'),//待估车辆的启用月份（格式：mm）
          "mileage" => input('post.mileage'),//待估车辆的公里数，单位万公里
          "price" => input('post.price'),//待估车辆在购买价(单位万元)
        );
        $paramstring = http_build_query($params);
        $content = $curl->juhecurl($url,$paramstring);
        $result = json_decode($content,true);
        if($result){
            if($result['error_code']=='0'){
                echo json_encode($result['result']);
            }else{
                echo $result['error_code'].":".$result['reason'];
            }
        }else{
            echo "请求失败";
        }
     }
    //  public function wz_city(){//违章城市
    //  	$curl = new Curl();
    //     $url = "http://v.juhe.cn/wz/citys";
    //     $params = 'key='.$this->wz_appkey.'&format=2';
    //     $content = $curl->juhecurl($url,$params);
    //     $result = json_decode($content,true);
    //     if($result){
    //         if($result['error_code']=='0'){
    //             // echo json_encode($result['result']);
    //             // var_dump($result);
    //             $prolist = array();
    //             foreach ($result['result'] as $key => $value) {
    //             	// var_dump($value);
    //             	$data = ['pro_code' => $value['province_code'],
    //             			 'pro' => $value['province']];
    //             	$pid = Db::name('prolist')->insertGetId($data);
    //             	foreach ($value['citys'] as $key1 => $value1) {
    //             	$city_data = [
    //             	'city_name' => $value1['city_name'],
    //             	'city_code' => $value1['city_code'],
    //             	'engine' => $value1['engine'],
    //             	'engine_no' => $value1['engineno'],
    //             	'class' => $value1['class'],
    //             	'class_no' => $value1['classno'],
    //             	'pid' => $pid
    //             			 ];
    //             	Db::name('wzcity')->insert($city_data);
    //             	}
    //             }
    //         }else{
    //             echo $result['error_code'].":".$result['reason'];
    //         }
    //     }else{
    //         echo "请求失败";
    //     }
    // }
 		public function newwz_city(){//违章城市
        header("Access-Control-Allow-Origin:*");
        header("Access-Control-Allow-Headers:Origin, X-Requested-With, Content-Type, Accept,USER_ID,TOKEN");
        header("Access-Control-Allow-Methods:HEAD, GET, POST, DELETE, PUT, OPTIONS");
     	  $curl = new Curl();
        $url = "http://v.juhe.cn/wz/citys";
        $params = 'key='.$this->wz_appkey.'&format=2';
        $content = $curl->juhecurl($url,$params);
        $result = json_decode($content,true);
        if($result){
            if($result['error_code']=='0'){
            	// var_dump($result['result']);
                foreach ($result['result'] as $key => $value) {
                	$data = [
                			'pro_code' => $value['province_code'],
                			'pro' => $value['province'],
                			'citys' => json_encode($value['citys'])
                			 ];
                	Db::name('wzsearch')->insert($data);
                }
            }else{
                echo $result['error_code'].":".$result['reason'];
            }
        }else{
            echo "请求失败";
        }
   	 }
    public function getwz_city(){//违章城市
      header("Access-Control-Allow-Origin:*");
        header("Access-Control-Allow-Headers:Origin, X-Requested-With, Content-Type, Accept,USER_ID,TOKEN");
        header("Access-Control-Allow-Methods:HEAD, GET, POST, DELETE, PUT, OPTIONS");
     	$res = Db::name('wzsearch')->select();
     	echo json_encode($res);
   	 }
    public function update_wz_city(){//违章城市更新 3小时一次
     	  
        $curl = new Curl();
        $url = "http://v.juhe.cn/wz/citys";
        $params = 'key='.$this->wz_appkey.'&format=2';
        $content = $curl->juhecurl($url,$params);
        $result = json_decode($content,true);
        if($result){
            if($result['error_code']=='0'){
                foreach ($result['result'] as $key => $value) {
                	$data = [
                			'pro_code' => $value['province_code'],
                			'pro' => $value['province'],
                			'citys' => json_encode($value['citys'])
                			 ];
                	Db::name('wzsearch')->where('pro_code', $value['province_code'])->update($data);
                }
            }else{
                echo $result['error_code'].":".$result['reason'];
            }
        }else{
            echo "请求失败";
        }
   	 }

     public function peccancy(){//违章查询
        header("Access-Control-Allow-Origin:*");
        header("Access-Control-Allow-Headers:Origin, X-Requested-With, Content-Type, Accept,USER_ID,TOKEN");
        header("Access-Control-Allow-Methods:HEAD, GET, POST, DELETE, PUT, OPTIONS");
         $curl = new Curl();
        $url = "http://v.juhe.cn/wz/query";
        $city = input('post.city');
        $hphm = input('post.hphm');
        $engineno = input('post.engineno');
        $classno = input('post.classno');
        // $result = array();
        // $result['city'] = $city;
        // $result['hphm'] = $hphm;
        // $result['engineno'] = $engineno;
        // $result['classno'] = $classno;
        // echo json_encode($result);
         $params = array(
          "key" => $this->wz_appkey,//应用APPKEY(应用详细页查询)
          "dtype" => "",//返回数据的格式,xml或json，默认jsont
          "city" => $city,// 城市标识（从“二手车价值评估/城市列表”接口获取)
          // "hphm" => input('post.hphm'),//车牌号
          "hphm" => $hphm,//车牌号
          // "engineno" => input('post.engineno'),//  发动机号
          "engineno" => $engineno,//  发动机号
          "classno" => $classno,//车架号
        );
        $paramstring = http_build_query($params);
        $content = $curl->juhecurl($url,$paramstring);
        $result = json_decode($content,true);
        if($result){
            if($result['error_code']=='0'){
                echo json_encode($result);
                // var_dump($result['result']['lists']);
            }else{
                echo $result['error_code'].":".$result['reason'];
            }
        }else{
            echo "请求失败";
        }
     }
     public function get_finded_pic(){//已找到车辆的现场图片接口
        header("Access-Control-Allow-Origin:*");
        header("Access-Control-Allow-Headers:Origin, X-Requested-With, Content-Type, Accept,USER_ID,TOKEN");
        header("Access-Control-Allow-Methods:HEAD, GET, POST, DELETE, PUT, OPTIONS");
     	$car_no = input('post.id');//接收车牌
      $res = Db::name('cardata')->where('car_card', $car_no)->find();
      // echo $res;
     	// // var_dump($res);
     	echo json_encode($res);
    }
    public function get_userinfo(){//已找到车辆的现场图片接口
      header("Access-Control-Allow-Origin:*");
        header("Access-Control-Allow-Headers:Origin, X-Requested-With, Content-Type, Accept,USER_ID,TOKEN");
        header("Access-Control-Allow-Methods:HEAD, GET, POST, DELETE, PUT, OPTIONS");
      $uid = input('post.uid');//接收车牌
      $res = Db::name('user')->where('user_id', $uid)->find();
      // echo $res;
      // // var_dump($res);
      echo json_encode($res);
    }
}
