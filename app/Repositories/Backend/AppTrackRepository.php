<?php

declare(strict_types=1);

namespace App\Repositories\Backend;

use App\Constants\CommonValues;
use App\Constants\StatusCode;
use App\Core\Repositories\BaseRepository;
use App\Exception\BusinessException;
use App\Services\AdminUserService;
use App\Services\AppTrackService;

/**
 * 应用统计分析
 *
 * @property  AppTrackService $appTrackService
 * @property  AdminUserService $adminUserService
 */
class AppTrackRepository extends BaseRepository
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
        $sort     = $this->getRequest($request, 'sort', 'string', 'click');
        $order    = $this->getRequest($request, 'order', 'int', -1);

        $query = array();
        $filter = array();


        if ($request['id']) {
            $filter['id'] = $this->getRequest($request, 'id', 'string');
            $query['id'] = strval($filter['id']);
        }
        if ($request['type']) {
            $filter['type'] = $this->getRequest($request, 'type', 'string');
            $query['type'] = $filter['type'];
        }
        if ($request['name']) {
            $filter['name'] = $this->getRequest($request, 'name', 'string');
            $query['name']  = array('$regex' => $filter['name'], '$options' => 'i');
        }
        if ($request['date']) {
            $filter['date'] = $this->getRequest($request, 'date', 'string');
            $query['date'] = $filter['date'];
        }

        $skip = ($page - 1) * $pageSize;
        $fields = array();
        $count = $this->appTrackService->count($query);
        $items = $this->appTrackService->getList($query, $fields, array($sort => $order), $skip, $pageSize);
        foreach ($items as $index => $item) {
            $item['id'] = $item['id']?:'-';
            $item['name'] = $item['name']?:'-';
            $item['type'] = CommonValues::getAppTrackTypes($item['type']);
            $item['created_at'] = dateFormat($item['created_at']);
            $item['updated_at'] = dateFormat($item['updated_at']);
            $item['num']      = formatNum($item['num'],2);
            $items[$index] = $item;
        }
        $countInfo=$this->appTrackService->appTrackModel->aggregate([
            [
                '$match'=>$query
            ],
            [
                '$group' => [
                    '_id' => null,
                    'num' => ['$sum' => '$num'],
                ]
            ]
        ]);
        return array(
            'filter' => $filter,
            'items' => empty($items) ? array() : array_values($items),
            'count' => $count,
            'page' => $page,
            'pageSize' => $pageSize,
            'totalRow'=>[
                '_id'=>'合计',
                'object_type'=>'-',
                'object_id'=>'-',
                'object_name'=>'-',
                'date_label'=>'-',
                'num'=>formatNum($countInfo->num,2),
                'updated_at'=>'-',
                'created_at'=>'-',
            ]
        );
    }


}