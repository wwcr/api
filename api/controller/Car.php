<?php
/**
 * Created by PhpStorm.
 * User: iyahe@qq.com (天明)
 * Date: 2017/11/14
 * Time: 14:06
 */
namespace app\api\controller;
use app\api\module\jisuapi;
use app\api\module\test;
use app\index\controller\Action;
use app\index\module\model;


class Car extends Action
{
    public function __call($name, $arguments)
    {
        // TODO: Implement __call() method.
    }
    public function CarBrand(){
        $model = new jisuapi();
        return $model->CarBrand();
    }
    public function CarType(){
        $model = new jisuapi();
        return $model->CarType();
    }
}