<?php


namespace App\Repositories\Backend;


use App\Constants\CommonValues;
use App\Constants\StatusCode;
use App\Core\Repositories\BaseRepository;
use App\Exception\BusinessException;
use App\Services\UserActService;

/**
 * Class UserActRepository
 * @property UserActService $userActService
 * @package App\Repositories\Backend
 */
class UserActRepository extends BaseRepository
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
            $query['user_id']  = intval($filter['user_id']);
        }
        if ($request['username']) {
            $filter['username'] = $this->getRequest($request, 'username');
            $query['username']  = array('$regex' => $filter['username'], '$options' => 'i');
        }
        if (isset($request['is_valid']) && $request['is_valid'] !== "") {
            $filter['is_valid'] = $this->getRequest($request, 'is_valid', 'int');
            $query['is_valid'] = $filter['is_valid'];
        }
        if ($request['channel_name']) {
            $filter['channel_name'] = $this->getRequest($request, 'channel_name');
            $query['channel_name'] = $filter['channel_name']=='-'?'':$filter['channel_name'];
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
        $count = $this->userActService->count($query);
        $items = $this->userActService->getList($query, $fields, array($sort => $order), $skip, $pageSize);
        foreach ($items as $index => $item) {
            $item['channel_name']    = $item['channel_name']?:'-';
            $item['is_valid_text']   = $item['is_valid']?'是':'否';
            $item['created_at']      = dateFormat($item['created_at']);
            $item['register_at']     = dateFormat($item['register_at']);
            $items[$index] = $item;
        }
        $pipeline = [];
        if(!empty($query)){
            $pipeline[] = ['$match' => $query];
        }
        $pipeline[] = ['$group' => value(function(){
            $group = ['_id' => null];
            foreach(CommonValues::getUserActs() as $key=>$val){
                $group[$key] = ['$sum'=>'$act.'.$key];
            }
            return $group;
        })];
        $aggregate=$this->userActService->userActModel->aggregate($pipeline);
        return array(
            'filter' => $filter,
            'items' => empty($items) ? array() : array_values($items),
            'count' => $count,
            'page' => $page,
            'pageSize' => $pageSize,
            'totalRow'=>value(function()use($aggregate){
                $totalRow = ['user_id' => '合计'];
                $aggregate = (array)$aggregate;
                foreach($aggregate as $key=>$value){
                    $totalRow['act.'.$key] = $value;
                }
                return $totalRow;
            })
        );
    }

}