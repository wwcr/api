<?php
namespace app\api\controller;

use app\api\validate;
use app\index\controller\Action;
use think\Db;
use think\session\driver\Redis;
use PHPExcel_IOFactory;
use PHPExcel;
use app\index\controller\Index;

class Phphandleexcel extends Action
{

    public function testImpord()
    {
        var_dump('接口未开放');die;
        header('Content-Type:text/html;charset=utf-8');
        $filename = dirname(__FILE__).'/02.xls';
        //自动获取文件的类型提供给phpexcel用
        $fileType = PHPExcel_IOFactory::identify($filename);
        //获取文件读取操作对象
        $objReader = PHPExcel_IOFactory::createReader($fileType);
        $sheetName = array('Sheet1','Sheet2');
        //只加载指定的sheet
        $objReader->setLoadSheetsOnly($sheetName);
        //加载文件
        $objPHPExcel = $objReader->load($filename);
        //逐行读取，循环取sheet
        $insertData = array();
        $sheetCount = $objPHPExcel->getSheetCount();
        for ($i=0; $i < $sheetCount ; $i++) {
            $data = $objPHPExcel->getsheet($i)->toArray();
            $deltedFirst = array_shift($data);
            $excelArray[] = $data;
        }

        $produceValue = $this->produceValue();
        foreach ($excelArray as $key => $array) {
            foreach ($array as $k => $value) {
                    $formatData[$key][$k]['card_brand'] = $value[$produceValue[0]];
                    $formatData[$key][$k]['card_color'] = $value[$produceValue[1]];
                    $formatData[$key][$k]['card_number'] = $value[$produceValue[2]];
                    $formatData[$key][$k]['card_addtime'] = date('Y-m-d H:m:i', time());
                    $formatData[$key][$k]['card_uid'] = 52;
                    $formatData[$key][$k]['car_hash'] = md5(input('card_number'));
                    $formatData[$key][$k]['car_status'] = 1;
                    $index = new index();
                    $index->findcarnew($formatData[$key][$k]);
            }
        }

        var_dump($formatData);die;

    }

    protected function produceValue(){
        $data = [2,5,4];
        return $data;
    }

}
