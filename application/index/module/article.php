<?php
/**
 * Created by PhpStorm.
 * User: iyahe@qq.com (天明)
 * Date: 2017/7/26
 * Time: 17:27
 */
namespace app\index\module;
use think\Controller;
use think\Db;
class article{
    private static $_instance;
    public static function start() {
        if (!self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    public function add($title,$desc,$content,$cate,$thumb='',$index=0,$editID=''){
        $index = $index == true ? 1: 0;
        $insert = array(
            'art_title'=>$title,
            'art_desc'=>$desc,
            'art_content'=>$content,
            'art_time'=>getStrtime(),
            'art_update'=>getStrtime(),
            'art_thumb'=>$thumb,
            'art_cate'=>$cate,
            'art_auid'=>1,
            'art_index'=>$index
        );
        if(intval($editID) > 0){
            foreach ($insert as $k=>$v){
                if($v == ''){
                    unset($insert[$k]);
                }
            }
            return Db::name('article')->where(['article_id'=>$editID])->update($insert);
        }else{
            return Db::name('article')->insertGetId($insert);
        }
    }
    public function get($where=[],$limit=10){
        $list = Db::name('article')
            ->join('admin','np_admin.ad_id=np_article.art_auid','left')
            ->join('acate','np_acate.cate_id=np_article.art_cate')
            ->where($where)
            ->order('article_id DESC')
            ->field('ad_name,art_content,art_desc,art_index,art_thumb,art_time,art_title,art_update,article_id,cate_name,cate_id')
            ->paginate($limit);
        return $list;
    }
    public function once($id){
        return Db::name('article')
            ->where(['article_id'=>$id])
            ->find();
    }
}