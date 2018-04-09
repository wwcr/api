<?php

namespace app\push\controller;
use think\Db;
use think\worker\Server;
use think\Cache;

class Worker extends Server
{
    protected $socket = 'tcp://0.0.0.0:8888';

    /**
     * 收到信息
     * @param $connection
     * @param $data
     */
    public function onMessage($connection, $data)
    {	
   //      $res = $this->dataToStr($data);
        $res = $this->dataToStr($data);
 		$sign = ['data' =>$res];
    	$res = Db::name('test')->insert($sign);
        $new = $this->strtoData();
        $connection->send($data);
    }
    function _str2hex($string)  
{  
       $str = '';  
       for($i=0; $i<strlen($string); $i+=2){  
               $str .= chr(hexdec(substr($string,$i,2)));  
       }  
       return $str;  
}  
    /*
     终端协议号
     服务器响应相同协议号
     01 --- 登录包
     22 --- 定位数据包
     26 --- 报警包
     13 --- 心跳包
     80 --- 服务器发送指令包  -- 终端返回 15 
     */
    public function response(){
    	//获取协议号
    	$num = '';
    	switch ($num) {
    		case 'value':
    			# code...
    			break;
    		
    		default:
    			# code...
    			break;
    	}
    }
    public function dataToStr($hex) {//把ACII码转为十进制保存 然后转为16进制进行检验
	      $string = "";
	      for ($i = 0; $i < strlen($hex); $i++) {
	       $string.=' '.ord($hex[$i]);
	      }
        $res = explode(" ",ltrim($string));//把最左面的空格去掉
		$new_data = '';
		foreach ($res as $key => $value) {
		  $data = strlen(dechex($value))>1 ? dechex($value) : '0'.dechex($value);//长度不是两位补0
		     $new_data.=' '.$data;
		}
	    return ltrim($new_data);
    }
    public function strtoData() {//把ACII码转为十进制保存 然后转为16进制进行检验----反过来 奶奶的
	 $data = '78 78 11 01 00 00 01 36 11 28 12 40 01 00 32 00 00 00 bb ce 0d 0a';
	 $res = explode(" ",$data);
	 // var_dump($res);
	 $string = '';
	 foreach ($res as $key => $value) {
	    $string .= ' '.hexdec($value);
	 }
	 $new = ltrim($string);
	 $new = explode(" ",$new);
	 $newdata = "";
	 foreach ($new as $key => $value) {
	    $newdata .= chr($value);
	 }
	 return $newdata;
    }
    /**
     * 当连接建立时触发的回调函数
     * @param $connection
     */
    public function onConnect($connection)
    {
        // $connection->send('我收到你的信息了');
    }

    /**
     * 当连接断开时触发的回调函数
     * @param $connection
     */
    public function onClose($connection)
    {
        
    }

    /**
     * 当客户端的连接上发生错误时触发
     * @param $connection
     * @param $code
     * @param $msg
     */
    public function onError($connection, $code, $msg)
    {
        echo "error $code $msg\n";
    }

    /**
     * 每个进程启动
     * @param $worker
     */
    public function onWorkerStart($worker)
    {

    }
}