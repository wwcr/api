<?php
namespace app\index\module;
use think\Controller;
use think\Model;
use think\Db;

/** 设备模型
 * Class user
 * @package app\index\module
 */
class machine extends model {

    protected $autoWriteTimestamp = true;

    protected $insert = ['status' => 1];

    protected $resultSetType = 'collection';

    public $validate =[
        ['account', 'require', '账号不能为空'],
        ['password', 'require', '密码不能为空']
    ];


}
