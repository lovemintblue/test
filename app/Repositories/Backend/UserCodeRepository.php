<?php

declare(strict_types=1);

namespace App\Repositories\Backend;

use App\Constants\CommonValues;
use App\Constants\StatusCode;
use App\Core\Repositories\BaseRepository;
use App\Exception\BusinessException;
use App\Services\AdminUserService;
use App\Services\UserCodeService;
use App\Services\UserGroupService;
use App\Services\ProductService;

/**
 * 兑换码管理
 * @package App\Repositories\Backend
 *
 * @property  UserCodeService $userCodeService
 * @property  UserGroupService $userGroupService
 * @property  ProductService $productService
 * @property  AdminUserService $adminUserService
 */
class UserCodeRepository extends BaseRepository
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

        if ($request['name']) {
            $filter['name'] = $this->getRequest($request, 'name');
            $query['name'] = array('$regex' => $filter['name'], '$options' => 'i');
        }
        if (isset($request['status']) && $request['status']!=="") {
            $filter['status'] = $this->getRequest($request, 'status', 'int');
            $query['status'] = $filter['status'];
        }
        if ($request['code_key']) {
            $filter['code_key'] = $this->getRequest($request, 'code_key');
            $query['code_key'] = $filter['code_key'];
        }
        if ($request['code']) {
            $filter['code'] = $this->getRequest($request, 'code');
            $query['code'] = $filter['code'];
        }
        if ($request['type']) {
            $filter['type'] = $this->getRequest($request, 'type', 'string');
            $query['type'] = $filter['type'];
        }

        $userGroups = $this->userGroupService->getAll();
        $productGroups = $this->productService->getAll();
        $skip = ($page - 1) * $pageSize;
        $fields = array();
        $count = $this->userCodeService->count($query);
        $items = $this->userCodeService->getList($query, $fields, array($sort => $order), $skip, $pageSize);
        foreach ($items as $index => $item) {
            $item['expired_at']  = dateFormat($item['expired_at']);
            $item['created_at']  = dateFormat($item['created_at']);
            $item['updated_at']  = dateFormat($item['updated_at']);
            $item['status_text'] = CommonValues::getUserCodeStatus($item['status']);
            $item['type_text']   = CommonValues::getUserCodeType($item['type']);
            $item['object_name'] = $item['type']=='point'?$productGroups[$item['object_id']]['name']:$userGroups[$item['object_id']]['name'];
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
     * 保存数据
     * @param $data
     * @return bool|int|mixed
     * @throws BusinessException
     */
    public function save($data)
    {
        $row = array(
            'name' => $this->getRequest($data, 'name'),
            'type' => $this->getRequest($data, 'type', 'string', ''),
            'object_id' => $this->getRequest($data, 'vip_id', 'int', 0),
            'num' => $this->getRequest($data, 'num', 'int', 1),
            'can_use_num' => $this->getRequest($data, 'can_use_num', 'int', 1),
            'add_num' => $this->getRequest($data, 'add_num', 'int', 1),
            'expired_at' => $this->getRequest($data, 'expired_at'),
            'status' => 0,
        );
        if($row['type']=='point'){
            $row['object_id'] = $this->getRequest($data, 'coin_id', 'int', 0);
        }
        if (empty($row['name']) || empty($row['num']) || empty($row['object_id']) || empty($row['can_use_num'])
            || empty($row['add_num']) || empty($row['expired_at'])) {
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '参数错误!');
        }

        if ($data['_id'] > 0) {
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '兑换码不能修改,如需修改只能联系技术!');
        }
        $this->userCodeService->save($row);
        $this->adminUserService->addAdminLog(sprintf('新增兑换码:%s 个数%s 天数%s', $row['name'], $row['num'], $row['day_num']));
        return true;
    }

    /**
     * 更新数据
     * @param $data
     * @return bool|int|mixed
     */
    public function update($data)
    {
        return $this->userCodeService->save($data);
    }

}