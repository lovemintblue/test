<?php


namespace App\Jobs\Report;


use App\Constants\CommonValues;
use App\Jobs\BaseJob;
use App\Models\AppLogModel;
use App\Models\ReportHourLogModel;
use App\Services\CollectionsService;
use App\Services\RechargeService;
use App\Services\UserOrderService;
use App\Services\UserService;
use App\Utils\CommonUtil;
use App\Utils\LogUtil;

/**
 * 强大的小时统计
 * Class ReportHourJob
 * @property AppLogModel $appLogModel
 * @property ReportHourLogModel $reportHourLogModel
 * @property UserService $userService
 * @property UserOrderService $userOrderService
 * @property RechargeService $rechargeService
 * @property CollectionsService $collectionsService
 * @package App\Jobs\Report
 */
class ReportHourJob extends BaseJob
{
    public $date;
    public function __construct($date)
    {
        $this->date=$date;
    }

    public function handler($uniqid)
    {
        $startTime = strtotime($this->date);
        $endTime   = strtotime(date('Y-m-d 23:59:59',$startTime));
        //统计24小时
        $pid = $this->count($startTime,$endTime,0);
        for ($i=0;$i<4*24;$i++){
            $startAt=$i*15*60+$startTime;
            $endAt  =$startAt+15*60;
            LogUtil::info(__CLASS__.' '.date("Y-m-d H:i:s",$startAt).'至'.date("Y-m-d H:i:s",$endAt));
            $this->count($startAt,$endAt,$pid);
        }
    }

    public function count($startAt,$endAt,$pid)
    {
        $date = date("Y-m-d",$startAt);


        $androidUser = $this->userService->count(['register_at'=>['$gte'=>$startAt,'$lte'=>$endAt],'device_type'=>'android']);
        $h5User     = $this->userService->count(['register_at'=>['$gte'=>$startAt,'$lte'=>$endAt],'device_type'=>'h5']);
        $totalUser   = $androidUser+$h5User;

        $androidDay   = $this->appLogModel->count(['device_type'=>'android','created_at'=>['$gte'=>$startAt,'$lte'=>$endAt]]);
        $h5Day        = $this->appLogModel->count(['device_type'=>'h5','created_at'=>['$gte'=>$startAt,'$lte'=>$endAt]]);
        $totalDay     = $androidDay+$h5Day;

        //今日日活
        $androidDayToday   = $this->appLogModel->count(['register_date' => $this->date,'device_type'=>'android','created_at'=>['$gte'=>$startAt,'$lte'=>$endAt]]);
        $h5DayToday        = $this->appLogModel->count(['register_date' => $this->date,'device_type'=>'h5','created_at'=>['$gte'=>$startAt,'$lte'=>$endAt]]);
        $totalDayToday     = $androidDayToday+$h5DayToday;

        $vipTotal     = $this->userOrderService->count(['created_at'=>['$gte'=>$startAt,'$lte'=>$endAt]]);
        $rechargeTotal= $this->rechargeService->count(['created_at'=>['$gte'=>$startAt,'$lte'=>$endAt]]);

        $succVip    =$this->userOrderService->userOrderModel->aggregate([
            ['$match' => ['created_at'=>['$gte'=>$startAt,'$lte'=>$endAt],'status'=>1]],
            ['$group' => ['_id' => null, 'total_money' => ['$sum' => '$real_price'], 'count_num' => ['$sum' => 1]]]
        ]);
        $succVipTotal = $succVip?$succVip->count_num:0;
        $succVipMoney = $succVip?$succVip->total_money:0;

        $succRecharge =$this->rechargeService->rechargeModel->aggregate([
            ['$match' => ['created_at'=>['$gte'=>$startAt,'$lte'=>$endAt],'status'=>1]],
            ['$group' => ['_id' => null, 'total_money' => ['$sum' => '$real_amount'], 'count_num' => ['$sum' => 1]]]
        ]);
        $succRechargeTotal = $succRecharge?$succRecharge->count_num:0;
        $succRechargeMoney = $succRecharge?$succRecharge->total_money:0;

        $orderTotal = $vipTotal+$rechargeTotal;
        $succTotal  = $succVipTotal+$succRechargeTotal;
        $succMoney  = $succVipMoney+$succRechargeMoney;


        //客单价=总金额/成功订单数
        $tav   = $succTotal>0?round($succMoney/$succTotal,2):0;
        //付费率=成功订单数/注册用户数
        $apr   = $totalUser>0?round($succTotal/$totalUser*100,2):0;
        //用户平均收入
        $arpu  = $totalUser>0?round($succMoney/$totalUser,2):0;
        //支付成功率=成功订单数/总订单数
        $payr  = $orderTotal>0?round($succTotal/$orderTotal*100,2):0;

        $data =[
            '_id'           => md5($startAt.'_'.$endAt),
            'reg'           => $totalUser,
            'reg_android'   => $androidUser,
            'reg_h5'        => $h5User,
            'dau'           => $totalDay,
            'dau_android'   => $androidDay,
            'dau_h5'        => $h5Day,
            'dau_today'           => $totalDayToday,
            'dau_today_android'   => $androidDayToday,
            'dau_today_h5'        => $h5DayToday,
            'order'         => $orderTotal,
            'order_success' => $succTotal,
            'order_money'   => $succMoney,
            'tav'           => $tav,
            'apr'           => $apr,
            'payr'          => $payr,
            'arpu'          => $arpu,
            'month'         => date("Y-m",$startAt),
            'date'          => $date,
            'date_limit'    => date("H:i:s",$startAt).'-'.date("H:i:s",$endAt),
            'pid'           => strval($pid),
            'created_at'    =>time(),
            'updated_at'    =>time(),
        ];

        //留存
        $days = CommonValues::getAppDay();
        foreach($days as $key=>$val){
            $data['dau_'.$key.'_android'] = $this->appLogModel->count(['register_date' => date('Y-m-d',$startAt-$key*24*60*60),'device_type'=>'android','created_at'=>['$gte'=>$startAt,'$lte'=>$endAt]]);
            $data['dau_'.$key.'_h5']      = $this->appLogModel->count(['register_date' => date('Y-m-d',$startAt-$key*24*60*60),'device_type'=>'h5','created_at'=>['$gte'=>$startAt,'$lte'=>$endAt]]);
            $data['dau'.$key]         = $data['dau_'.$key.'_android']+$data['dau_'.$key.'_h5'];
        }

        $this->reportHourLogModel->findAndModify(['_id'=>$data['_id']],$data,[],true);
        return $data['_id'];
    }

    public function success($uniqid)
    {
        // TODO: Implement success() method.
    }

    public function error($uniqid)
    {
        // TODO: Implement error() method.
    }

}