<?php

declare(strict_types=1);

namespace App\Repositories\Backend;

use App\Constants\CommonValues;
use App\Core\Repositories\BaseRepository;
use App\Services\AppErrorService;

/**
 * APP错误信息
 * @package App\Repositories\Backend
 *
 * @property  AppErrorService $appErrorService
 */
class AppErrorRepository extends BaseRepository
{
    /**
     * 获取列表
     * @param $request
     * @return array
     */
    public function getList($request)
    {
        $page       = $this->getRequest($request, 'page', 'int', 1);
        $pageSize   = $this->getRequest($request, 'pageSize', 'int', 15);

        $query  = array();
        $filter = array();

        if ($request['device_type']) {
            $filter['device_type'] = $this->getRequest($request, 'device_type');
            $query['device_type']  = $filter['device_type'];
        }
        if ($request['device_version']) {
            $filter['device_version'] = $this->getRequest($request, 'device_version');
            $query['device_version']  = $filter['device_version'];
        }

        if ($query['ip']) {
            $filter['ip'] = $this->getRequest($request, 'ip');
            $query['ip']  = $filter['ip'];
        }
        if ($request['start_time']) {
            $filter['start_time'] = $this->getRequest($request, 'start_time');
            $query['created_at']['$gte'] = strtotime($filter['start_time']);
        }
        if ($request['end_time']) {
            $filter['end_time'] = $this->getRequest($request, 'end_time');
            $query['created_at'] = ['$lte' => strtotime($filter['end_time'])];
        }
        $skip   = ($page - 1) * $pageSize;
        $fields = array();
        $count  = $this->appErrorService->count($query);
        $items  = $this->appErrorService->getList($query, $fields, array('created_at' => -1), $skip, $pageSize);
        foreach ($items as $index => $item) {
            $item['created_at']         = dateFormat($item['created_at']);
            $item['updated_at']         = dateFormat($item['updated_at']);
            $item['device_type_text']          = CommonValues::getDeviceTypes($item['device_type']);
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

}