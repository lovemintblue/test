<?php

declare(strict_types=1);

namespace App\Repositories\Backend;

use App\Core\Repositories\BaseRepository;
use App\Services\AdminUserService;
use App\Services\CommonService;
use App\Utils\CommonUtil;


/**
 * 系统用户
 * @package App\Repositories\Backend
 *
 * @property  AdminUserService $adminUserService
 * @property  CommonService $commonService
 */
class AdminLogRepository extends BaseRepository
{
    /**
     * 获取日志列表
     * @param $request
     * @return array
     */
    public function getLogList($request)
    {

        $page = $this->getRequest($request, 'page', 'int', 1);
        $pageSize = $this->getRequest($request, 'pageSize', 'int', 15);
        $sort     = $this->getRequest($request, 'sort', 'string', '_id');
        $order    = $this->getRequest($request, 'order', 'int', -1);

        $query = array();
        $filter = array();

        if ($request['admin_id']) {
            $filter['admin_id'] = $this->getRequest($request, 'admin_id', 'int');
            $query['admin_id'] = $filter['admin_id'];
        }

        if ($request['start_time']) {
            $filter['start_time'] = $this->getRequest($request, 'start_time');
            $query['created_at']['$gte'] = strtotime($filter['start_time']);
        }
        if ($request['end_time']) {
            $filter['end_time'] = $this->getRequest($request, 'end_time');
            $query['created_at']['$lte'] = strtotime($filter['end_time']);
        }

        $skip = ($page - 1) * $pageSize;
        $fields = array();
        $count = $this->adminUserService->countLog($query);
        $items = $this->adminUserService->getLogList($query, $fields, array($sort => $order), $skip, $pageSize);
        foreach ($items as $index => $item) {
            $item['created_at'] = dateFormat($item['created_at']);
            $item['updated_at'] = dateFormat($item['updated_at']);
            $items[$index] = $item;
        }

        return array(
            'filter' => $filter,
            'items' => empty($items) ? array() : array_values($items),
            'count' => $count,
            'page' => $page,
            'pageSize' => $pageSize
        );
    }

    /**
     * @param int $days
     * @return bool|mixed
     */
    public function delLogs($days = 30)
    {
        $todayTime = CommonUtil::getTodayZeroTime();
        $delEndTime = $todayTime - 86400 * $days;

        return $this->adminUserService->deleteLog(["date_time" => ['$lt' => $delEndTime]]);
    }

}



