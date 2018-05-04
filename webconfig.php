<?php
/**
 * descrption:项目基础配置文件，每项配置都对项目影响较大，修改时请慎重
 * User: jfeng
 * Date: 2018/4/24
 */
return [
    //开启押金模式
    'depositButton' => false,

    //开启认证审核模式
    'certificationAudit' => true,

     //开启委单审核模式
    'findcarAudit' => true,

    //大区经理开启地域模式
    'managerCityProxy' => true,

    //寻车导航
    'findcarBanner' =>"[{'val':'审核中','status': -2,},{'val':'未通过','status': -1,},{'val':'查找中','status': 0,},{'val':'已找到','status': 2,}]",

];