<?php
/**
 * Created by PhpStorm.
 * User: iyahe@qq.com (天明)
 * Date: 2017/10/26/026
 * Time: 22:28
 */
namespace app\api\controller;
use app\index\controller\Action;
use app\index\module;
use think\Db;
use app\api\validate;

class Map extends Action
{
    public function index(){}
    //百度地图转换
    public function changeAddress($address){
        $url = 'http://api.map.baidu.com/geocoder/v2/?address='.$address.'&output=json&ak=PSKUhq4yWkMPXo8G83D6mYlS';
        $data = https_request($url);
        $address = json_decode($data,true);
        if(is_array($address)){
            return $address['result'];
        }
        return false;
    }
    public function info(){
        $id = input('id');
        $data = Db::name('findcard')
            ->join('np_cardata','np_cardata.car_id=np_findcard.card_cardata')
            ->find();
        if(!empty($data)){
            $data['location'] = $this->changeAddress($data['car_location']);
            self::AjaxReturn($data);
        }else{
            self::AjaxReturnError('获取失败');
        }
    }
}