<?php


namespace App\Repositories\Backend;


use App\Constants\CommonValues;
use App\Core\Repositories\BaseRepository;
use App\Services\CollectionsService;

/**
 * Class CollectionsRepository
 * @property CollectionsService $collectionsService
 * @package App\Repositories\Backend
 */
class CollectionsRepository extends BaseRepository
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
        $sort     = $this->getRequest($request, 'sort', 'string', '_id');
        $order    = $this->getRequest($request, 'order', 'int', -1);
        $query = array();
        $filter = array();


        if ($request['user_id']) {
            $filter['user_id'] = $this->getRequest($request, 'user_id','int');
            $query['user_id']  = $filter['user_id'];
        }

        if ($request['channel_name']) {
            $filter['channel_name'] = $this->getRequest($request, 'channel_name','string');
            $query['channel_name'] = $filter['channel_name']=='-'?'':$filter['channel_name'];
        }

        if ($request['device_type']) {
            $filter['device_type'] = $this->getRequest($request, 'device_type', 'string');
            $query['device_type']  = $filter['device_type'];
        }
        if ($request['pay_id']) {
            $filter['pay_id'] = $this->getRequest($request, 'pay_id','int');
            $query['pay_id']  = $filter['pay_id'];
        }

        if ($request['pay_name']) {
            $filter['pay_name'] = $this->getRequest($request, 'pay_name');
            $query['pay_name'] = array('$regex' => $filter['pay_name'], '$options' => 'i');
        }

        if ($request['record_type']) {
            $filter['record_type'] = $this->getRequest($request, 'record_type');
            $query['record_type']  = $filter['record_type'];
        }
        if ($request['device_type']) {
            $filter['device_type'] = $this->getRequest($request, 'device_type');
            $query['device_type']  = $filter['device_type'];
        }
        if ($request['order_sn']) {
            $filter['order_sn'] = $this->getRequest($request, 'order_sn');
            $query['order_sn']  = $filter['order_sn'];
        }
        if ($request['trade_sn']) {
            $filter['trade_sn'] = $this->getRequest($request, 'trade_sn');
            $query['trade_sn']  = $filter['trade_sn'];
        }

        if ($request['start_time']) {
            $filter['start_time'] = $this->getRequest($request, 'start_time');
            $query['pay_at']['$gte'] = strtotime($filter['start_time']);
        }

        if ($request['end_time']) {
            $filter['end_time'] = $this->getRequest($request, 'end_time');
            $query['pay_at']['$lte'] = strtotime($filter['end_time']);
        }
        if ($request['reg_start_time']) {
            $filter['reg_start_time'] = $this->getRequest($request, 'reg_start_time');
            $query['register_at']['$gte'] = strtotime($filter['reg_start_time']);
        }
        if ($request['reg_end_time']) {
            $filter['reg_end_time'] = $this->getRequest($request, 'reg_end_time');
            $query['register_at']['$lte'] = strtotime($filter['reg_end_time']);
        }
        $skip = ($page - 1) * $pageSize;
        $fields = array();
        $count = $this->collectionsService->count($query);
        $items = $this->collectionsService->getList($query, $fields, array($sort => $order), $skip, $pageSize);
        foreach ($items as $index => $item) {
            $item['created_at']         = dateFormat($item['created_at'],'m-d H:i:s');
            $item['updated_at']         = dateFormat($item['updated_at'],'m-d H:i:s');
            $item['register_at']        = dateFormat($item['register_at'],'m-d H:i:s');
            $item['pay_at']             = $item['pay_at']?dateFormat($item['pay_at'],'m-d H:i:s'):'-';
            $item['pay_date']           = $item['pay_date'] ?: '-';
            $item['pay_name']           = $item['pay_name'] ?: '-';
            $item['channel_name']       = $item['channel_name']?:'-';

            $item['record_type_text']   = CommonValues::getAccountRecordType($item['record_type']);
            $item['price']              = formatNum($item['price'],2);
            $item['real_price']         = formatNum($item['real_price'],2);
            $items[$index] = $item;
        }
        $moneyCount=$this->collectionsService->sum([['$match'=>$query], ['$group' => ['_id' => null, 'order_money' => ['$sum' => '$real_price'],]]]);
        return array(
            'filter' => $filter,
            'items' => empty($items) ? array() : array_values($items),
            'count' => $count,
            'page' => $page,
            'pageSize' => $pageSize,
            'totalRow'=>[
                '_id'       =>'合计',
                'channel_name'=>"总数: {$count}",
                'real_price'=>"合计: ".formatNum($moneyCount?$moneyCount->order_money:0,2),
            ]
        );
    }
}