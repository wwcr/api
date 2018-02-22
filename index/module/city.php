<?php
namespace app\index\module;

use think\Controller;
use think\Db;
use think\Model;

/** 地区操作模型
 * Class city
 * @package app\index\module
 */
class city extends model
{
    public function selectProvinces()
    {
        $provinces = Db::name('city')->where('pid=0')->select();

        return $provinces;
    }

    public function selectCity($province_id)
    {
        $citys = Db::name('city')->where('pid',$province_id)->select();

        return $citys;
    }

    public function getIDByProvinceName($name)
    {
        $provinceId = Db::name('city')->where('name',$name)->field('id')->find();

        return $provinceId;
    }

}
