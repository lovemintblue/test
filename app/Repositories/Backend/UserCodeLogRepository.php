<?php

declare(strict_types=1);

namespace App\Repositories\Backend;

use App\Constants\CommonValues;
use App\Core\Repositories\BaseRepository;
use App\Services\UserCodeLogService;
use App\Services\UserGroupService;
use App\Services\ProductService;

/**
 * 兑换码日志
 * @package App\Repositories\Backend
 *
 * @property  UserCodeLogService $userCodeLogService
 * @property  UserGroupService $userGroupService
 * @property  ProductService $productService
 */
class UserCodeLogRepository extends BaseRepository
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
        $sort     = $this->getRequest($request, 'sort', 'string', '_id');
        $order    = $this->getRequest($request, 'order', 'int', -1);
        $query  = array();
        $filter = array();

        if ($request['type']) {
            $filter['type'] = $this->getRequest($request, 'type', 'string');
            $query['type'] = $filter['type'];
        }
        if ($request['user_id']) {
            $filter['user_id'] = $this->getRequest($request, 'user_id','int');
            $query['user_id']  = $filter['user_id'];
        }
        if ($request['group_id']) {
            $filter['group_id'] = $this->getRequest($request, 'group_id','int');
            $query['group_id']  = $filter['group_id'];
        }
        if ($request['username']) {
            $filter['username'] = $this->getRequest($request, 'username');
            $query['username']  = array('$regex' => $filter['username'], '$options' => 'i');
        }
        if ($request['name']) {
            $filter['name'] = $this->getRequest($request, 'name');
            $query['name']  = array('$regex' => $filter['name'], '$options' => 'i');
        }
        if ($request['code'] != null) {
            $filter['code'] = $this->getRequest($request, 'code');
            $query['code']  = $filter['code'];
        }
        $userGroups = $this->userGroupService->getAll();
        $productGroups = $this->productService->getAll();
        $skip   = ($page - 1) * $pageSize;
        $fields = array();
        $count  = $this->userCodeLogService->count($query);
        $items  = $this->userCodeLogService->getList($query, $fields, array($sort => $order), $skip, $pageSize);
        foreach ($items as $index => $item) {
            $item['created_at']     = dateFormat($item['created_at']);
            $item['updated_at']     = dateFormat($item['updated_at']);
            $item['user_group']     = $userGroups[$item['group_id']]['name'];
            $item['type_text']      = CommonValues::getUserCodeType($item['type']);
            $item['object_name']    = $item['type']=='point'?$productGroups[$item['object_id']]['name']:$userGroups[$item['object_id']]['name'];
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