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

    public function testImpord($excelPath)
    {
        // var_dump('接口未开放');die;
        header('Content-Type:text/html;charset=utf-8');
        // $filename = dirname(__FILE__).'/09.xls';//固定位置
        $filename = $_SERVER['DOCUMENT_ROOT'] . '/wwcr' . input('excelPath');
        //自动获取文件的类型提供给phpexcel用
        $fileType = PHPExcel_IOFactory::identify($filename);
        // dump($fileType);die;
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
        $index = new index();
        foreach ($excelArray as $key => $array) {
            foreach ($array as $k => $value) {
                if ($value[$produceValue[2]]) {
                    $formatData[$key][$k]['card_brand'] = $value[$produceValue[0]];
                    $formatData[$key][$k]['card_color'] = $value[$produceValue[1]];
                    $formatData[$key][$k]['card_number'] = $value[$produceValue[2]];
                    $formatData[$key][$k]['card_addtime'] = date('Y-m-d H:m:i', time());
                    $formatData[$key][$k]['card_uid'] = input('uid');
                    $formatData[$key][$k]['car_hash'] = md5(input('card_number'));
                    $formatData[$key][$k]['car_status'] = 1;
                    $isFindCar = Db::name('findcard')->where('card_number', $value[$produceValue[2]])->field('find_id')->find();

                    if (!$isFindCar) {

                        $index->findcarnew($formatData[$key][$k]);
                    }

                }
            }
        }

        return 'success';

    }

    protected function produceValue(){
        $data = [4,3,2];
        return $data;
    }

    public function addCardatas()
    {
        echo 'hahaha';die;
        $data['car_card'] = '陕A' . rand(10000, 99999);
        $data['car_location'] = '陕西省西安市莲湖区沣惠南路靠近金光门桥';
        $data['car_photo'] = '/public/uplaod/eff6b7e4ad3c5be1307867e1100b62f9-8c7dd922ad47494fc02c388e12c00eac.png';
        $data['car_addtime'] = '2018-05-18 17:17:22';
        $data['car_hash'] = 'c8e3aceceddf013628bf6263c401bb23';
        $data['machine_id'] = 25;

        for ($i=0; $i < 1500 ; $i++) {
            Db::name('cardata')->insert($data);
        }
    }

    public function deleteFindcard()
    {
        echo '123213';die;
        $cardNumbers = '京Q2QA83,蒙DYA068,京NFA930,京NME112,京N5MD00,冀GBB675,京QB53Z0,京HAR532,京QT39B1,京QF18C6,冀J301XM,京EC1889,京NB6962,京KW5361,冀AS38F5,津MSU108,晋L8D993,鲁NS9387,蒙A016VU,京QH6Q31,京Q91NL5,京NFOW49,京NRY506,京N6L433,晋L816E5';

        $result = Db::name('findcard')->where('card_number', 'in', $cardNumbers)->delete();
        dump($result);

        if ($result) {
            echo '成功';
        } else {
            echo '失败';

        }
    }

}
