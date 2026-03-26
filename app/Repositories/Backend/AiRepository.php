<?php


namespace App\Repositories\Backend;


use App\Constants\CommonValues;
use App\Constants\StatusCode;
use App\Core\Repositories\BaseRepository;
use App\Exception\BusinessException;
use App\Services\AiService;
use App\Services\UserService;

/**
 * Class AiRepository
 * @property AiService $aiService
 * @property UserService $userService
 * @package App\Repositories\Backend
 */
class AiRepository extends BaseRepository
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


        if (isset($request['status']) && $request['status']!=="") {
            $filter['status'] = $this->getRequest($request, 'status');
            $query['status']  = intval($filter['status']);
        }
        if (isset($request['is_disabled']) && $request['is_disabled']!=="") {
            $filter['is_disabled'] = $this->getRequest($request, 'is_disabled');
            $query['is_disabled']  = intval($filter['is_disabled']);
        }
        if (isset($request['position']) && $request['position']!=="") {
            $filter['position'] = $this->getRequest($request, 'position');
            $query['position']  = strval($filter['position']);
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
        if ($request['channel_name']) {
            $filter['channel_name'] = $this->getRequest($request, 'channel_name');
            $query['channel_name']  = $filter['channel_name'];
        }
        if ($request['device_type']) {
            $filter['device_type'] = $this->getRequest($request, 'device_type', 'string');
            $query['device_type']  = $filter['device_type'];
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
        $count = $this->aiService->count($query);
        $items = $this->aiService->getList($query, $fields, array($sort => $order), $skip, $pageSize);
        foreach ($items as $index => $item) {
            $user = $this->userService->findByID($item['user_id']);
            $item['user_id']    = $user['_id'];
            $item['nickname']   = $user['nickname'];
            $item['head']       = $user['img'];
            $item['is_system']  = $user['is_system'];
            $item['user_sex']   = CommonValues::getUserSex($user['sex']);
            $item['group_name'] = $this->userService->isVip($user)?$user['group_name']:'-';

            $item['channel_name']    = $item['channel_name']?:'-';
            $item['error_msg']       = $item['error_msg']?:'-';
            $item['position']        = CommonValues::getAiPosition($item['position']);
            $item['created_at']      = dateFormat($item['created_at'],'m-d H:i:s');
            $item['updated_at']      = in_array($item['status'],[-1,1])?dateFormat($item['updated_at'],'m-d H:i:s'):'-';
            $item['status_text']     = CommonValues::getAiStatus($item['status']);
//            $item['money']           = formatNum($item['money'],2);
//            $item['real_money']      = formatNum($item['real_money'],2);
            $item['is_disabled']     = CommonValues::getIsDisabled($item['is_disabled']);
            $items[$index] = $item;
        }
        $pipeline = [];
        if(!empty($query)){
            $pipeline[] = ['$match' => $query];
        }
        $pipeline[] = ['$group' => ['_id' => '$status', 'order_money' => ['$sum' => '$real_money'],'order_count' => ['$sum'=>1]]];
        $aggregates=$this->aiService->aggregates($pipeline);
        $totalCount = $totalMoney = 0;
        $summary = [];
        foreach($aggregates as $aggregate){
//            $aggregate->order_money = formatNum($aggregate->order_money,2);
            $totalCount+=$aggregate->order_count;
            $totalMoney+=$aggregate->order_money;
            $summary[$aggregate->_id] = [
                'order_money'=>$aggregate->order_money,
                'order_count'=>$aggregate->order_count
            ];
        }
        return array(
            'filter' => $filter,
            'items' => empty($items) ? array() : array_values($items),
            'count' => $count,
            'page' => $page,
            'pageSize' => $pageSize,
            'summary_count' => "总数:{$totalCount}  成功:".intval($summary[1]['order_count'])."  退款:".intval($summary[-1]['order_count'])." 异常:".intval($summary[0]['order_count']),
            'summary_money' => "总金币:{$totalMoney}  成功金币:".floatval($summary[1]['order_money'])."  退款金币:".floatval($summary[-1]['order_money'])." 异常金币:".floatval($summary[0]['order_money']),
            'totalRow'=>[
                'real_money'=>"合计: ".$totalMoney,
            ]
        );
    }

    /**
     * 获取详情
     * @param $id
     * @return mixed
     * @throws BusinessException
     */
    public function getDetail($id)
    {
        $row = $this->aiService->findByID($id);
        if (empty($row)) {
            throw  new BusinessException(StatusCode::DATA_ERROR, '数据不存在!');
        }
        $row['extra'] = (array)$row['extra'];
        $row['position_text'] = CommonValues::getAiPosition($row['position']);
        return $row;
    }
}