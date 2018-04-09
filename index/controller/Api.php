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
use think\Cache;
/** api 数据交互中心
 * Class Api
 * @package app\index\controller
 */
class Api extends Action
{   
    private $appkey = '743a9232f73942a9fa5a6645c3f1877e';
    private $wz_appkey = 'f99efbffea18d69350538e27f02a4a09'; //申请的全国违章查询APPKEY
    public function _initialize(){
        header("Access-Control-Allow-Origin:*");
        header("Access-Control-Allow-Headers:Origin, X-Requested-With, Content-Type, Accept,USER_ID,TOKEN");
        header("Access-Control-Allow-Methods:HEAD, GET, POST, DELETE, PUT, OPTIONS");
          $options = [
            // 缓存类型为File
            'type' => 'redis',
            'prefix' => ''
        ];
        $this->redis = Cache::connect($options);//连接redi

        $deposit = Db::name('user')->where(['user_id' => $this->uid])->value('deposit');
    }
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

    public function examine()
    {    
        $data['company_name'] = input('post.company_name');
        $data['person'] = input('post.person');
        $data['tel'] = input('post.tel');
        $code= input('post.code');
         if($this->redis->get($data['tel']) != $code){
            echo json_encode('MSGFAIL');
            return;
        }
        $data['city'] = input('post.city');
        $data['type'] = input('post.type');
        $mo = Db::name('examine')->where(['tel'=>$data['tel']])->find();//查询有没有重复提交
        if(empty($mo)){
           $res = Db::name('examine')->insert($data);
             if($res){
                echo json_encode('SUCCESS');
             }else{
                echo json_encode('FAIL');
             }
        }else{
            echo json_encode('RESUBMIT');
        }

         
    }
    public function get_examine()
    {    
        $data['tel'] = input('post.tel');
         $res = Db::name('examine')->where('tel',$data['tel'])->select();
         if($res){
            echo json_encode($res);
         }else{
            echo json_encode('FAIL');
         }
    }
    public function car_pinggu()
    {   
        // header("Access-Control-Allow-Origin:*");
        // header("Access-Control-Allow-Headers:Origin, X-Requested-With, Content-Type, Accept,USER_ID,TOKEN");
        // header("Access-Control-Allow-Methods:HEAD, GET, POST, DELETE, PUT, OPTIONS");
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
        // header("Access-Control-Allow-Origin:*");
        // header("Access-Control-Allow-Headers:Origin, X-Requested-With, Content-Type, Accept,USER_ID,TOKEN");
        // header("Access-Control-Allow-Methods:HEAD, GET, POST, DELETE, PUT, OPTIONS"); 
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
     public function provincetest(){//获取车辆评估省份
        $curl = new Curl();
//如果需要设置允许所有域名发起的跨域请求，可以使用通配符 *  
        // header("Access-Control-Allow-Origin:*");
        // header("Access-Control-Allow-Headers:Origin, X-Requested-With, Content-Type, Accept,USER_ID,TOKEN");
        // header("Access-Control-Allow-Methods:HEAD, GET, POST, DELETE, PUT, OPTIONS"); 
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
                // echo json_encode($result);
                // var_dump($result['result']);
                $data = array();
                foreach ($result['result'] as $key => $value) {
                  // var_dump($key);
                     $data[$value['pin']][$key] = $value;
                     // $data[$value['pin']][]['proID'] = $value['proID'];
                     // $data[$value['pin']][]['proID'] = $value['proID'];
                }
                // var_dump($data);
                $data1 = array();
                foreach ($data as $key1 => $value1) {
                  // echo $key1;
                  // var_dump($value1);
                  foreach ($value1 as $key2 => $value2) {
                    # code...
                    $data1[$key1][] = $value2;
                  }
                  
                }
                ksort($data1);
                // echo '<pre>';
                // var_dump($data1);
                echo json_encode($data1);
            }else{
                echo $result['error_code'].":".$result['reason'];
            }
        }else{
            echo "请求失败";
        }
    }
    public function city(){
        // header("Access-Control-Allow-Origin:*");
        // header("Access-Control-Allow-Headers:Origin, X-Requested-With, Content-Type, Accept,USER_ID,TOKEN");
        // header("Access-Control-Allow-Methods:HEAD, GET, POST, DELETE, PUT, OPTIONS");
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
        // header("Access-Control-Allow-Origin:*");
        // header("Access-Control-Allow-Headers:Origin, X-Requested-With, Content-Type, Accept,USER_ID,TOKEN");
        // header("Access-Control-Allow-Methods:HEAD, GET, POST, DELETE, PUT, OPTIONS");   
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
     public function brandtest($type = 1){
      // header("Access-Control-Allow-Origin:*");
      //   header("Access-Control-Allow-Headers:Origin, X-Requested-With, Content-Type, Accept,USER_ID,TOKEN");
      //   header("Access-Control-Allow-Methods:HEAD, GET, POST, DELETE, PUT, OPTIONS");
        $curl = new Curl();
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
                // var_dump($result);
                echo json_encode($result['result']);
                // $brand_result = array();
                // foreach ($result['result'] as $key => $value) {

                //     foreach ($value as $key1 => $value1) {
                //         $brand_result[] = $value1;
                //     }
                // }
                //  var_dump($brand_result);
            }else{
                echo $result['error_code'].":".$result['reason'];
            }
        }else{
            echo "请求失败";
        }
    }
     public function series(){//车系
        $curl = new Curl();
        // header("Access-Control-Allow-Origin:*");
        // header("Access-Control-Allow-Headers:Origin, X-Requested-With, Content-Type, Accept,USER_ID,TOKEN");
        // header("Access-Control-Allow-Methods:HEAD, GET, POST, DELETE, PUT, OPTIONS");
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
        // header("Access-Control-Allow-Origin:*");
        // header("Access-Control-Allow-Headers:Origin, X-Requested-With, Content-Type, Accept,USER_ID,TOKEN");
        // header("Access-Control-Allow-Methods:HEAD, GET, POST, DELETE, PUT, OPTIONS");
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
        // header("Access-Control-Allow-Origin:*");
        // header("Access-Control-Allow-Headers:Origin, X-Requested-With, Content-Type, Accept,USER_ID,TOKEN");
        // header("Access-Control-Allow-Methods:HEAD, GET, POST, DELETE, PUT, OPTIONS");
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
        // header("Access-Control-Allow-Origin:*");
        // header("Access-Control-Allow-Headers:Origin, X-Requested-With, Content-Type, Accept,USER_ID,TOKEN");
        // header("Access-Control-Allow-Methods:HEAD, GET, POST, DELETE, PUT, OPTIONS");
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
        // header("Access-Control-Allow-Origin:*");
        // header("Access-Control-Allow-Headers:Origin, X-Requested-With, Content-Type, Accept,USER_ID,TOKEN");
        // header("Access-Control-Allow-Methods:HEAD, GET, POST, DELETE, PUT, OPTIONS");
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
      // header("Access-Control-Allow-Origin:*");
      //   header("Access-Control-Allow-Headers:Origin, X-Requested-With, Content-Type, Accept,USER_ID,TOKEN");
      //   header("Access-Control-Allow-Methods:HEAD, GET, POST, DELETE, PUT, OPTIONS");
     	$res = Db::name('wzsearch')->order('pro_code')->select();
        $data = [];
        foreach ($res as $key => $value) {
            $data[$value['code']][] = $value;
        }
     	echo json_encode($data);
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
                   
                     // echo substr($value['province_code'],0,1);
                	 $data = [
                            'code' => substr($value['province_code'],0,1),
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
        // header("Access-Control-Allow-Origin:*");
        // header("Access-Control-Allow-Headers:Origin, X-Requested-With, Content-Type, Accept,USER_ID,TOKEN");
        // header("Access-Control-Allow-Methods:HEAD, GET, POST, DELETE, PUT, OPTIONS");
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
     public function change_gps($address){
     	$curl = new Curl();
        $url = "http://restapi.amap.com/v3/geocode/geo?";
        $url = $url."key=9ce89cf409e2844be189fd5e37907c9f&output=JSON&address=".$address;
        $res = $curl->gps_change($url);
        $result = json_decode($res,true);
        return $result;
     }
     public function get_finded_pic(){//已找到车辆的现场图片接口
        // header("Access-Control-Allow-Origin:*");
        // header("Access-Control-Allow-Headers:Origin, X-Requested-With, Content-Type, Accept,USER_ID,TOKEN");
        // header("Access-Control-Allow-Methods:HEAD, GET, POST, DELETE, PUT, OPTIONS");
     	$car_no = input('post.id');//接收车牌
      	$res = Db::name('cardata')->where('car_card', $car_no)->find();
      	// echo '<pre>';
      	$gps = $this->change_gps($res['car_location']);//通过高德的接口把位置名转换为坐标
      	$gps = explode(',',$gps['geocodes'][0]['location']);
      	// var_dump($gps);
      	$res['gpsdata'] = $gps;
     	// var_dump($gps['geocodes'][0]['location']);
     	echo json_encode($res);
    }
    public function get_userinfo(){//已找到车辆的现场图片接口
      // header("Access-Control-Allow-Origin:*");
      //   header("Access-Control-Allow-Headers:Origin, X-Requested-With, Content-Type, Accept,USER_ID,TOKEN");
      //   header("Access-Control-Allow-Methods:HEAD, GET, POST, DELETE, PUT, OPTIONS");
      $uid = input('post.uid');//接收车牌
      $res = Db::name('user')->where('user_id', $uid)->find();
      // echo $res;
      // // var_dump($res);
      echo json_encode($res);
    }
    public function car_loan(){//车贷计算
        $money = input('post.money');//本金
        $li = input('post.li')/12;//年利率转为月
        $month = input('post.month');
        $type = input('post.type');
        if($type == 1){//等额本金
        $each_money = $money/$month;//每月本金
        $n;//第几个月
        $data=[];
        for($n =1;$n<=$month;$n++){
            $each_all_money = $each_money+($money-($n-1)*$each_money)*$li;//每月月供
            $each_month_li = ($money-($n-1)*$each_money)*$li;
            $all_li += $each_month_li;//总利息
            $all_money += $each_all_money;//还款总额
                
            // echo '第'.$n.'月月供'.$each_all_money.'<br>';
            // echo '第'.$n.'月利息'.$each_month_li.'<br>';
                $data_month[$n] = $each_month_li;
        }    
            $data['first'] = $each_money+$money*$li;;
            $data['all_money'] = $all_money;
            $data['all_li'] = $all_li;
            $data['each_reduce'] = $data_month[1]-$data_month[2];
            echo json_encode($data);
        }else{//等额本息
            $data['each_money'] = $money*$li*pow((1+$li),$month)/(pow((1+$li),$month)-1);
            $data['all_li'] = $data['each_money'] * $month -$money;
            $data['all_money'] = $data['each_money'] * $month;
            echo json_encode($data);
        }
    }
    public function content_up(){
        // header("Access-Control-Allow-Origin:*");
        // header("Access-Control-Allow-Headers:Origin, X-Requested-With, Content-Type, Accept,USER_ID,TOKEN");
        // header("Access-Control-Allow-Methods:HEAD, GET, POST, DELETE, PUT, OPTIONS");
        $data['title'] = input('post.title');
        $data['content'] = input('post.content');
        $data['uid'] = input('post.uid');
        $data['release_time'] = input('post.publishTime');//时间
        // var_dump($content);
        // $content = '阿宾阿达安局办公楼的打';
        
        // $res = trie_filter_search($tire, $content);
        // var_dump($tire);
        // print_r(substr($content, $res[0], $res[1])); //傻逼
        // print_r(str_replace($content,substr($content, $res[0], $res[1]), '****'));
        //在文本中查找所有的脏字
        
        // ------------上传图片
    //     $fileName = $_SERVER['DOCUMENT_ROOT'].'/wwcr';
    //     // echo $fileName;
    //     $files = request()->file('imgFiles');
		  //   foreach($files as $file){
		  // //       // 移动到框架应用根目录/public/uploads/ 目录下
		  //       $info = $file->rule('uniqid')->move($fileName . DS . 'public' . DS . 'upload');
		  //       if($info){
		  //           // 成功上传后 获取上传信息
		  //           // 输出 jpg
		  //           // echo $info->getExtension(); 
		  //           // 输出 42a79759f284b767dfcb2a0197904287.jpg
		  //           $data['pic'] =$data['pic'].'^'.$info->getFilename();
		            
		  //           // echo $info->getFilename(); 
		  //       }else{
		  //           // 上传失败获取错误信息
		  //           echo $file->getError();
		  //       }    
    // }
	    $res = Db::name('chatinfo')->insert($data);
	    if($res){
	    	self::AjaxReturn('','上传成功',1);
	    }else{
	    	self::AjaxReturn('','提交失败',0);
	    }
    }
    public function get_content_readnum(){//统计文章浏览次数
        $id = input('post.id');//接收文章id
        $data = Db::name('chatinfo')->where('id',$id)->update(['read_num' => ['exp','read_num+1']]);
    }
    public function get_all_contentlist(){
        $limit = input('limit');
         $allcount = Db::name('chatinfo')//总条数
           ->alias('c')
            ->join('user u','np_chatinfo.uid=np_user.user_id','left')
            ->order('c.release_time desc')
            ->field('u.user_mobile,u.user_header,c.*')
            ->select();
        $count = count($allcount);//总条数
    	$data = Db::name('chatinfo')
    		->alias('c')
            ->join('user u','np_chatinfo.uid=np_user.user_id','left')
            ->order('c.release_time desc')
            ->field('u.user_mobile,u.user_header,c.*')
            ->limit($limit)
            ->select();
            foreach ($data as $key => $value) {
               $data[$key]['content'] = $this->get_dirty($data[$key]['content']);
            }
         self::AjaxReturn($data,$count);
    }
    public function get_single_contentlist(){
        $limit = input('limit');
    	$uid = input('post.uid');
        $allcount = Db::name('chatinfo')//总条数
           ->alias('c')
            ->join('user u','np_chatinfo.uid=np_user.user_id','left')
            ->order('c.release_time desc')
            ->field('u.user_mobile,u.user_header,c.*')
            ->where('uid',$uid)
            ->select();
        $count = count($allcount);//总条数
    	$data = Db::name('chatinfo')
    		->alias('c')
            ->join('user u','np_chatinfo.uid=np_user.user_id','left')
            ->order('c.release_time desc')
            ->field('u.user_mobile,u.user_header,c.*')
            ->where('uid',$uid)
            ->limit($limit)
            ->select();
             foreach ($data as $key => $value) {
               $data[$key]['content'] = $this->get_dirty($data[$key]['content']);
            }
         self::AjaxReturn($data,$count);
    }
    public function get_dirty($content){//获取脏字
        $dic = $_SERVER['DOCUMENT_ROOT'].'/dirty_words.dic';
        $tire = trie_filter_load($dic);
        //该函数的返回值是一个数组，第一个值为脏字出现的位置，第二个值为脏字的长度，拿到返回值后，可以用substr等函数获取脏字
        $res = trie_filter_search_all($tire, $content);
        foreach ($res as $key => $value) {
            $content = str_replace(substr($content, $res[$key][0], $res[$key][1]), '****',$content);
        }
        return $content;//把替换的脏字返回
    }
    public function response(){
        echo json_encode('success');
    }
}
