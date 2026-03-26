<?php


namespace App\Jobs\Jms;


use App\Services\UserService;
use App\Utils\CommonUtil;
use App\Utils\LogUtil;

/**
 * 上报用户
 * Class JmsUserJob
 * @property UserService $userService
 * @package App\Jobs\Jms
 */
class JmsUserJob extends JmsJob
{
    public function handler($uniqid)
    {
        // TODO: Implement handler() method.
        $dayNum = ceil((CommonUtil::getTodayEndTime() + 1 - $this->startAt) / 24 / 3600);
        $query = ['updated_at' => ['$gte' => $this->startAt]];
        //如果时间间隔是当天则可以使用日期索引加速查询
//        if ($dayNum == 1) {
//            $query['last_date'] = array(
//                '$gte' => CommonUtil::getTodayZeroTime() - 24 * 3600
//            );
//        }
        $count = $this->userService->count($query);
        $pageSize = 2000;
        $totalPage = ceil($count / $pageSize);
        for ($page = 1; $page <= $totalPage; $page++) {
            $skip = ($page - 1) * $page;
            $items = $this->userService->getList($query, array(), array('_id' => -1), $skip, $pageSize);
            $data = array();
            foreach ($items as $item) {
                $data[] = [
                    'user_id'   => $item['_id'],
                    'username'  => $item['username'],
                    'nickname'  => $item['username'],
                    'channel'   => strval($item['channel_name']),
                    'reg_at'    => $item['register_at'],
                    'reg_ip'    => $item['register_ip'],
                    'login_at'  => $item['last_at'],
                    'login_ip'  => $item['last_ip'],
                    'is_vip'    => $this->userService->isVip($item)?'y':'n',
                    'balance'   => $item['balance'],
                    'vip_start_time' => $item['group_start_time'],
                    'vip_end_time' => $item['group_end_time'],
                    'app_id'    => $this->appid,
                    'device_type' => $item['device_type'],
                    'device_id' => $item['device_id'],
                    'is_disable'=> $item['is_disabled'] ? 'y' : 'n'
                ];
            }
            $result = $this->doHttpRequest($data,'/report/user');
            LogUtil::info("User report page:{$page}/{$totalPage} result:" . ($result ? "ok" : "error"));
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