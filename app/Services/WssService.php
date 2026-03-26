<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Services\BaseService;
use App\Models\AppLogModel;
use App\Models\RechargeModel;
use App\Models\UserModel;
use App\Models\UserOrderModel;
use App\Models\WithdrawModel;
use App\Utils\CommonUtil;
use App\Utils\LogUtil;

/**
 * 数据分析系统对接
 * Class MssService
 * @package App\Services
 * @property CommonService $commonService
 * @property AppLogModel $appLogModel
 * @property UserService $userService
 * @property UserModel $userModel
 * @property UserOrderModel $userOrderModel
 * @property RechargeModel $rechargeModel
 * @property WithdrawModel $withdrawModel
 */
class WssService extends BaseService
{
    const  ACTION_QUEUE = 'user_action_queue';

    /**
     * 获取开始时间
     * @param $startTime
     * @return false|int
     */
    public function getStartTime($startTime = null)
    {
        if (empty($startTime)) {
            $startTime = time() - 30;
        } else if ($startTime == 'all') {
            $startTime = null;
        } else {
            $startTime = strtotime($startTime);
        }
        return $startTime;
    }


    /**
     * 上报日活
     * @param $startTime
     */
    public function doAppLogs($startTime)
    {
        LogUtil::info('Start do app logs...');
        $startDayTime = intval(strtotime(date('Y-m-d 00:00:00', $startTime)));
        $endDayTime = CommonUtil::getTodayEndTime() + 1;
        $maxDay = ceil(($endDayTime - $startDayTime) / 24 / 3600);
        for ($day = 1; $day <= $maxDay; $day++) {
            $query = array('date' => date('Y-m-d', $startDayTime), 'created_at' => array('$gte' => $startTime));
            $count = $this->appLogModel->count($query);
            $pageSize = 2000;
            $totalPage = ceil($count / $pageSize);
            for ($page = 1; $page <= $totalPage; $page++) {
                LogUtil::info(sprintf('Build app logs data %s=>%s/%s', date('Y-m-d', $startDayTime), $page, $totalPage));
                $skip = ($page - 1) * $pageSize;
                $items = $this->appLogModel->find($query, array(), array(), $skip, $pageSize);
                $data = array();
                foreach ($items as $item) {
                    $userInfo = $this->userService->getInfoFromCache($item['user_id']);
                    $row = array(
                        'id' => $item['_id'],
                        'user_id' => intval($item['user_id']),
                        'channel' => strval($userInfo['channel_name']),
                        'username' => strval($userInfo['username']),
                        'reg_at' => intval($userInfo['register_at']),
                        'time' => intval($item['created_at']),
                        'ip' => strval($item['ip']),
                        'device' => strval($userInfo['device_type']),
                        'device_version' => strval($userInfo['device_version']),
                    );
                    $data[] = $row;
                }
                $result = $this->doRequest('/app/logs', $data);
                LogUtil::info(sprintf('Post app logs data %s %s=>%s/%s', $result ? 'ok' : 'error', date('Y-m-d', $startDayTime), $page, $totalPage));
            }
            $startDayTime += 24 * 3600;
        }
    }

    /**
     * 上报用户
     * @param $startTime
     */
    public function doUsers($startTime = null)
    {
        LogUtil::info('Start report user ...');
        $query = array();
        if ($startTime) {
            $startDayTime = intval(strtotime(date('Y-m-d 00:00:00', $startTime)));
            $query = array('last_date' => array('$gte' => $startDayTime - 24 * 3600 * 2), 'updated_at' => array('$gte' => $startTime));
        }
        $count = $this->userModel->count($query);
        $pageSize = 2000;
        $totalPage = ceil($count / $pageSize);
        for ($page = 1; $page <= $totalPage; $page++) {
            LogUtil::info(sprintf('Post user data %s/%s', $page, $totalPage));
            $skip = ($page - 1) * $pageSize;
            $items = $this->userModel->find($query, array(), array(), $skip, $pageSize);
            $data = array();
            foreach ($items as $item) {
                $row = array(
                    'user_id' => intval($item['_id']),
                    'username' => strval($item['username']),
                    'nickname' => strval($item['nickname']),
                    'channel' => strval($item['channel_name']),
                    'phone' => strval($item['phone']),
                    'parent_user_id' => intval($item['parent_id']),
                    'parent_username' => strval($item['parent_name']),
                    'reg_at' => intval($item['register_at']),
                    'reg_ip' => strval($item['register_ip']),
                    'login_at' => intval($item['last_at']),
                    'login_ip' => strval($item['last_ip']),
                    'is_vip' => $item['group_id'] > 0 ? 'y' : 'n',
                    'balance' => intval($item['balance'] / 10),
                    'vip_start_time' => intval($item['group_start_time']),
                    'vip_end_time' => intval($item['group_end_time']),
                    'device_type' => strval($item['device_type']),
                    'device_id' => strval($item['device_id']),
                    'device_version' => strval($item['device_version']),
                    'is_disable' => $item['is_disabled'] ? 'y' : 'n',
                    'updated_at' => intval($item['updated_at']),
                    'created_at' => intval($item['created_at'])
                );
                if ($row['vip_start_time'] < time()) {
                    $row['vip_end_time'] = 0;
                    $row['vip_start_time'] = 0;
                    $row['is_vip'] = 'n';
                }
                $data[] = $row;
            }
            $result = $this->doRequest('/app/users', $data);
            LogUtil::info(sprintf('Post user data %s %s/%s', $result ? 'ok' : 'error', $page, $totalPage));
        }
    }

    /**
     * 上报用户订单
     * @param $startTime
     */
    public function doUserOrders($startTime = null)
    {
        LogUtil::info('Start report user order ...');
        $query = array();
        if ($startTime) {
            $startDayTime = intval(strtotime(date('Y-m-d 00:00:00', $startTime)));
            $query = array('created_at' => array('$gte' => $startDayTime - 5 * 24 * 3600), 'updated_at' => array('$gte' => $startTime));
        }
        $count = $this->userOrderModel->count($query);
        $pageSize = 2000;
        $totalPage = ceil($count / $pageSize);
        for ($page = 1; $page <= $totalPage; $page++) {
            LogUtil::info(sprintf('Post user order data %s/%s', $page, $totalPage));
            $skip = ($page - 1) * $pageSize;
            $items = $this->userOrderModel->find($query, array(), array(), $skip, $pageSize);
            $data = array();
            foreach ($items as $item) {
                $userInfo = $this->userService->getInfoFromCache($item['user_id']);
                //部分数据在更换数据源的时候丢失
                if (empty($item['created_at'])) {
                    $item['created_at'] = $item['register_at'];
                    $item['updated_at'] = $item['created_at'];
                }
                $row = array(
                    'order_sn' => strval($item['order_sn']),
                    'type' => 'vip',
                    'user_id' => intval($item['user_id']),
                    'username' => strval($userInfo['username']),
                    'amount' => doubleval($item['price']),
                    'real_amount' => doubleval($item['real_price']),
                    'trade_sn' => strval($item['trade_sn']),
                    'status' => intval($item['status']),
                    'pay_at' => intval($item['pay_at']),
                    'payment_id' => intval($item['pay_id']),
                    'created_at' => intval($item['created_at']),
                    'updated_at' => !empty($item['pay_at']) ? intval($item['pay_at']) : intval($item['updated_at']),
                    'channel' => strval($userInfo['channel_name']),
                    'reg_at' => intval($userInfo['register_at']),
                    'reg_ip' => strval($userInfo['register_ip']),
                    'ip' => strval($item['ip']),
                );
                $data[] = $row;
            }
            $result = $this->doRequest('/app/order', $data);
            LogUtil::info(sprintf('Post user order data %s %s/%s', $result ? 'ok' : 'error', $page, $totalPage));
        }
    }

    /**
     * 充值
     * @param $startTime
     */
    public function doRecharge($startTime = null)
    {
        LogUtil::info('Start report recharge  ...');
        $query = array('record_type' => 'point');
        if ($startTime) {
            $startDayTime = intval(strtotime(date('Y-m-d 00:00:00', $startTime)));
            $query = array('record_type' => 'point', 'created_at' => array('$gte' => $startDayTime - 5 * 24 * 3600), 'updated_at' => array('$gte' => $startTime));
        }
        $count = $this->rechargeModel->count($query);
        $pageSize = 2000;
        $totalPage = ceil($count / $pageSize);
        for ($page = 1; $page <= $totalPage; $page++) {
            LogUtil::info(sprintf('Post report  data %s/%s', $page, $totalPage));
            $skip = ($page - 1) * $pageSize;
            $items = $this->rechargeModel->find($query, array(), array(), $skip, $pageSize);
            $data = array();
            foreach ($items as $item) {
                $userInfo = $this->userService->getInfoFromCache($item['user_id']);
                //部分数据在更换数据源的时候丢失
                if (empty($item['created_at'])) {
                    $item['created_at'] = $item['register_at'];
                    $item['updated_at'] = $item['created_at'];
                }
                $row = array(
                    'order_sn' => strval($item['order_sn']),
                    'type' => 'point',
                    'user_id' => intval($item['user_id']),
                    'username' => strval($userInfo['username']),
                    'amount' => doubleval($item['amount']),
                    'real_amount' => doubleval($item['real_amount']),
                    'trade_sn' => strval($item['trade_sn']),
                    'status' => intval($item['status']),
                    'pay_at' => intval($item['pay_at']),
                    'payment_id' => intval($item['pay_id']),
                    'created_at' => intval($item['created_at']),
                    'updated_at' => !empty($item['pay_at']) ? intval($item['pay_at']) : intval($item['updated_at']),
                    'channel' => strval($userInfo['channel_name']),
                    'reg_at' => intval($userInfo['register_at']),
                    'reg_ip' => strval($userInfo['register_ip']),
                    'ip' => strval($item['ip']),
                );
                $data[] = $row;
            }
            $result = $this->doRequest('/app/order', $data);
            LogUtil::info(sprintf('Post report data %s %s/%s', $result ? 'ok' : 'error', $page, $totalPage));
        }
    }

    /**
     * 游戏充值
     * @param $startTime
     */
    public function doGameRecharge($startTime = null)
    {
        LogUtil::info('Start report game recharge  ...');
        $query = array('record_type' => 'game');
        if ($startTime) {
            $startDayTime = intval(strtotime(date('Y-m-d 00:00:00', $startTime)));
            $query = array('record_type' => 'game', 'created_at' => array('$gte' => $startDayTime - 5 * 24 * 3600), 'updated_at' => array('$gte' => $startTime));
        }
        $count = $this->rechargeModel->count($query);
        $pageSize = 2000;
        $totalPage = ceil($count / $pageSize);
        for ($page = 1; $page <= $totalPage; $page++) {
            LogUtil::info(sprintf('Post report game data %s/%s', $page, $totalPage));
            $skip = ($page - 1) * $pageSize;
            $items = $this->rechargeModel->find($query, array(), array(), $skip, $pageSize);
            $data = array();
            foreach ($items as $item) {
                $userInfo = $this->userService->getInfoFromCache($item['user_id']);
                //部分数据在更换数据源的时候丢失
                if (empty($item['created_at'])) {
                    $item['created_at'] = $item['register_at'];
                    $item['updated_at'] = $item['created_at'];
                }
                $row = array(
                    'order_sn' => strval($item['order_sn']),
                    'type' => 'game',
                    'user_id' => intval($item['user_id']),
                    'username' => strval($userInfo['username']),
                    'amount' => doubleval($item['amount']),
                    'real_amount' => doubleval($item['real_amount']),
                    'trade_sn' => strval($item['trade_sn']),
                    'status' => intval($item['status']),
                    'pay_at' => intval($item['pay_at']),
                    'payment_id' => intval($item['pay_id']),
                    'created_at' => intval($item['created_at']),
                    'updated_at' => !empty($item['pay_at']) ? intval($item['pay_at']) : intval($item['updated_at']),
                    'channel' => strval($userInfo['channel_name']),
                    'reg_at' => intval($userInfo['register_at']),
                    'reg_ip' => strval($userInfo['register_ip']),
                    'ip' => strval($item['ip']),
                );
                $data[] = $row;
            }
            $result = $this->doRequest('/app/gameOrder', $data);
            LogUtil::info(sprintf('Post report game data %s %s/%s', $result ? 'ok' : 'error', $page, $totalPage));
        }
    }

    /**
     * app状态
     */
    public function doAppStatus()
    {
        LogUtil::info('Start report app status  ...');
        $nowTime = time();
        $todayStartTime = CommonUtil::getTodayZeroTime();
        if (($nowTime - $todayStartTime) < 15 * 60) {
            $nowTime = $todayStartTime;
            $todayStartTime = $todayStartTime - 3600 * 24;
        }
        $data = array(
            'user_count' => $this->userModel->count(),
            'user_count_today' => $this->userModel->count(array('register_date' => $todayStartTime)),
            'order_num' =>0,
            'order_success_num' => 0,
            'time' => $nowTime,
            'game_order_num' => 0,
            'game_order_success_num' => 0
        );
        $result = $this->doRequest('/app/status', $data);
        LogUtil::info(sprintf('Post report app status %s', $result ? 'ok' : 'error'));
    }


    /**
     * 收益提现
     * @param $startTime
     */
    public function doWithdraw($startTime = null)
    {
        LogUtil::info('Start report  withdraw  ...');
//        $query = array('record_type' => 'point');
        $query = array('record_type' => ['$in'=>['writer','agent']]);
        if ($startTime) {
            $startDayTime = intval(strtotime(date('Y-m-d 00:00:00', $startTime)));
//            $query = array('record_type' => 'point', 'created_at' => array('$gte' => $startDayTime - 5 * 24 * 3600), 'updated_at' => array('$gte' => $startTime));
            $query = array('record_type' => ['$in'=>['writer','agent']], 'created_at' => array('$gte' => $startDayTime - 5 * 24 * 3600), 'updated_at' => array('$gte' => $startTime));
        }
        $count = $this->withdrawModel->count($query);
        $pageSize = 2000;
        $totalPage = ceil($count / $pageSize);
        for ($page = 1; $page <= $totalPage; $page++) {
            LogUtil::info(sprintf('Post report  withdraw data %s/%s', $page, $totalPage));
            $skip = ($page - 1) * $pageSize;
            $items = $this->withdrawModel->find($query, array(), array(), $skip, $pageSize);
            $data = array();
            foreach ($items as $item) {
                $userInfo = $this->userService->getInfoFromCache($item['user_id']);
                $row = array(
                    'id' => intval($item['user_id']),
                    'user_id' => intval($item['user_id']),
                    'username' => strval($userInfo['username']),
                    'channel' => strval($userInfo['channel_name']),
                    'money' => doubleval($item['num']),//人民币
                    'fee' => doubleval($item['fee']),//人民币
                    'status' => intval($item['status']),
                    'account' => strval($item['account']),
                    'account_name' => strval($item['account_name']),
                    'type' => strval($item['method']),
                    'bank_info' => strval($item['bank_name']),
                    'remark' => strval($item['error_msg']),
                    'action_at' => empty($item['status']) ? 0 : intval($item['updated_at']),
                    'ip' => strval($item['ip']),
                    'created_at' => intval($item['created_at']),
                    'updated_at' => intval($item['updated_at']),
                );
                $data[] = $row;
            }
            $result = $this->doRequest('/app/withdraw', $data);
            LogUtil::info(sprintf('Post report withdraw data %s %s/%s', $result ? 'ok' : 'error', $page, $totalPage));
        }
    }

    /**
     * 获取13位时间戳
     * @return int
     */
    public function getMicroTime()
    {
        list($usec, $sec) = explode(" ", microtime());
        return intval(($usec + $sec) * 1000);
    }

    /**
     * 获取日志编号
     * @return string
     */
    public function getLogId()
    {
        $configs = $this->commonService->getConfigs();
        return $configs['wss_app_id'] . '-' . $this->getMicroTime() . '-' . mt_rand(1000, 9999);
    }


    /**
     * 加入日志
     * @param $userId
     * @param $event
     * @param $eventData
     * @param $eventIp
     */
    public function joinActionQueue($userId, $event, $eventData, $eventIp)
    {
        $data = array(
            'id' => $this->getLogId(),
            'user_id' => $userId,
            'event' => $event,
            'event_data' => $eventData,
            'ip' => $eventIp,
            'time' => time(),
        );
        $this->getRedis()->lPush(self::ACTION_QUEUE, $data);
    }


    /**
     * 处理队列
     */
    public function runQueue()
    {
        $items = array();
        $startTime = time();
        while (true) {
            $item = $this->getRedis()->rPop(self::ACTION_QUEUE);
            if (empty($item)) {
                break;
            }
            if (empty($item['time']) || $item['time'] < (time() - 3600 * 3)) {
                continue;
            }
            $userInfo = $this->userService->getInfoFromCache($item['user_id']);
            $items[] = array(
                'user_id' => $item['user_id'] * 1,
                'user_name' => strval($userInfo['username']),
                'reg_at' => intval($userInfo['register_at']),
                'channel' => strval($userInfo['channel_name']),
                'time' => intval($item['time']),
                'ip' => strval($item['ip']),
                'event' => strval($item['event']),
                'event_data' => strval($item['event_data']),
                'id' => strval($item['id']),
            );
            //两个条件退出 程序满30秒  数据满5000条
            if ((time() - $startTime) > 30) {
                break;
            }
            if (count($items) >= 5000) {
                break;
            }
        }
        if (empty($items)) {
            LogUtil::info('Queue is empty!');
            return;
        }
        $result = $this->doRequest('/app/actions', $items);
        LogUtil::info(sprintf('Post report action %s=>%s', $result ? 'ok' : 'error', count($items)));
    }

    /**
     * 加密
     * @param string $str 要加密的数据
     * @param string $key
     * @return bool|string   加密后的数据
     */
    public function encryptRaw($str, $key)
    {
        return openssl_encrypt($str, 'AES-128-ECB', $key, OPENSSL_RAW_DATA);
    }

    /**
     * 解密
     * @param string $str 要解密的数据
     * @param string $key
     * @return string        解密后的数据
     */
    public function decryptRaw($str, $key)
    {
        return openssl_decrypt($str, 'AES-128-ECB', $key, OPENSSL_RAW_DATA);
    }


    /**
     * 网络请求
     * @param $path
     * @param $data
     * @return mixed|null
     */
    public function doRequest($path, $data)
    {
        $configs = $this->commonService->getConfigs();
        if (empty($configs['wss_url']) || empty($configs['wss_app_id']) || empty($configs['wss_app_key']) || empty($configs['wss_app_common_key'])) {
            LogUtil::error('Please config wss!');
            return null;
        }
        $data = array('data' => $data);
        $data = json_encode($data);
        $data = $this->encryptRaw($data, $configs['wss_app_key']);
        $header = array(
            'version:1.0',
            'time:' . date('Y-m-d H:i:s'),
            'appid:' . $configs['wss_app_id']
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $configs['wss_url'] . $path);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $result = curl_exec($ch);
        curl_close($ch);
        $result = empty($result) ? null : $this->decryptRaw($result, $configs['wss_app_common_key']);
        if (empty($result)) {
            LogUtil::error('Network error!');
            return null;
        }
        $result = json_decode($result, true);
        if ($result['status'] == 'y') {
            return empty($result['data']) ? true : $result['data'];
        }
        LogUtil::error($result['error']);
        return null;
    }

}