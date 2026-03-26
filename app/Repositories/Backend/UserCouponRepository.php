<?php

declare(strict_types=1);

namespace App\Repositories\Backend;

use App\Constants\CommonValues;
use App\Constants\StatusCode;
use App\Core\Repositories\BaseRepository;
use App\Exception\BusinessException;
use App\Services\AdminUserService;
use App\Services\UserCouponService;

/**
 * 观影券
 * @package App\Repositories\Backend
 *
 * @property  UserCouponService $userCouponService
 * @property  AdminUserService $adminUserService
 */
class UserCouponRepository extends BaseRepository
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
        if (isset($request['type']) && $request['type']!=="") {
            $filter['type'] = $this->getRequest($request, 'type', 'string');
            $query['type'] = $filter['type'];
        }
        if ($request['code_key']) {
            $filter['code_key'] = $this->getRequest($request, 'code_key');
            $query['code_key'] = $filter['code_key'];
        }
        if ($request['code']) {
            $filter['code'] = $this->getRequest($request, 'code');
            $query['code'] = $filter['code'];
        }
        if ($request['user_id']) {
            $filter['user_id'] = $this->getRequest($request, 'user_id', 'int');
            $query['user_id'] = $filter['user_id'];
        }

        $skip = ($page - 1) * $pageSize;
        $fields = array();
        $count = $this->userCouponService->count($query);
        $items = $this->userCouponService->getList($query, $fields, array($sort => $order), $skip, $pageSize);
        foreach ($items as $index => $item) {
            $item['expired_at']  = dateFormat($item['expired_at']);
            $item['created_at']  = dateFormat($item['created_at']);
            $item['updated_at']  = dateFormat($item['updated_at']);
            $item['label']       = $item['label']?:'-';
            $item['status_text'] = CommonValues::getUserCodeStatus($item['status']);
            $item['type_text']   = CommonValues::getCouponType($item['type']);
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
     * @param bool $isAdmin
     * @return bool
     * @throws BusinessException
     */
    public function save($data,$isAdmin=false)
    {
        $row = array(
            'name'        => $this->getRequest($data, 'name','string'),
            'money'       => $this->getRequest($data, 'money', 'int'),
            'type'        => $this->getRequest($data, 'type', 'string', ''),
            'user_id'     => $this->getRequest($data, 'user_id', 'int'),
            'num'         => $this->getRequest($data, 'num', 'int', 1),
            'can_use_num' => $this->getRequest($data, 'can_use_num', 'int', 1),
            'expired_at'  => $this->getRequest($data, 'expired_at'),
            'status'      => 0,
        );
        $row['name']       = $row['name']?:($row['type']=='movie'?'观影券':'裸聊券').$row['money'].'元';
        if (empty($row['name']) || empty($row['money'])|| empty($row['user_id'])|| empty($row['type'])) {
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '参数错误!');
        }
        if ($data['_id'] > 0) {
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '观影券不能修改,如需修改只能联系技术!');
        }
        $this->userCouponService->save($row,$isAdmin);

        return true;
    }

    /**
     * 更新数据
     * @param $data
     * @return bool|int|mixed
     */
    public function update($data)
    {
        return $this->userCouponService->save($data,true);
    }



}