<?php


namespace App\Repositories\Backend;


use App\Constants\CommonValues;
use App\Core\Repositories\BaseRepository;
use App\Services\UserOrderService;

/**
 * Class UserOrderRepository
 * @property UserOrderService $userOrderService
 * @package App\Repositories\Backend
 */
class UserOrderRepository extends BaseRepository
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


        if (isset($request['status']) && $request['status']!=="") {
            $filter['status'] = $this->getRequest($request, 'status');
            $query['status']  = intval($filter['status']);
        }
        if ($request['pay_id']) {
            $filter['pay_id'] = $this->getRequest($request, 'pay_id');
            $query['pay_id']  = intval($filter['pay_id']);
        }
        if ($request['user_id']) {
            $filter['user_id'] = $this->getRequest($request, 'user_id','int');
            $query['user_id']  = intval($filter['user_id']);
        }
        if ($request['username']) {
            $filter['username'] = $this->getRequest($request, 'username');
            $query['username']  = array('$regex' => $filter['username'], '$options' => 'i');
        }
        if ($request['order_sn']) {
            $filter['order_sn'] = $this->getRequest($request, 'order_sn');
            $query['order_sn']  = $filter['order_sn'];
        }
        if ($request['trade_sn']) {
            $filter['trade_sn'] = $this->getRequest($request, 'trade_sn');
            $query['trade_sn']  = $filter['trade_sn'];
        }
        if ($request['channel_name']) {
            $filter['channel_name'] = $this->getRequest($request, 'channel_name');
            $query['channel_name'] = $filter['channel_name']=='-'?'':$filter['channel_name'];
        }
        if ($request['group_id']) {
            $filter['group_id'] = $this->getRequest($request, 'group_id', 'int');
            $query['group_id']  = $filter['group_id'];
        }
        if ($request['device_type']) {
            $filter['device_type'] = $this->getRequest($request, 'device_type', 'string');
            $query['device_type']  = $filter['device_type'];
        }

        if ($request['start_time']) {
            $filter['start_time'] = $this->getRequest($request, 'start_time');
            $query['pay_at']['$gte'] = strtotime($filter['start_time']);
        }

        if ($request['end_time']) {
            $filter['end_time'] = $this->getRequest($request, 'end_time');
            $query['pay_at']['$lte'] = strtotime($filter['end_time']);
        }

        if ($request['cre_start_time']) {
            $filter['cre_start_time'] = $this->getRequest($request, 'cre_start_time');
            $query['created_at']['$gte'] = strtotime($filter['cre_start_time']);
        }
        if ($request['cre_end_time']) {
            $filter['cre_end_time'] = $this->getRequest($request, 'cre_end_time');
            $query['created_at']['$lte'] = strtotime($filter['cre_end_time']);
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
        $count = $this->userOrderService->count($query);
        $items = $this->userOrderService->getList($query, $fields, array($sort => $order), $skip, $pageSize);
        foreach ($items as $index => $item) {
            $item['created_at']      = dateFormat($item['created_at'],'m-d H:i:s');
            $item['updated_at']      = dateFormat($item['updated_at'],'m-d H:i:s');
            $item['pay_at']          = $item['pay_at']?dateFormat($item['pay_at'],'m-d H:i:s'):'-';
            $item['trade_sn']        = $item['trade_sn']?:'-';
            $item['channel_name']    = $item['channel_name']?:'-';
            $item['pay_name']        = $item['pay_name']?:'-';
            $item['status_text']     = CommonValues::getUserOrderStatus($item['status']);
            $item['price']           = formatNum($item['price'],2);
            $item['real_price']      = formatNum($item['real_price'],2);
            $items[$index] = $item;
        }
        $moneyCount=$this->userOrderService->sum([['$match'=>$query], ['$group' => ['_id' => null, 'order_money' => ['$sum' => '$real_price'],]]]);
        $query['status']=1;
        $successCount=$this->userOrderService->count($query);
        return array(
            'filter' => $filter,
            'items' => empty($items) ? array() : array_values($items),
            'count' => $count,
            'page' => $page,
            'pageSize' => $pageSize,
            'totalRow'=>[
                '_id'       =>'合计',
                'order_sn'=>"总数:{$count} 成功:{$successCount} ",
                'real_price'=>"合计: ".formatNum($moneyCount?$moneyCount->order_money:0,2),
                'pay_id'    =>"成功率: ".round($successCount/$count*100,2).'%',
            ]
        );
    }
}