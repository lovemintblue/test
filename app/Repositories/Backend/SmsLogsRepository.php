<?php


namespace App\Repositories\Backend;


use App\Core\Repositories\BaseRepository;
use App\Services\SmsService;

/**
 * Class SmsLogsRepository
 * @property SmsService $smsService
 * @package App\Repositories\Backend
 */
class SmsLogsRepository extends BaseRepository
{
    /**
     * 获取列表
     * @param $request
     * @return array
     */
    public function getList($request)
    {
        $page = $this->getRequest($request, 'page', 'int', 1);
        $pageSize = $this->getRequest($request, 'pageSize', 'int', 15);
        $sort     = $this->getRequest($request, 'sort', 'string', '_id');
        $order    = $this->getRequest($request, 'order', 'int', -1);

        $query = array();
        $filter = array();

        if ($request['phone']) {
            $filter['phone'] = $this->getRequest($request, 'phone');
            $query['phone'] = $filter['phone'];
        }
        if ($request['ip']) {
            $filter['ip'] = $this->getRequest($request, 'ip');
            $query['ip'] = $filter['ip'];
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

        $count = $this->smsService->count($query);
        $items = $this->smsService->getList($query, [],array($sort => $order), $skip, $pageSize);
        foreach ($items as $index => $item) {
            $item['error_info'] = $item['error_info']?:'-';
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
}