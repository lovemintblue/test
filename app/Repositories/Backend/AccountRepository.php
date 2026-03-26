<?php


namespace App\Repositories\Backend;


use App\Constants\CommonValues;
use App\Core\Repositories\BaseRepository;
use App\Services\AccountService;
use App\Services\CreditLogService;

/**
 * Class AccountLogRepository
 * @property AccountService $accountService
 * @property CreditLogService $creditLogService
 * @package App\Repositories\Backend
 */
class AccountRepository extends BaseRepository
{
    /**
     * 获取列表
     * @param $request
     * @return array
     */
    public function getList($request)
    {
        $page       = $this->getRequest($request, 'page', 'int', 1);
        $pageSize   = $this->getRequest($request, 'pageSize', 'int', 10);
        $sort     = $this->getRequest($request, 'sort', 'string', '_id');
        $order    = $this->getRequest($request, 'order', 'int', -1);

        $query  = array();
        $filter = array();

        if ($request['user_id']) {
            $filter['user_id'] = $this->getRequest($request, 'user_id','int');
            $query['user_id']  = $filter['user_id'];
        }
        if ($request['order_sn']) {
            $filter['order_sn'] = $this->getRequest($request, 'order_sn');
            $query['order_sn']  = $filter['order_sn'];
        }
        if ($request['type']) {
            $filter['type'] = $this->getRequest($request, 'type','int');
            $query['type']  = $filter['type'];
        }
        if ($request['record_type']) {
            $filter['record_type'] = $this->getRequest($request, 'record_type');
            $query['record_type']  = $filter['record_type'];
        }
        if ($request['start_time']) {
            $filter['start_time'] = $this->getRequest($request, 'start_time');
            $query['created_at']['$gte'] = strtotime($filter['start_time']);
        }

        if ($request['end_time']) {
            $filter['end_time'] = $this->getRequest($request, 'end_time');
            $query['created_at']['$lte'] = strtotime($filter['end_time']);
        }

        $skip   = ($page - 1) * $pageSize;
        $fields = array();

        $count  = $this->accountService->count($query);
        $items = $this->accountService->getList($query, $fields, array($sort => $order), $skip, $pageSize);
        foreach ($items as $index => $item) {
            $item['created_at']         = dateFormat($item['created_at']);
            $item['updated_at']         = dateFormat($item['updated_at']);
            $item['record_type_text']   = CommonValues::getAccountRecordType($item['record_type']);
            $item['type_text']          = CommonValues::getAccountLogsType($item['type']);
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
     * 积分明细列表
     * @param $request
     * @return array
     */
    public function getCreditList($request)
    {
        $page       = $this->getRequest($request, 'page', 'int', 1);
        $pageSize   = $this->getRequest($request, 'pageSize', 'int', 10);
        $sort     = $this->getRequest($request, 'sort', 'string', '_id');
        $order    = $this->getRequest($request, 'order', 'int', -1);

        $query  = array();
        $filter = array();

        if ($request['user_id']) {
            $filter['user_id'] = $this->getRequest($request, 'user_id','int');
            $query['user_id']  = $filter['user_id'];
        }
        if ($request['type']) {
            $filter['type'] = $this->getRequest($request, 'type','int');
            $query['type']  = $filter['type'];
        }
        if ($request['start_time']) {
            $filter['start_time'] = $this->getRequest($request, 'start_time');
            $query['created_at']['$gte'] = strtotime($filter['start_time']);
        }
        if ($request['end_time']) {
            $filter['end_time'] = $this->getRequest($request, 'end_time');
            $query['created_at']['$lte'] = strtotime($filter['end_time']);
        }

        $skip   = ($page - 1) * $pageSize;
        $fields = array();

        $count  = $this->creditLogService->count($query);
        $items = $this->creditLogService->getList($query, $fields, array($sort => $order), $skip, $pageSize);
        foreach ($items as $index => $item) {
            $item['created_at']         = dateFormat($item['created_at']);
            $item['updated_at']         = dateFormat($item['updated_at']);
            $item['type_text']          = CommonValues::getCreditType($item['type']);
            $item['object_text']        = '-';
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