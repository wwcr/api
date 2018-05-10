<?php
/**
 * Created by PhpStorm.
 * User: iyahe@qq.com (天明)
 * Date: 2017/10/24/024
 * Time: 21:40
 */
namespace app\api\validate;
use think\Validate;
class Order extends Validate
{
    public static function terrace($data){
        if(!is_numeric($data['ex_money'])){
            return '车辆佣金请输入完整的金额数字';
        }
        // if(!is_numeric($data['ex_overdue'])){
        //     return '逾期次数请输入数字';
        // }
        // if(!is_numeric($data['ex_term'])){
        //     return '逾执行期限请输入天数';
        // }
        $arrin = ['是','否'];
        if(!in_array($data['ex_virecord'],$arrin)){
            return '是否有违章记录是能输入是或者否';
        }
        if(!in_array($data['ex_gps'],$arrin)){
            return '是否有GPS只能输入是或者否';
        }
        return true;
    }
}