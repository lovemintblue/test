<?php


namespace App\Jobs\Report;


use App\Jobs\BaseJob;
use App\Models\AppLogModel;
use App\Models\ChannelModel;
use App\Services\AgentSystemService;
use App\Services\ChannelReportService;
use App\Services\ChannelService;
use App\Services\CommonService;
use App\Services\RechargeService;
use App\Services\UserOrderService;
use App\Services\UserService;
use App\Utils\CommonUtil;
use App\Utils\LogUtil;

/**
 * 上报代理系统v3
 * Class ReportAgentV3Job
 * @property ChannelService $channelService
 * @property UserService $userService
 * @property UserOrderService $userOrderService
 * @property RechargeService $rechargeService
 * @property CommonService $commonService
 * @property ChannelReportService $channelReportService
 * @property AgentSystemService $agentSystemService
 * @property AppLogModel $appLogModel
 * @package App\Jobs\Report
 */
class ReportAgentV3Job extends BaseJob
{
    public $startAt;

    public function __construct($startAt)
    {
        $this->startAt=$startAt;
    }

    public function handler($uniqid)
    {
        // TODO: Implement handler() method.
        $channels=$this->channels();
        $this->order($channels);
        $this->point($channels);
        $this->user($channels);
        $this->day($channels);
    }

    /**
     * 会员订单
     * @param $channels
     */
    public function order($channels)
    {
        $query = ['pay_at'=>['$gte'=>$this->startAt],'status'=>1,'channel_name'=>['$in'=>$channels]];
        $count     = $this->userOrderService->count($query);
        $pageSize  = 300;
        $totalPage = ceil($count / $pageSize);
        for ($page = 1; $page <= $totalPage; $page++) {
            $skip = ($page - 1) * $pageSize;
            $items = $this->userOrderService->getList($query, [], ['_id' => 1], $skip, $pageSize);
            $data = array();
            foreach ($items as $index => $item) {
                if($item['pay_id']==-1){continue;}
                $data[] = array(
                    'order_sn'      => $item['order_sn'],
                    'user_id'       => $item['user_id'],
                    'username'      => $item['username'],
                    'record_type'   => 'vip',
                    'channel_name'  => $item['channel_name'],
                    'order_money'   => $item['price'],
                    'pay_money'     => $item['real_price'],
                    'pay_status'    => $item['status'],
                    'pay_at'        => $item['pay_at'],
                    'register_at'   => $item['register_at'],
                    'order_at'      => $item['created_at'],
                );
            }
            $result = $this->agentSystemService->reportOrder($data);
            LogUtil::info(sprintf('Do agent order   %s/%s =>%s ', $page, $totalPage, $result ? 'ok' : 'error'));
        }
    }

    /**
     * 金币订单
     * @param $channels
     */
    public function point($channels)
    {
        $query = ['pay_at'=>['$gte'=>$this->startAt],'status'=>1,'channel_name'=>['$in'=>$channels]];
        $count     = $this->rechargeService->count($query);
        $pageSize  = 300;
        $totalPage = ceil($count / $pageSize);
        for ($page = 1; $page <= $totalPage; $page++) {
            $skip = ($page - 1) * $pageSize;
            $items = $this->rechargeService->getList($query, [], ['_id' => 1], $skip, $pageSize);
            $data = array();
            foreach ($items as $index => $item) {
                if($item['pay_id']==-1){continue;}
                $data[] = array(
                    'order_sn'      => $item['order_sn'],
                    'user_id'       => $item['user_id'],
                    'username'      => $item['username'],
                    'record_type'   => 'point',
                    'channel_name'  => $item['channel_name'],
                    'order_money'   => $item['amount'],
                    'pay_money'     => $item['real_amount'],
                    'pay_status'    => $item['status'],
                    'pay_at'        => $item['pay_at'],
                    'register_at'   => $item['register_at'],
                    'order_at'      => $item['created_at'],
                );
            }
            $result = $this->agentSystemService->reportOrder($data);
            LogUtil::info(sprintf('Do agent order  %s/%s =>%s ', $page, $totalPage, $result ? 'ok' : 'error'));
        }
    }

    /**
     * 用户
     * @param $channels
     * @return void
     */
    public function user($channels)
    {
        $query = ['register_at'=>['$gte'=>$this->startAt],'channel_name'=>['$in'=>$channels]];
        if(container()->get('config')->app->filter_invalid_user){//过滤无效用户
            $query['is_valid'] = 1;
        }
        $count = $this->userService->count($query);
        $pageSize = 300;
        $totalPage = ceil($count / $pageSize);
        for ($page = 1; $page <= $totalPage; $page++) {
            $skip = ($page - 1) * $pageSize;
            $items = $this->userService->getList($query, array(), array('_id' => 1), $skip, $pageSize);
            $data = array();
            foreach ($items as $index => $item) {
                $data[] = array(
                    'user_id'       => $item['_id'],
                    'username'      => $item['username'],
                    'device_type'   => $item['device_type'],
                    'channel_name'  => $item['channel_name'],
                    'register_at'   => $item['created_at'],
                    'register_ip'   => $item['register_ip'],
                );
            }
            $result = $this->agentSystemService->reportUser($data);
            LogUtil::info(sprintf('Do agent user  %s=> %s/%s =>%s ', $count,$page, $totalPage, $result ? 'ok' : 'error'));
        }
    }

    /**
     * 日活
     * @param $channels
     * @return void
     */
    public function day($channels)
    {
        $query = ['created_at'=>['$gte'=>$this->startAt],'channel_name'=>['$in'=>$channels]];
        if(container()->get('config')->app->filter_invalid_user){//过滤无效日活
            $query['is_valid'] = 1;
        }
        $count = $this->appLogModel->count($query);
        $pageSize = 300;
        $totalPage = ceil($count / $pageSize);
        for ($page = 1; $page <= $totalPage; $page++) {
            $skip = ($page - 1) * $pageSize;
            $items = $this->appLogModel->find($query, array(), array('created_at' => 1), $skip, $pageSize);
            $data = array();
            foreach ($items as $index => $item) {
                $data[] = array(
                    'user_id'       => $item['user_id'],
                    'channel_name'  => $item['channel_name'],
                    'ip'            => $item['ip'],
                    'device_type'   => $item['device_type'],
                    'register_at'   => strtotime($item['register_date']),
                    'created_at'    => $item['created_at'],
                );
            }
            $result = $this->agentSystemService->reportDayLog($data);
            LogUtil::info(sprintf('Do agent day  %s=> %s/%s =>%s ', $count,$page, $totalPage, $result ? 'ok' : 'error'));
        }
    }

    /**
     * @return array
     */
    public function channels()
    {
        $result =[];
        $items = $this->channelService->getAll();
        foreach ($items as $item) {
//            //一天内没新增的不进行上报
//            if ((time() - $item['last_bind']) > 3600 * 24) {
//                continue;
//            }
            if(strpos($item['code'],'st_')===false){
                $result[]=trim($item['code']);
            }
        }
        return $result;
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