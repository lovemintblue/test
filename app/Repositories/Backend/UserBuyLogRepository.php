<?php


namespace App\Repositories\Backend;


use App\Core\Repositories\BaseRepository;
use App\Services\UserBuyLogService;
use App\Utils\LogUtil;


/**
 * Class UserBuyLogRepository
 * @property UserBuyLogService $userBuyLogService
 * @package App\Repositories\Backend
 */
class UserBuyLogRepository extends BaseRepository
{
    /**
     * 获取列表
     * @param $request
     * @return array
     */
    public function getList($request)
    {
        $page = $this->getRequest($request, 'page', 'int', 1);
        $pageSize = $this->getRequest($request, 'pageSize', 'int', 30);
        $sort     = $this->getRequest($request, 'sort', 'string', 'created_at');
        $order    = $this->getRequest($request, 'order', 'int', -1);
        $query = array();
        $filter = array();


        if ($request['user_id']) {
            $filter['user_id'] = $this->getRequest($request, 'user_id','int');
            $query['user_id']  = $filter['user_id'];
        }
        if ($request['username']) {
            $filter['username'] = $this->getRequest($request, 'username','string');
            $query['username']  = $filter['username'];
        }

        if ($request['channel_name']) {
            $filter['channel_name'] = $this->getRequest($request, 'channel_name','string');
            $query['channel_name']  = $filter['channel_name'];
        }

        if ($request['object_id']) {
            $filter['object_id'] = $this->getRequest($request, 'object_id','int');
            $query['object_id']  = $filter['object_id'];
        }

        if ($request['object_type']) {
            $filter['object_type'] = $this->getRequest($request, 'object_type');
            $query['object_type']  = $filter['object_type'];
        }
        if ($request['order_sn']) {
            $filter['order_sn'] = $this->getRequest($request, 'order_sn');
            $query['order_sn']  = $filter['order_sn'];
        }
        if ($request['start_time']) {
            $filter['start_time'] = $this->getRequest($request, 'start_time');
            $query['created_at']['$gte'] = intval(strtotime($filter['start_time']));
        }

        if ($request['end_time']) {
            $filter['end_time'] = $this->getRequest($request, 'end_time');
            $query['created_at']['$lte'] = intval(strtotime($filter['end_time']));
        }
        $skip = ($page - 1) * $pageSize;
        $fields = array();
        $count = $this->userBuyLogService->count($query);
        $items = $this->userBuyLogService->getList($query, $fields, array($sort => $order), $skip, $pageSize);
        foreach ($items as $index => $item) {
            $item['created_at']         = dateFormat($item['created_at'],'m-d H:i:s');
            $item['updated_at']         = dateFormat($item['updated_at'],'m-d H:i:s');
            $item['register_at']        = dateFormat($item['register_at'],'m-d H:i:s');
            $item['channel_name']       = $item['channel_name']?:'-';

            $item['object_money']       = formatNum($item['object_money'],2);
            $item['object_money_old']   = formatNum($item['object_money_old'],2);
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