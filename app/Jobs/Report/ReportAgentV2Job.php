<?php


namespace App\Jobs\Report;


use App\Jobs\BaseJob;
use App\Models\ChannelModel;
use App\Services\ChannelReportService;
use App\Services\ChannelService;
use App\Services\CommonService;
use App\Services\RechargeService;
use App\Services\UserOrderService;
use App\Services\UserService;
use App\Utils\CommonUtil;
use App\Utils\LogUtil;

/**
 * 上报代理系统
 * Class ReportAgentJob
 * @property ChannelService $channelService
 * @property UserOrderService $userOrderService
 * @property RechargeService $rechargeService
 * @property CommonService $commonService
 * @property UserService $userService
 * @property ChannelReportService $channelReportService
 * @package App\Jobs\Report
 */
class ReportAgentV2Job extends BaseJob
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
        if(date('H')=='00'){
            $this->day($channels,2);
        }else{
            $this->day($channels);
        }
    }

    /**
     * @param $channels
     */
    public function order($channels)
    {
        $query = ['pay_at'=>['$gte'=>$this->startAt],'status'=>1,'channel_name'=>['$in'=>$channels]];
        $count     = $this->userOrderService->count($query);
        $pageSize  = 1000;
        $totalPage = ceil($count / $pageSize);
        for ($page = 1; $page <= $totalPage; $page++) {
            $skip  = ($page - 1) * $pageSize;
            $items = $this->userOrderService->getList($query, [], ['_id' => 1], $skip, $pageSize);
            $data  = array();
            foreach ($items as $index => $item) {
                if ($item['pay_id'] == -1) {
                    continue;
                }
                $data[] = array(
                    'order_sn'     => $item['order_sn'],
                    'user_id'      => $item['user_id'],
                    'username'     => $item['username'],
                    'record_type'  => 'vip',
                    'channel_name' => $item['channel_name'],
                    'order_money'  => $item['price'],
                    'pay_money'    => $item['real_price'],
                    'pay_status'   => $item['status'],
                    'pay_at'       => $item['pay_at'],
                    'register_at'  => $item['register_at'],
                    'order_at'     => $item['created_at'],
                );
            }
            $result = $this->doHttpRequest($data, '/report/order');
            LogUtil::info(sprintf('Do agent order   %s/%s =>%s ', $page, $totalPage, $result ? 'ok' : 'error'));
        }
    }

    /**
     * @param $channels
     */
    public function point($channels)
    {
        $query = ['pay_at'=>['$gte'=>$this->startAt],'status'=>1,'channel_name'=>['$in'=>$channels]];
        $count     = $this->rechargeService->count($query);
        $pageSize  = 1000;
        $totalPage = ceil($count / $pageSize);
        for ($page = 1; $page <= $totalPage; $page++) {
            $skip  = ($page - 1) * $pageSize;
            $items = $this->rechargeService->getList($query, [], ['_id' => 1], $skip, $pageSize);
            $data  = array();
            foreach ($items as $index => $item) {
                if ($item['pay_id'] == -1) {
                    continue;
                }
                $data[] = array(
                    'order_sn'     => $item['order_sn'],
                    'user_id'      => $item['user_id'],
                    'username'     => $item['username'],
                    'record_type'  => 'point',
                    'channel_name' => $item['channel_name'],
                    'order_money'  => $item['amount'],
                    'pay_money'    => $item['real_amount'],
                    'pay_status'   => $item['status'],
                    'pay_at'       => $item['pay_at'],
                    'register_at'  => $item['register_at'],
                    'order_at'     => $item['created_at'],
                );
            }
            $result = $this->doHttpRequest($data, '/report/order');
            LogUtil::info(sprintf('Do agent order  %s/%s =>%s ', $page, $totalPage, $result ? 'ok' : 'error'));
        }
    }

    /**
     * @param $channels
     */
    public function user($channels)
    {
        $query = ['register_at' => ['$gte' => $this->startAt], 'channel_name' => ['$in' => $channels]];
        if (container()->get('config')->app->filter_invalid_user) {//过滤无效用户
            $query['is_valid'] = 1;
        }
        $count     = $this->userService->count($query);
        $pageSize  = 1000;
        $totalPage = ceil($count / $pageSize);
        for ($page = 1; $page <= $totalPage; $page++) {
            $skip  = ($page - 1) * $pageSize;
            $items = $this->userService->getList($query, array(), array('_id' => 1), $skip, $pageSize);
            $data  = array();
            foreach ($items as $index => $item) {
                $data[] = array(
                    'user_id'      => $item['_id'],
                    'username'     => $item['username'],
                    'device_type'  => $item['device_type'],
                    'channel_name' => $item['channel_name'],
                    'register_at'  => $item['created_at'],
                );
            }
            $result = $this->doHttpRequest($data, '/report/user');
            LogUtil::info(sprintf('Do agent user  %s=> %s/%s =>%s ', $count, $page, $totalPage, $result ? 'ok' : 'error'));
        }
    }

    /**
     * 日活
     * @param $channels
     * @return void
     */
    public function day($channels, $maxDay = 1)
    {
        for ($i = 0; $i < $maxDay; $i++) {
            $date  = date("Y-m-d", strtotime("-{$i}day"));
            $query = ['date' => strval($date), 'code' => ['$in' => $channels]];

            $count     = $this->channelReportService->count($query);
            $pageSize  = 1000;
            $totalPage = ceil($count / $pageSize);
            for ($page = 1; $page <= $totalPage; $page++) {
                $skip  = ($page - 1) * $pageSize;
                $items = $this->channelReportService->getList($query, array(), array('_id' => 1), $skip, $pageSize);
                $data  = array();
                foreach ($items as $index => $item) {
                    $data[] = array(
                        'channel_name'      =>$item['code'],
                        'date'              =>$item['date'],
                        'app_day'           =>$item['app_day'],
                        'today_app_day'     =>$item['today_app_day'],
//                        'yesterday_app_day' =>$item['yesterday_app_day'],
                        'yesterday_app_day' =>$item['app_day']-$item['today_app_day'],
                        'uv'                => $this->channelReportService->getUVCount($item['code'], $date),
                        'pv'                => $this->channelReportService->getPVCount($item['code'], $date),
                        'adv'               => $item['adv'] ?? 0,
                        'adv_app'           => $item['adv_app'] ?? 0,
                        'dau_1'             => $item['app_day1'] ?? 0,
                        'dau_3'             => $item['app_day3'] ?? 0,
                        'dau_7'             => $item['app_day7'] ?? 0,
                        'dau_15'            => $item['app_day15'] ?? 0,
                        'dau_30'            => $item['app_day30'] ?? 0,
                        'movie_click'       => $item['view'] ?? 0
                    );
                }
                $result = $this->doHttpRequest($data, '/report/day');
                LogUtil::info(sprintf('Do agent day  %s=> %s/%s =>%s ', $count, $page, $totalPage, $result ? 'ok' : 'error'));
            }
        }
    }

    /**
     * @return array
     */
    public function channels()
    {
        $result = [];
        $items  = $this->channelService->getAll();
        foreach ($items as $item) {
//            //一天内没新增的不进行上报
//            if ((time() - $item['last_bind']) > 3600 * 24) {
//                continue;
//            }
            if (strpos($item['code'], 'st_') === false) {
                $result[] = trim($item['code']);
            }
        }
        return $result;
    }

    /**
     * @param $data
     * @param $url
     * @param int $retry
     * @return bool
     */
    public function doHttpRequest($data, $url, $retry = 5)
    {
        if ($retry < 1) {
            return false;
        }
        $configs = $this->commonService->getConfigs();
        $baseUrl = $configs['agent_v2_url'];
        $appId   = $configs['agent_v2_appid'];
        $appKey  = $configs['agent_v2_key'];

        $reqData     = json_encode($data);
        $time        = date('Y-m-d H:i:s');
        $requestData = array(
            'time'   => $time,
            'app_id' => $appId,
            'data'   => $reqData,
            'sign'   => md5($time . $appKey . $reqData)
        );
        try {
            $result = CommonUtil::httpPost($baseUrl . $url, $requestData);
            if (!$result) {
                throw new \Exception();
            }
            $result = json_decode($result, true);
            return $result['status'] == 'y' ? true : false;
        } catch (\Exception $e) {
            return $this->doHttpRequest($data, $url, --$retry);
        }
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
