<?php
namespace app\api\controller;
use app\index\controller\Action;
use app\index\module\machine;
use think\Db;
use app\api\validate;
use think\session\driver\Redis;
class Machines extends Action
{
    protected $machine;

    protected $validate;

    public function __construct()
    {
        parent::__construct();
        $this->machine = new machine();
        $this->validate = $this->machine->validate;
    }

    public function addMachine()
    {
        $data = input();
        $info = $this->machine->where('account',$data['account'])->value('id');

        if ($info) {
            self::ajaxReturnError('该账号已经存在，不能重复添加!');
        }

        $data['password'] = $this->getpwd($data['password']);
        $result = $this->machine->validate($this->validate)->save($data);

        if ($result) {
            self::AjaxReturn($result,'设备添加成功');
        } else {
            $error = $this->machine->getError();
            self::ajaxReturnError($error);
        }
    }

    public function machineList()
    {
        $uid = input('uid');
        $switch = input('switch');

        if ($switch == 'admin') {
            $map['uid'] = $uid;
            $data = $this->machine
                ->where($map)
                ->order('create_time', 'DESC')
                ->select()
                ->toArray();

            self::ajaxReturn($data,'数据获取成功',1);
        }
    }

    public function updateMachine()
    {
        $id = input('id');

        if (input('switch') == 'deleted') {
            $result = $this->machine->where('id',$id)->delete();
        }

        if (input('switch') == 'forbidden') {
            $result = $this->machine->save(['status' => 0],['id' => $id]);
        }

        if (input('switch') == 'formatPwd') {
            $pwd = $this->getpwd(input('password'));
            $result = $this->machine->save(['password' => $pwd],['id' => $id]);
        }

        if(input('switch') == 'savemark') {
            $mark = input('mark');
            $result = $this->machine->save(['mark' => $mark],['id' => $id]);
        }

        $result ? self::ajaxReturn($result,'操作成功',1) : self::ajaxReturn($result,'操作失败',0);

    }

    public function statisticsMachine()
    {
        $role = input('role'); //大区经理统计

        if ($role == 1){
            $uid = input('uid'); //大区经理id
            $statisticsData = [];
            $datas = Db::name('machine')
                ->join('cardata','np_cardata.machine_id=np_machine.id','left')
                ->where(['np_machine.uid'=>$uid])
                ->order('create_time', 'DESC')
                ->select();

            $statisticsData['machine_list'] = $this->formatDataToMachineList($datas); //设备列表
            $statisticsData['machine_num'] = count($statisticsData['machine_list']); //设备数量
            $statisticsData['statistics_machine'] = $this->formateDataTostatistics($datas);

            $statisticsData ? self::ajaxReturn($statisticsData,'获取统计数据成功',1) : self::ajaxReturn($statisticsData,'失败',0);
        }

    }

    //大区经理设备列表
    private function formatDataToMachineList($data = array())
    {
        $formatData = [];

        foreach ($data as $key => $value) {
            $formatData[$value['id']] = $value;
        }

        return $formatData;
    }

    //大区经理设备统计
    private function formateDataTostatistics($data = array())
    {
        $formatData = [];

        foreach ($data as $key => $value) {
            if ($value['car_card']) {
                $formatData['list'][] = $value;
                $formatData['car_card_list'][] = $value['car_card'];
            }
        }

        $weekTime = time() - (7 * 24 * 60 * 60);
        $monthTime = time() - (30 * 24 * 60 *60);

        foreach ($formatData['list'] as $formatId => $data) {
            if(strtotime($data['car_addtime']) >= $weekTime) {
                $formatData['week_list'][] = $data;
                $formatData['car_card_weeklist'][] = $data['car_card'];
            }

            if(strtotime($data['car_addtime']) >= $monthTime) {
                $formatData['month_list'][] = $data;
                $formatData['car_card_monthlist'][] = $data['car_card'];
            }
        }

        $formatData['total_find_num'] = $this->findCarStatistics($formatData['car_card_list']); //报警总数
        $formatData['week_find_num']  = $this->findCarStatistics($formatData['car_card_weeklist']); //近7天报警数
        $formatData['month_find_num'] = $this->findCarStatistics($formatData['car_card_monthlist']); //近30天报警数

        $formatData['total_num'] = count($formatData['list']); //拍摄总数
        $formatData['week_num']  = count($formatData['week_list']); //近7天拍摄总数
        $formatData['month_num'] = count($formatData['month_list']); //近30天拍摄总数

        return $formatData;
    }

    private function findCarStatistics($car_cards = array())
    {
        $cards = array_unique($car_cards);
        $count = 0;

        foreach ($cards as $key => $card) {
            $findcard = Db::name('findcard')->where('card_number',$card)->field('card_number')->find();

            if ($findcard) {
                $count++;
            }
        }

        return $count;
    }

//测试phpredis
    public function redisTest(){
        $redis = new \Redis();
        $redis->connect(config('redis.host'), config('redis.hostport'));

        $redis->set('test','hello worddddd');
        $name = $redis->get('test');

        echo $name;
    }

    /**
     * 将session保存到session中
     * @Author   jfeng
     * 使用时需要修改session在config中的配置
     *
     */
    public function sessionTest(){
        $redis = new Redis;
        $redis->open();

        ini_set('session.save_handler', 'redis');
        ini_set('session.save_path', 'tcp://127.0.0.1:6379');
        session('openid', '123456'); //设置session
        echo '设置session-->' . session('openid') . '</br>';

        echo $redis->read($_COOKIE['PHPSESSID']);
    }

}