<?php


namespace App\Jobs\Center;

use App\Services\AccountService;
use App\Services\ChannelService;
use App\Services\DataCenterService;
use App\Services\RechargeService;
use App\Services\UserBuyLogService;
use App\Services\UserOrderService;
use App\Services\UserService;
use App\Utils\LogUtil;

/**
 * 数据中心
 * Class CenterAgentJob
 * @property ChannelService $channelService
 * @property UserService $userService
 * @property UserOrderService $userOrderService
 * @property RechargeService $rechargeService
 * @property AccountService $accountService
 * @property UserBuyLogService $userBuyLogService
 * @package App\Jobs\Common
 */
class CenterDataJob extends CenterBaseJob
{
    public $action;
    public $startAt;
    public $configs;

    public function __construct($action='')
    {
        $this->action = $action;
        $this->configs = $this->getCenterConfig('data');
        DataCenterService::setAppid($this->configs['appid']);
    }

    public function handler($uniqid)
    {
        switch ($this->action) {
            case 'report':
                $this->report();
                break;
            default:
                $this->onQueue();
                break;
        }

    }

    /**
     * @return void
     */
    public function onQueue()
    {
        LogUtil::debug("queue");
        DataCenterService::setRedis(container()->get('redis'));
        DataCenterService::onQueue($this->configs['push_url']);
    }

    /**
     * @return void
     */
    public function report()
    {
        $this->startAt = intval(time() - 3600 * 2);
        $this->user();
        $this->order();
        $this->point();
        $this->buy();
    }

    /**
     * 用户
     */
    public function user()
    {
        $where = ['register_at'=>['$gte'=>$this->startAt]];

        $count = $this->userService->count($where);
        $pageSize = 1000;
        $totalPage = ceil($count / $pageSize);

        $result = [];
        for ($page = 1; $page <= $totalPage; $page++) {
            $skip = ($page - 1) * $pageSize;

            $items = $this->userService->getList($where, ['_id','register_ip','device_type','device_id','channel_name','register_at'], ['register_at'=>-1], $skip, $pageSize);
            foreach ($items as $item) {
                $result[]=value(function ()use($item){
                    DataCenterService::setSessionId();
                    DataCenterService::setClientIp($item['register_ip']);
                    DataCenterService::setDeviceType($item['device_type']);
                    DataCenterService::setUserId($item['_id']);
//                    DataCenterService::setUserAgent($_SERVER['HTTP_USER_AGENT']);
                    DataCenterService::setDeviceId($item['device_id']);
                    DataCenterService::setChannelCode($item['channel_name']??'');
//                    DataCenterService::setDataCenterPushStatus($configs['status']);

                    return DataCenterService::getReportData('user_register',[
                        'type'=>strval('deviceid'),
                        'trace_id'   => strval(DataCenterService::uuidV4()),
                        'create_time'=> intval($item['register_at']),
                    ]);
                });
            }

            LogUtil::info(sprintf(__CLASS__.' user   %s/%s', $page, $totalPage));
        }

        $result = array_chunk($result, 100);
        foreach ($result as $chunk) {
            DataCenterService::doHttpRequest($this->configs['push_url'].'/api/eventTracking/batchReport.json',$chunk);
        }
    }

    /**
     * 会员订单
     */
    public function order()
    {
        $where = ['created_at'=>['$gte'=>$this->startAt]];

        $count = $this->userOrderService->count($where);
        $pageSize = 1000;
        $totalPage = ceil($count / $pageSize);
        $result = [];///结构同埋点
        for ($page = 1; $page <= $totalPage; $page++) {
            $skip = ($page - 1) * $pageSize;
            $items = $this->userOrderService->getList($where, [], ['created_at' => -1], $skip, $pageSize);
            $userIds= array_column($items,'user_id');
            $users = [];
            if(!empty($userIds)){
                $users = $this->userService->getList(['_id'=>['$in'=>$userIds]], ['_id','register_ip','device_type','device_id','channel_name','register_at'], [], 0,count($userIds));
                $users = array_column($users,null,'_id');
            }
            foreach ($items as $item) {
                if($item['pay_id']==-1){continue;}
                $result[]=value(function ()use($item,$users){
                    $userRow = $users[$item['user_id']];
                    DataCenterService::setSessionId();
                    DataCenterService::setClientIp($item['created_ip']);
                    DataCenterService::setDeviceType($userRow['device_type']);
                    DataCenterService::setUserId($userRow['_id']);
//                    DataCenterService::setUserAgent($_SERVER['HTTP_USER_AGENT']);
                    DataCenterService::setDeviceId($userRow['device_id']);
                    DataCenterService::setChannelCode($userRow['channel_name']??'');
//                    DataCenterService::setDataCenterPushStatus($configs['status']);

                    if($item['status']==1){
                        // doVipOrderPay
                        return DataCenterService::getReportData('order_paid',[
                            'order_id'=>strval($this->configs['appid'].'_'.$item['_id']),
                            'order_type'=>'vip_subscription',
                            'product_id'=>strval($item['group_id']),
                            'amount'    =>intval($item['real_price']*100),
                            'currency'  =>'CNY',
                            'coin_quantity'=>0,
                            'vip_expiration_time'=>time()+$item['day_num']*86400,
                            'pay_type'=>$item['pay_name'],
                            'pay_channel'=>'',
                            'transaction_id'=>strval($item['trade_sn']),
                            'create_time'=>intval($item['pay_at']),
                        ]);
                    }else{
                        //doVipOrder
                        return DataCenterService::getReportData('order_created',[
                            'order_id'=>strval($this->configs['appid'].'_'.$item['_id']),
                            'order_type'=>'vip_subscription',
                            'product_id'=>strval($item['group_id']),
                            'product_name'=>strval($item['group_name']),
                            'amount'=>intval($item['price']*100),
                            'currency'  =>'CNY',
                            'coin_quantity'=>0,
                            'vip_duration_type'=>strval($item['group_id']),
                            'vip_duration_name'=>strval($item['group_name']),
                            'source_page_key'=>strval('vip'),
                            'source_page_name'=>strval('个人中心'),
                            'create_time'=>intval($item['created_at']),
                        ]);
                    }
                });
            }
            LogUtil::info(sprintf(__CLASS__.' order   %s/%s', $page, $totalPage));
        }

        $result = array_chunk($result, 100);
        foreach ($result as $chunk) {
            DataCenterService::doHttpRequest($this->configs['push_url'].'/api/eventTracking/batchReport.json',$chunk);
        }
    }

    /**
     * 金币订单
     */
    public function point()
    {
        $where = ['created_at'=>['$gte'=>$this->startAt]];

        $count = $this->rechargeService->count($where);
        $pageSize = 1000;
        $totalPage = ceil($count / $pageSize);
        $result = [];///结构同埋点
        for ($page = 1; $page <= $totalPage; $page++) {
            $skip = ($page - 1) * $pageSize;
            $items = $this->rechargeService->getList($where, [], ['created_at' => -1], $skip, $pageSize);
            $userIds= array_column($items,'user_id');
            $users = [];
            if(!empty($userIds)){
                $users = $this->userService->getList(['_id'=>['$in'=>$userIds]], ['_id','register_ip','device_type','device_id','channel_name','register_at'], [], 0,count($userIds));
                $users = array_column($users,null,'_id');
            }

            foreach ($items as $item) {
                if($item['pay_id']==-1){continue;}
                $result[]=value(function ()use($item,$users){
                    $userRow = $users[$item['user_id']];
                    DataCenterService::setSessionId();
                    DataCenterService::setClientIp($item['created_ip']);
                    DataCenterService::setDeviceType($userRow['device_type']);
                    DataCenterService::setUserId($userRow['_id']);
//                    DataCenterService::setUserAgent($_SERVER['HTTP_USER_AGENT']);
                    DataCenterService::setDeviceId($userRow['device_id']);
                    DataCenterService::setChannelCode($userRow['channel_name']??'');
//                    DataCenterService::setDataCenterPushStatus($configs['status']);

                    if($item['status']==1){
                        ///doRechargeOrderPay
                        return DataCenterService::getReportData('order_paid',[
                            'order_id'=>strval($this->configs['appid'].'_'.$item['_id']),
                            'order_type'=>'coin_purchase',
                            'product_id'=>strval($item['product_id']),
                            'amount'    =>intval($item['real_amount']*100),
                            'currency'  =>'CNY',
                            'coin_quantity'=>intval($item['num']),
                            'vip_expiration_time'=>0,
                            'pay_type'=>$item['pay_name'],
                            'pay_channel'=>'',
                            'transaction_id'=>strval($item['trade_sn']),
                            'create_time'=>intval($item['pay_at']),
                        ]);
                    }else{
                        ///doRechargeOrder
                        return DataCenterService::getReportData('order_created',[
                            'order_id'=>strval($this->configs['appid'].'_'.$item['_id']),
                            'order_type'=>'coin_purchase',
                            'product_id'=>strval($item['product_id']),
                            'product_name'=>strval($item['num'].'金币'),
                            'amount'    =>intval($item['amount']*100),
                            'currency'  =>'CNY',
                            'coin_quantity'=>intval($item['num']),
                            'vip_duration_type'=>strval($item['product_id']),
                            'vip_duration_name'=>strval($item['num'].'金币'),
                            'source_page_key'=>strval('recharge'),
                            'source_page_name'=>strval('个人中心'),
                            'create_time'=>intval($item['created_at']),
                        ]);
                    }
                });
            }
            LogUtil::info(sprintf(__CLASS__.' recharge   %s/%s', $page, $totalPage));
        }

        $result = array_chunk($result, 100);
        foreach ($result as $chunk) {
            DataCenterService::doHttpRequest($this->configs['push_url'].'/api/eventTracking/batchReport.json',$chunk);
        }
    }


    /**
     * 用户购买
     */
    public function buy()
    {
        $where = ['created_at' => ['$gte' => $this->startAt]];

        $count = $this->accountService->count($where);
        $pageSize = 1000;
        $totalPage = ceil($count / $pageSize);
        $result = [];///结构同埋点
        for ($page = 1; $page <= $totalPage; $page++) {
            $skip = ($page - 1) * $pageSize;
            $items = $this->accountService->getList($where, [], ['created_at' => -1], $skip, $pageSize);
            if (empty($items)) {
                break;
            }
            $userIds = array_column($items, 'user_id');
            $users = [];
            if (!empty($userIds)) {
                $users = $this->userService->getList(['_id' => ['$in' => $userIds]], ['_id', 'register_ip', 'device_type', 'device_id', 'channel_name', 'register_at'], [], 0, count($userIds));
                $users = array_column($users, null, '_id');
            }
            foreach ($items as $item) {
                if ($item['num'] > 0) {
                    continue;
                }
                $result[] = value(function () use ($item, $users) {
                    $userRow = $users[$item['user_id']];

                    DataCenterService::setSessionId();
                    DataCenterService::setClientIp('');
                    DataCenterService::setDeviceType($userRow['device_type']);
                    DataCenterService::setUserId($userRow['_id']);
//                    DataCenterService::setUserAgent($_SERVER['HTTP_USER_AGENT']);
                    DataCenterService::setDeviceId($userRow['device_id']);
                    DataCenterService::setChannelCode($userRow['channel_name'] ?? '');
//                    DataCenterService::setDataCenterPushStatus($configs['status']);


                    if ($item['ext'] == '礼物赠送') {
                        $buyType = 'gift_send';
                        $buyName = '礼物赠送';
                    } elseif ($item['ext'] == '视频解锁') {
                        $buyType = 'video_unlock';
                        $buyName = '视频解锁';
                    } else {
                        $buyType = 'content_purchase';
                        $buyName = '内容购买';
                    }
                    return DataCenterService::getReportData('coin_consume', [
                        'order_id' => strval($item['order_sn']),
                        'product_id' => strval($item['object_id'] ?: '9999'),///未知,固定一个
                        'product_name' => strval('金币消耗'),
                        'coin_consume_amount' => intval($item['num'] * -1),
                        'coin_balance_before' => intval($item['num_log'] - $item['num']),
                        'coin_balance_after' => intval($item['num_log']),
                        'consume_reason_key' => strval($buyType),
                        'consume_reason_name' => strval($buyName),
                        'create_time' => intval($item['created_at']),
                    ]);
                });
            }
            LogUtil::info(sprintf(__CLASS__ . ' buy   %s/%s', $page, $totalPage));
        }

        $result = array_chunk($result, 100);
        foreach ($result as $chunk) {
            DataCenterService::doHttpRequest($this->configs['push_url'] . '/api/eventTracking/batchReport.json', $chunk);
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