<?php


namespace App\Jobs\Jms;

use App\Models\AppLogModel;
use App\Utils\CommonUtil;
use App\Utils\LogUtil;

/**
 * 日活上报
 * Class JmsDayJob
 * @property AppLogModel $appLogModel
 * @package App\Jobs\Jms
 */
class JmsDayJob extends JmsJob
{
    public function handler($uniqid)
    {
        // TODO: Implement handler() method.
        $dayNum = ceil((CommonUtil::getTodayEndTime() + 1 - $this->startAt) / 24 / 3600);
        $query = ['created_at' => ['$gte' => $this->startAt]];
        //如果时间间隔是当天则可以使用日期索引加速查询
        if ($dayNum == 1) {
            $query['date'] = date('Y-m-d');
        }
        $count = $this->appLogModel->count($query);
        $pageSize = 500;
        $totalPage = ceil($count / $pageSize);
        for ($page = 1; $page <= $totalPage; $page++) {
            $skip = ($page - 1) * $page;
            $items = $this->appLogModel->find($query, array(), array('created_at' => -1), $skip, $pageSize);
            $data = array();
            foreach ($items as $item) {
                $data[] = [
                    'user_id'   => strval($item['user_id']),
                    'login_ip'  => strval($item['ip']),
                    'login_at'  => strval($item['created_at']),
                    'app_id'    => $this->appid,
                ];
            }
            $result = $this->doHttpRequest($data,'/report/day');
            LogUtil::info("Day report page:{$page}/{$totalPage} result:" . ($result ? "ok" : "error"));
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