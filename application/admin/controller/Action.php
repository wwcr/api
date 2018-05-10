<?php
/**
 * Created by PhpStorm.
 * User: iyahe@qq.com (天明)
 * Date: 2017/10/24/024
 * Time: 23:48
 */
namespace app\admin\controller;
use think\Controller;
use think\Db;

class  Action extends Controller{
   public function test(){
   	echo 11111;
   }
   public function add_banner(){//添加轮播图
   		$data['banner'] = input('thumb');//图片路径
   		$data['title'] = input('name');
   		$data['content'] = input('content');
   		$res = Db::name('banner')->insert($data);
   		echo json_encode($res);
   }
   public function get_banner(){//添加轮播图
   		$res = Db::name('banner')->select();
   		echo json_encode($res);
   }
   public function update_banner(){//添加轮播图
   		$id = input('id');
   		$data['banner'] = input('banner');//图片路径
   		$data['title'] = input('title');
   		$data['content'] = input('content');
   		$res = Db::name('banner')->where('id',$id)->update($data);
   		echo json_encode($res);
   }
   public function delete_banner(){//添加轮播图
   		$id = input('id');
   		$res = Db::name('banner')->where('id',$id)->delete();
   		echo json_encode($res);
   }
   public function delete_article(){//添加轮播图
   		$id = input('id');
   		$res = Db::name('chatinfo')->where('id',$id)->delete();
   		echo json_encode($res);
   }
   public function edit_banner(){//添加轮播图
   		$id = input('id');
   		$res = Db::name('banner')->where('id',$id)->select();
   		echo json_encode($res);
   }
}