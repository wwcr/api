<?php
/**
 * Created by PhpStorm.
 * User: iyahe@qq.com (天明)
 * Date: 2017/11/15
 * Time: 12:29
 */
namespace app\api\controller;
use app\index\controller\Action;
use think\Db;

/** city tab
 * Class City
 * @package app\api\controller
 */
class City extends Action
{
    public function index(){}
    public function getlist(){
        $list =  Db::name('areas')->where(['rank'=>1])->order('parent_id DESC')->select();
        $hot = Db::name('areas')->where(['rank'=>1,'is_hot'=>1])->order('parent_id DESC')->select();
        return json(['list'=>$this->getAZ($list),'hot'=>$hot]);
    }
    public function getAZ($list){
        $listLast = [];
        foreach(range('A','Z') as $v){
            foreach($list as $key=>$value){
                if( $v == strtoupper($value['letter']))
                {
                    $listLast[$v][] = $value;
                }
            }
        }
        return $listLast;
    }
    public function search(){
        $keyword = input('keyword','');
        if($keyword) {
            $list =  Db::name('areas')->where('name','like','%'.$keyword.'%')->select();
            return json(['list'=>$this->getAZ($list)]);
        }
    }
}