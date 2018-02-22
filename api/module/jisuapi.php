<?php
/**
 * Created by PhpStorm.
 * User: iyahe@qq.com (天明)
 * Date: 2017/11/13
 * Time: 13:26
 */
namespace app\api\module;
use think\Cache;
use think\Model;

/** 急速数据api接口
 * Class jisuap
 * @package app\api\module
 */
class jisuapi extends Model
{
    protected $key = 'e7b9b843bdaa452f';
    protected $cacheTime = 60*60*24;
    protected $api='http://api.jisuapi.com/';
    public function _curl($name,$data=[]){
        $query = http_build_query($data);
        if($query) $query = '&'.$query;
        $url = $this->api.$name."?appkey=".$this->key.$query;
        $name = md5($url);
        if($cacheData = Cache::get($name)) {
            return json_decode($cacheData,true);
        }else{
            $result = https_request($url);
            $jsonarr = json_decode($result, true);
            if($jsonarr['status'] == 0) {
                Cache::set($name,json_encode($jsonarr['result']),$this->cacheTime);
                return $jsonarr['result'];
            }else{
                return 'fail';
            }
        }
    }
    //获取所有品牌
    public function CarBrand(){
        $data = $this->_curl('car/brand');
        $list = [];
        foreach(range('A','Z') as $v){
            foreach($data as $key=>$value){
                if( $v == strtoupper($value['initial']))
                {
                    $list[$v][] = $value;
                }
            }
        }
        return json($list);
    }
    //根据品牌获取车型
    public function CarType(){
        $parentid = input('parentid',1);
        $data = [];
        if($parentid)
        {
            $data = $this->_curl('car/type',['parentid'=>$parentid]);
        }
        return json($data);
    }
    //根据车型获取车型
    public function CarInfo(){
        $parentid = input('parentid');
        $data = [];
        if($parentid){
            $data = $this->_curl('car/car',['parentid'=>$parentid]);
        }
        return json($data);
    }
    //根据ID获取车型详情
    public function CarDetail(){
        $carid = input('carid');
        $data = [];
        if($carid){
            $data = $this->_curl('car/detail',['carid'=>'']);
        }
        return json($data);
    }
    public function CarSearch(){
        $keyword = input('keyword');
        $data = [];
        if($keyword){
            $data = $this->_curl('car/search',['keyword'=>'']);
        }
        return json($data);
    }
}