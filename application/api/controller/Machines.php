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
                 ->join('findcard','np_findcard.card_cardata=np_cardata.car_id','left')
                ->where(['np_machine.uid'=>$uid])
                ->field('np_cardata.machine_id,np_cardata.car_id,np_cardata.car_addtime,np_cardata.car_card,np_machine.id,np_machine.uid,np_machine.account,np_machine.mark,np_findcard.find_id')//join 然后统计报警数量
                ->order('create_time', 'DESC')
                ->select();
            $statisticsData['machine_list'] = $this->formatDataToMachineList($datas); //设备列表
            $statisticsData['machine_num'] = count($statisticsData['machine_list']); //设备数量
            $statisticsData['statistics_machine'] = $this->formateDataTostatistics($datas);

            // $statisticsData ? self::ajaxReturn($statisticsData,'获取统计数据成功',1) : self::ajaxReturn($statisticsData,'失败',0);
            self::ajaxReturn($statisticsData,'获取统计数据成功',1);
        }

    }
    public function statisticsMachinetest()//这是以前
    {
        $role = input('role'); //大区经理统计

        if ($role == 1){
             $uid = input('uid'); //大区经理id
            $statisticsData = [];
            $datas = Db::name('machine')
                ->join('cardata','np_cardata.machine_id=np_machine.id','left')
                 // ->join('findcard','np_findcard.card_cardata=np_cardata.car_id','left')
                ->where(['np_machine.uid'=>$uid])
                ->field('np_cardata.machine_id,np_cardata.car_id,np_cardata.car_addtime,np_cardata.car_card,np_machine.id,np_machine.uid,np_machine.account,np_machine.mark')
                ->order('create_time', 'DESC')
                ->select();
            $statisticsData['machine_list'] = $this->formatDataToMachineList($datas); //设备列表
            $statisticsData['machine_num'] = count($statisticsData['machine_list']); //设备数量
            $statisticsData['statistics_machine'] = $this->formateDataTostatisticstest($datas);

            // // $statisticsData ? self::ajaxReturn($statisticsData,'获取统计数据成功',1) : self::ajaxReturn($statisticsData,'失败',0);
            self::ajaxReturn($statisticsData,'获取统计数据成功',1);
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
    private function formateDataTostatisticstest($data = array())//这是以前便利执行sql
    {
        $formatData = [];

        foreach ($data as $key => $value) {
            if ($value['car_card']) {
                $formatData2['list'][] = $value;
                // $formatData['car_card_list'][] = $value['car_card'];
                $formatData2['car_card_list'][] = $value['car_id'];
            }
        }

        $weekTime = time() - (7 * 24 * 60 * 60);//7天前的时间
        $monthTime = time() - (30 * 24 * 60 *60);//一个月之前的时间
        $today = strtotime(date('Y-m-d',time())); //今天0点时间

        foreach ($formatData2['list'] as $formatId => $data) {
            if(strtotime($data['car_addtime']) >= $weekTime) {
                $formatData2['week_list'][] = $data;
                // $formatData['car_card_weeklist'][] = $data['car_card'];
                $formatData2['car_card_weeklist'][] = $data['car_id'];
            }

            if(strtotime($data['car_addtime']) >= $monthTime) {
                $formatData2['month_list'][] = $data;
                // $formatData['car_card_monthlist'][] = $data['car_card'];
                $formatData2['car_card_monthlist'][] = $data['car_id'];
            }

            if(strtotime($data['car_addtime']) >= $today) {
                $formatData2['today_list'][] = $data;
                $formatData2['car_card_todaylist'][] = $data['car_id'];
            }
        }
        $collectCount = $this->findCarStatistics($formatData2['list']);
        $formatData['total_find_num'] = $collectCount['count']; //报警总数
        $formatData['today_find_num']  = $collectCount['todayCount']; //当天天报警数
        $formatData['week_find_num']  = $collectCount['weekCount']; //近7天报警数
        $formatData['month_find_num'] = $collectCount['monthCount']; //近30天报警数list']); //报警总数
        // $formatData['today_find_num']  = $this->findCarStatistics($formatData2['car_card_list']); //报警zong数
        // $formatData['today_find_num']  = $this->findCarStatistics($formatData2['car_card_todaylist']); //当天天报警数
        // $formatData['week_find_num']  = $this->findCarStatistics($formatData2['car_card_weeklist']); //近7天报警数
        // $formatData['month_find_num'] = $this->findCarStatistics($formatData2['car_card_monthlist']); //近30天报警数

        $formatData['total_num'] = count($formatData2['list']); //拍摄总数
        $formatData['today_num']  = count($formatData2['today_list']); //当天拍摄数量
        $formatData['week_num']  = count($formatData2['week_list']); //近7天拍摄总数
        $formatData['month_num'] = count($formatData2['month_list']); //近30天拍摄总数

        // $formatData['total_num']= $this->findCarStatistics($formatData['car_card_list']); //报警总数
        // $formatData['week_num']= $this->findCarStatistics($formatData['car_card_weeklist']); //近7天报警数
        // $formatData['month_num']= $this->findCarStatistics($formatData['car_card_monthlist']); //近30天报警数

        // $formatData['total_find_num'] = count($formatData['list']); //拍摄总数
        // $formatData['today_num']  = count($formatData['today_list']); //当天拍摄数量
        // $formatData['week_find_num']  = count($formatData['week_list']); //近7天拍摄总数
        // $formatData['month_find_num'] = count($formatData['month_list']); //近30天拍摄总数

        return $formatData;
    }

    private function findCarStatistics($cards = array())
    {
        // $cards = array_unique($ccards);
        $count = 0;
        $weekCount = 0;
        $monthCount = 0;
        $todayCount = 0;
        $weekTime = time() - (7 * 24 * 60 * 60);
        $monthTime = time() - (30 * 24 * 60 *60);
        $today = strtotime(date('Y-m-d',time())); //今天0点时间

        foreach ($cards as $key => $card) {
            // $findcard = Db::name('findcard')->where('card_number',$card)->field('card_number')->find();
            $findcard = Db::name('findcard')->where('card_cardata',$card['car_id'])->field('card_number')->find();

            if ($findcard) {
                $count++;
            }

            if(strtotime($card['car_addtime']) >= $weekTime && $findcard) {
                $weekCount++;
            }

            if(strtotime($card['car_addtime']) >= $monthTime && $findcard) {
                $monthCount++;
            }

            if(strtotime($card['car_addtime']) >= $today && $findcard) {
                $todayCount++;
            }
        }

        $collectCount = array(
            'count' => $count,
            'weekCount' => $weekCount,
            'monthCount' => $monthCount,
            'todayCount' => $todayCount,
        );

        return $collectCount;
    }
     private function formateDataTostatistics($data = array())
    {
        $formatData = [];

        foreach ($data as $key => $value) {
            if ($value['car_card']) {
                $formatData2['list'][] = $value;
                // $formatData['car_card_list'][] = $value['car_card'];
                $formatData2['car_card_list'][] = $value['car_id'];
            }
        }

        $weekTime = time() - (7 * 24 * 60 * 60);//7天前的时间
        $monthTime = time() - (30 * 24 * 60 *60);//一个月之前的时间
        $today = strtotime(date('Y-m-d',time())); //今天0点时间
        // echo '<pre>';
        // var_dump($formatData2['list']);
        // die;
        $count = 0;
        $weekCount = 0;
        $monthCount = 0;
        $todayCount = 0;
        foreach ($formatData2['list'] as $formatId => $data) {
            if($data['find_id']){
                $count++;
            }
            if(strtotime($data['car_addtime']) >= $weekTime) {
                if($data['find_id']){
                $weekCount++;
                }
                $formatData2['week_list'][] = $data;
                // $formatData['car_card_weeklist'][] = $data['car_card'];
                $formatData2['car_card_weeklist'][] = $data['car_id'];
            }

            if(strtotime($data['car_addtime']) >= $monthTime) {
                if($data['find_id']){
                $monthCount++;
                }
                $formatData2['month_list'][] = $data;
                // $formatData['car_card_monthlist'][] = $data['car_card'];
                $formatData2['car_card_monthlist'][] = $data['car_id'];
            }

            if(strtotime($data['car_addtime']) >= $today) {
                if($data['find_id']){
                $todayCount++;
                }
                $formatData2['today_list'][] = $data;
                $formatData2['car_card_todaylist'][] = $data['car_id'];
            }
        }
        $formatData['total_find_num'] = $count; //报警总数
        $formatData['today_find_num']  = $todayCount; //当天天报警数
        $formatData['week_find_num']  = $weekCount; //近7天报警数
        $formatData['month_find_num'] = $monthCount; //近30天报警数list']); //报警总数
        // // $formatData['today_find_num']  = $this->findCarStatistics($formatData2['car_card_list']); //报警zong数
        // // $formatData['today_find_num']  = $this->findCarStatistics($formatData2['car_card_todaylist']); //当天天报警数
        // // $formatData['week_find_num']  = $this->findCarStatistics($formatData2['car_card_weeklist']); //近7天报警数
        // // $formatData['month_find_num'] = $this->findCarStatistics($formatData2['car_card_monthlist']); //近30天报警数

        $formatData['total_num'] = count($formatData2['list']); //拍摄总数
        $formatData['today_num']  = count($formatData2['today_list']); //当天拍摄数量
        $formatData['week_num']  = count($formatData2['week_list']); //近7天拍摄总数
        $formatData['month_num'] = count($formatData2['month_list']); //近30天拍摄总数

        // $formatData['total_num']= $this->findCarStatistics($formatData['car_card_list']); //报警总数
        // $formatData['week_num']= $this->findCarStatistics($formatData['car_card_weeklist']); //近7天报警数
        // $formatData['month_num']= $this->findCarStatistics($formatData['car_card_monthlist']); //近30天报警数

        // $formatData['total_find_num'] = count($formatData['list']); //拍摄总数
        // $formatData['today_num']  = count($formatData['today_list']); //当天拍摄数量
        // $formatData['week_find_num']  = count($formatData['week_list']); //近7天拍摄总数
        // $formatData['month_find_num'] = count($formatData['month_list']); //近30天拍摄总数

        return $formatData;
    }
private function findCarStatisticstest($cards = array())
    {
        // $cards = array_unique($ccards);
        $count = 0;
        $weekCount = 0;
        $monthCount = 0;
        $todayCount = 0;
        $weekTime = time() - (7 * 24 * 60 * 60);
        $monthTime = time() - (30 * 24 * 60 *60);
        $today = strtotime(date('Y-m-d',time())); //今天0点时间

        $allcard =[];
        foreach ($cards as $key => $card){
                $allcard[]=$card['car_id'];
        }

        $findIds = implode(',', $allcard);
        $findcards = Db::name('findcard')->where('card_cardata in (' . $findcards . ')')->select();

        foreach ($findcards as $key => $card) {
            // $findcard = Db::name('findcard')->where('card_cardata',$card['car_id'])->field('card_number')->find();

            if ($findcard) {
                $count++;
            }

            if(strtotime($card['car_addtime']) >= $weekTime && $card['card_number']) {
                $weekCount++;
            }

            if(strtotime($card['car_addtime']) >= $monthTime && $card['card_number']) {
                $monthCount++;
            }

            if(strtotime($card['car_addtime']) >= $today && $card['card_number']) {
                $todayCount++;
            }
        }

        $collectCount = array(
            'count' => $count,
            'weekCount' => $weekCount,
            'monthCount' => $monthCount,
            'todayCount' => $todayCount,
        );

        return $collectCount;
    }
//测试phpredis
    public function redisTest(){
        $redis = new \Redis();
        $redis->connect(config('redis.host'), config('redis.hostport'));

        $redis->set('test','hello worddddd'); //string 类型
        $redis->hmset('user:2', ['id' => 2, 'name' => '姜峰2', 'age' => 29]); //hash 类型
        $name = $redis->get('test');
        $user = $redis->hgetall('user:1');
        echo $name;
        echo '</br>';
        var_dump($user);
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

        $user = [
            'id' => 1,
            'status' => 1,
            'name' => '王德贵'
        ];
        ini_set('session.save_handler', 'redis');
        ini_set('session.save_path', 'tcp://127.0.0.1:6379');
        session('openid3', $user); //设置session
        var_dump(session('openid3')) ;

        echo $redis->read($_COOKIE['PHPSESSID']);
    }

}
