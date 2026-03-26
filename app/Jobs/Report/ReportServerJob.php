<?php

namespace App\Jobs\Report;

use App\Constants\CommonValues;
use App\Jobs\BaseJob;
use App\Models\AppLogModel;
use App\Models\ChannelReportModel;
use App\Models\CollectionsModel;
use App\Models\ReportLogModel;
use App\Services\AgentSystemService;
use App\Services\ChannelReportService;
use App\Services\ChannelService;
use App\Services\ReportAdvAppLogService;
use App\Services\ReportAdvLogService;
use App\Services\UserService;
use App\Utils\CommonUtil;
use App\Utils\LogUtil;

/**
 * 自身统计
 * Class ReportServerJob
 * @property ChannelService $channelService
 * @property UserService $userService
 * @property AppLogModel $appLogModel
 * @property ChannelReportModel $channelReportModel
 * @property CollectionsModel $collectionsModel
 * @property ReportLogModel $reportLogModel
 * @property AgentSystemService $agentSystemService
 * @property ChannelReportService $channelReportService
 * @property ReportAdvLogService $reportAdvLogService
 * @property ReportAdvAppLogService $reportAdvAppLogService
 * @package App\Jobs\Report
 */
class ReportServerJob extends BaseJob
{
    public function handler($uniqid)
    {
        // TODO: Implement handler() method.
        $channels = $this->channelService->getAll();
        $this->doCounter();
        for ($i = 0; $i < 2; $i++) {
            $startTime = strtotime(date("Y-m-d", strtotime("-{$i}day")));
            $this->doUR(1, $startTime);
            $this->doUR(3, $startTime);
            $this->doUR(7, $startTime);
            $this->doUR(15, $startTime);
            $this->doUR(30, $startTime);
        }
        if (date('H') == '00') {
            $this->doChannel($channels, 2);
        } else {
            $this->doChannel($channels, 1);
        }
        //同步渠道系统统计数据
        $this->getDayLogs($channels);
    }

    /**
     * 统计日活等
     * @param int $maxDay
     */
    public function doCounter($maxDay = 2)
    {
        $nowTime     = time();
        $endTime     = CommonUtil::getTodayEndTime();
        $date        = date('Y-m-d', $endTime);
        $deviceTypes = array('android', 'h5');
        foreach ($deviceTypes as $deviceType) {
            LogUtil::info(sprintf('Starting count device type %s', $deviceType));
            $type    = 'device_type_' . $deviceType;
            $count   = $this->userService->count(array('device_type' => $deviceType));
            $idValue = md5($date . '_' . $type);
            $data    = array(
                'type'       => $type,
                'value'      => $count * 1,
                'date'       => $date,
                'created_at' => strtotime($date),
                'updated_at' => $nowTime
            );
            $this->reportLogModel->findAndModify(array('_id' => $idValue), $data, array('_id'), true);
        }
        LogUtil::info('Starting count user total');
        $userTotal = $this->userService->count(array());
        $type      = 'user_total';
        $idValue   = md5($date . '_' . $type);
        $data      = array(
            'type'       => $type,
            'value'      => $userTotal * 1,
            'date'       => $date,
            'created_at' => strtotime($date),
            'updated_at' => $nowTime
        );
        $this->reportLogModel->findAndModify(array('_id' => $idValue), $data, array('_id'), true);

        LogUtil::info('Starting has buy user total');
        $userTotal = $this->userService->count(array('has_buy' => 1));
        $type      = 'user_total_has_buy';
        $idValue   = md5($date . '_' . $type);
        $data      = array(
            'type'       => $type,
            'value'      => $userTotal * 1,
            'date'       => $date,
            'created_at' => strtotime($date),
            'updated_at' => $nowTime
        );
        $this->reportLogModel->findAndModify(array('_id' => $idValue), $data, array('_id'), true);

        //统计日活和注册
        for ($i = $maxDay; $i >= 0; $i--) {
            $startTime = $endTime - 3600 * 24;
            LogUtil::info(sprintf('Starting count %s==>%s', date('Y-m-d H:i:s', $startTime), date('Y-m-d H:i:s', $endTime)));
            $date    = date('Y-m-d', $endTime);
            $count   = $this->userService->count(array('register_date' => $date));
            $type    = 'user_reg';
            $idValue = md5($date . '_' . $type);
            $data    = array(
                'type'       => $type,
                'value'      => $count * 1,
                'date'       => $date,
                'created_at' => strtotime($date),
                'updated_at' => $nowTime
            );
            $this->reportLogModel->findAndModify(array('_id' => $idValue), $data, array('_id'), true);

            //统计金币和vip充值
            $this->doCountMoney('money', $date);
            //统计游戏充值
            $this->doCountMoney('game', $date);

            $count   = $this->appLogModel->count(array('date' => $date));
            $type    = 'app_day';
            $idValue = md5($date . '_' . $type);
            $data    = array(
                'type'       => $type,
                'value'      => $count * 1,
                'date'       => $date,
                'created_at' => strtotime($date),
                'updated_at' => $nowTime
            );
            $this->reportLogModel->findAndModify(array('_id' => $idValue), $data, array('_id'), true);
            $endTime = $startTime;
        }
    }

    /**
     * 统计留存率
     * @param $day
     * @param int $startTime 基准时间
     */
    public function doUR($day, $startTime)
    {
        // TODO:次日留存率、三日留存率、周留存率、半月留存率和月留存率
        $date = date("Y-m-d", $startTime - 3600 * 24 * $day);

        //指定日期注册用户,基准时间是否活跃
        $total       = $this->userService->count(['register_date' => $date]);
        $activeTotal = $this->userService->count(['register_date' => $date, 'last_date' => date("Y-m-d", $startTime)]);
        LogUtil::info($date . " reg:{$total} active:{$activeTotal}");
        $data = array(
            '_id'        => md5($date . '_retention_' . $day),
            'type'       => "retention_{$day}",
            'value'      => strval($total > 0 ? number_format($activeTotal / $total * 100, 2) : 0),
            'date'       => date("Y-m-d", $startTime),//统计日期
            'created_at' => time(),
            'updated_at' => time(),
        );
        $this->reportLogModel->findAndModify(array('_id' => $data['_id']), $data, array('_id'), true);
    }

    /**
     * 渠道统计
     * @param $channels
     * @param $maxDay
     */
    public function doChannel($channels, $maxDay = 2)
    {
        foreach ($channels as $item) {

            $channel   = $item['code'];
            $startTime = CommonUtil::getTodayZeroTime();
            for ($day = 1; $day <= $maxDay; $day++) {
                $date = date('Y-m-d', $startTime);
                LogUtil::info(sprintf('Do counter channel %s=>%s', $channel, $date));

                //用户注册
                $androidReg = $this->userService->count(['channel_name' => $channel, 'register_date' => $date, 'device_type' => 'android']);
                $h5Reg      = $this->userService->count(['channel_name' => $channel, 'register_date' => $date, 'device_type' => 'h5']);
                $userReg    = $androidReg + $h5Reg;
                //有效注册
                $androidRegValid = $this->userService->count(['channel_name' => $channel, 'register_date' => $date, 'device_type' => 'android', 'is_valid' => 1]);
                $h5RegValid      = $this->userService->count(['channel_name' => $channel, 'register_date' => $date, 'device_type' => 'h5', 'is_valid' => 1]);
                $userRegValid    = $androidRegValid + $h5RegValid;
                //无效注册
                $androidRegInvalid = $androidReg - $androidRegValid;
                $h5RegInvalid      = $h5Reg - $h5RegValid;
                $userRegInvalid    = $userReg - $userRegValid;

                //会员充值
                $orderCount = $this->doCounterChannelOrder(array('channel_name' => $channel, 'record_type' => 'vip', 'pay_date' => $date));
                //金币充值
                $pointCount = $this->doCounterChannelOrder(array('channel_name' => $channel, 'record_type' => 'point', 'pay_date' => $date));
                //新用户订单
                $orderCountNew = $this->doCounterChannelOrder(array(
                    'channel_name' => $channel,
                    'record_type'  => array('$ne' => 'game'),
                    'pay_date'     => $date,
                    'is_new_user'  => 1
                ));;

                //总日活
                $androidAppDay = $this->appLogModel->count(['date' => $date, 'channel_name' => $channel, 'device_type' => 'android']);
                $h5AppDay      = $this->appLogModel->count(['date' => $date, 'channel_name' => $channel, 'device_type' => 'h5']);
                $appDay        = $androidAppDay + $h5AppDay;
                //当日日活
                $androidAppDayNew = $this->appLogModel->count(['date' => $date, 'channel_name' => $channel, 'device_type' => 'android', 'register_date' => $date]);
                $h5AppDayNew      = $this->appLogModel->count(['date' => $date, 'channel_name' => $channel, 'device_type' => 'h5', 'register_date' => $date]);
                $appDayNew        = $androidAppDayNew + $h5AppDayNew;
                //次日总留存
                $androidAppDayYesterday = $androidAppDay - $androidAppDayNew;
                $h5AppDayYesterday      = $h5AppDay - $h5AppDayNew;
                $appDayYesterday        = $androidAppDayYesterday + $h5AppDayYesterday;

                //次日转换
                $orderCountOld = ($orderCount + $pointCount) - $orderCountNew;

                $data = array(
                    'code'                      => $channel,
                    'date'                      => $date,
                    'user_reg'                  => intval($userReg),
                    'android_reg'               => intval($androidReg),
                    'h5_reg'                    => intval($h5Reg),
                    'user_reg_valid'            => intval($userRegValid),
                    'android_reg_valid'         => intval($androidRegValid),
                    'h5_reg_valid'              => intval($h5RegValid),
                    'user_reg_invalid'          => intval($userRegInvalid),
                    'android_reg_invalid'       => intval($androidRegInvalid),
                    'h5_reg_invalid'            => intval($h5RegInvalid),
                    'app_day'                   => $appDay,//日活
                    'today_app_day'             => intval($appDayNew),//当日日活
                    'android_today_app_day'     => intval($androidAppDayNew),
                    'h5_today_app_day'          => intval($h5AppDayNew),
                    'yesterday_app_day'         => intval($appDayYesterday),//次日总日活
                    'android_yesterday_app_day' => intval($androidAppDayYesterday),
                    'h5_yesterday_app_day'      => intval($h5AppDayYesterday),
                    'order_num'                 => $orderCount,//会员充值
                    'point_num'                 => $pointCount,//金币充值
                    'today_order_num'           => $orderCountNew,
                    'yesterday_order_num'       => $orderCountOld,//次日订单
                    'game_order_num'            => 0,//游戏订单
                    'today_game_order_num'      => 0,
                    'yesterday_game_order_num'  => 0,//次日游戏转换
//                    'ip'                        => $this->channelReportService->getIPCount($channel, $date),
//                    'uv'                        => $this->channelReportService->getUVCount($channel, $date),
//                    'pv'                        => $this->channelReportService->getPVCount($channel, $date),
                    'view'                      => $this->channelReportService->getViewCount($channel, $date),
                    'adv'                       => $this->reportAdvLogService->getFieldCount($date, 'click', $channel),
                    'adv_app'                   => $this->reportAdvAppLogService->getFieldCount($date, 'click', $channel),
                    'created_at'                => $startTime,
                    'updated_at'                => time()
                );

                //留存
                $days = CommonValues::getAppDay();
                foreach ($days as $key => $val) {
                    $data['android_app_day' . $key] = $this->appLogModel->count(['date' => $date, 'channel_name' => $channel, 'device_type' => 'android', 'register_date' => date('Y-m-d', $startTime - $key * 24 * 60 * 60)]);
                    $data['h5_app_day' . $key]      = $this->appLogModel->count(['date' => $date, 'channel_name' => $channel, 'device_type' => 'h5', 'register_date' => date('Y-m-d', $startTime - $key * 24 * 60 * 60)]);
                    $data['app_day' . $key]         = $data['android_app_day' . $key] + $data['h5_app_day' . $key];
                }

                $idValue = md5($channel . '_' . $date);
                $this->channelReportModel->findAndModify(array('_id' => $idValue), $data, array(), true);
                $startTime -= 24 * 3600;
            }
        }
    }

    /**
     * 统计金额
     * @param $type
     * @param $date
     */
    public function doCountMoney($type, $date)
    {
        $query = array(
            'pay_date' => $date
        );
        if ($type == 'money') {
            $query['record_type'] = array('$ne' => 'game');
        } else {
            $query['record_type'] = 'game';
        }
        $query  = [
            ['$match' => $query],
            ['$group' => ['_id' => null, 'total_money' => ['$sum' => '$real_price'], 'count_num' => ['$sum' => 1]]]
        ];
        $result = $this->collectionsModel->aggregate($query);
        $money  = 0;
        $count  = 0;
        if ($result) {
            $money = $result->total_money;
            $count = $result->count_num;
        }
        $idValue = md5($date . '_' . $type);
        $data    = array(
            'type'       => $type,
            'value'      => $count . '|' . $money,
            'date'       => $date,
            'created_at' => strtotime($date),
            'updated_at' => time()
        );
        $this->reportLogModel->findAndModify(array('_id' => $idValue), $data, array('_id'), true);
    }

    /**
     * 统计总数
     * @param $query
     * @return int
     */
    public function doCounterChannelOrder($query)
    {
        $query      = [
            ['$match' => $query],
            ['$group' => ['_id' => null, 'total_money' => ['$sum' => '$real_price'], 'count_num' => ['$sum' => 1]]]
        ];
        $result     = $this->collectionsModel->aggregate($query);
        $totalMoney = 0;
        if ($result) {
            $totalMoney = $result->total_money;
        }
        return $totalMoney;
    }

    /**
     * @param $channels
     * @param $day
     * @return void
     */
    public function getDayLogs($channels, $day = 2)
    {
        if (empty($channels)) {
            return;
        }
        $channels = array_column($channels, 'code');
        for ($i = 0; $i < $day; $i++) {
            $date  = date('Y-m-d', strtotime("-{$i} day"));
            $items = $this->agentSystemService->getDayLogs([
                'date'         => $date,
                'channel_code' => '',
            ]);
            foreach ($items as $item) {
                LogUtil::info(sprintf('Do counter channel %s=>%s', $item['channel'], $date));
                $saveData = [
                    'pv'                     => intval($item['pv']),
                    'uv'                     => intval($item['uv']),
                    'ip'                     => intval($item['ip']),
                    'click_android'          => intval($item['click_android']),
                    'click_ios'              => intval($item['click_ios']),
                    'total_user_reg'         => intval($item['total_user_reg']),
                    'total_ios_user_reg'     => intval($item['total_ios_user_reg']),
                    'total_android_user_reg' => intval($item['total_android_user_reg']),
                    'total_order_money'      => intval($item['total_order_money']),
                    'today_order_money'      => intval($item['today_order_money']),
                    'urr_1'                  => intval($item['urr_1']),
                    'urr_3'                  => intval($item['urr_3']),
                    'urr_7'                  => intval($item['urr_7']),
                    'count_time'             => strtotime($item['count_time']),
                ];
                if ($item['channel'] == '_all') {
                    $type    = 'web_log';
                    $idValue = md5($date . '_' . $type);
                    $data    = array(
                        'type'       => $type,
                        'value'      => json_encode($saveData),
                        'date'       => $date,
                        'created_at' => strtotime($date),
                        'updated_at' => time()
                    );
                    $this->reportLogModel->findAndModify(array('_id' => $idValue), $data, array('_id'), true);
                }
                if (!in_array($item['channel'], $channels)) {
                    continue;
                }
                $this->channelReportModel->updateRaw(['$set' => $saveData], ['_id' => md5(trim($item['channel']) . '_' . $date)]);
            }

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