<?php

declare(strict_types=1);

namespace App\Repositories\Backend;

use App\Constants\CommonValues;
use App\Constants\StatusCode;
use App\Core\Repositories\BaseRepository;
use App\Exception\BusinessException;
use App\Services\UserBalanceService;
use App\Services\UserService;

/**
 * 用户余额
 * @package App\Repositories\Backend
 *
 * @property  UserBalanceService $userBalanceService
 * @property  UserService $userService
 */
class UserBalanceRepository extends BaseRepository
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

        if ($request['_id']) {
            $filter['_id'] = $this->getRequest($request, '_id','int');
            $query['_id']  = intval($filter['_id']);
        }
        if ($request['username']) {
            $filter['username'] = $this->getRequest($request, 'username');
            $query['username']  = array('$regex' => $filter['username'], '$options' => 'i');
        }
        if ($request['device_type']) {
            $filter['device_type'] = $this->getRequest($request, 'device_type', 'string');
            $query['device_type']  = $filter['device_type'];
        }
        if ($request['type']) {
            $filter['type'] = $this->getRequest($request, 'type', 'string');
            $query['type']  = $filter['type'];
        }
        if ($request['channel_name']) {
            $filter['channel_name'] = $this->getRequest($request, 'channel_name');
            $query['channel_name']  = $filter['channel_name'];
        }
        if (isset($request['status']) && $request['status']!=="") {
            $filter['status'] = $this->getRequest($request, 'status');
            $query['status']  = intval($filter['status']);
        }

        $skip   = ($page - 1) * $pageSize;
        $fields = array();
        $count  = $this->userBalanceService->count($query);
        $items  = $this->userBalanceService->getList($query, $fields, array($sort => $order), $skip, $pageSize);
        foreach ($items as $index => $item) {
            $user = $this->userService->findByID($item['_id']);
            $item['user_id']    = $user['_id'];
            $item['nickname']   = $user['nickname'];
            $item['head']       = $user['img'];
            $item['is_system']  = $user['is_system'];
            $item['user_sex']   = CommonValues::getUserSex($user['sex']);
            $item['group_name'] = $this->userService->isVip($user)?$user['group_name']:'-';
            $item['channel_name']    = $item['channel_name']?:'-';
            $item['balance_text'] = CommonValues::getBalanceTypes($item['type']);
            $item['error_msg']       = $item['error_msg']?:'-';
            $item['created_at']      = dateFormat($item['created_at'],'m-d H:i:s');
            $item['updated_at']      = in_array($item['status'],[3])?dateFormat($item['updated_at'],'m-d H:i:s'):'-';
            $item['status_text']     = CommonValues::getBalanceStatus($item['status']);
            $items[$index] = $item;
        }

        return array(
            'filter'    => $filter,
            'items'     => empty($items) ? array() : array_values($items),
            'count'     => $count,
            'page'      => $page,
            'pageSize'  => $pageSize
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
        $userId = $this->getRequest($data, '_id','int',0);
        $awardNum = $this->getRequest($data, 'award_num','int',0);
        if (empty($userId)) {
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '参数错误!');
        }
        $userInfo = $this->userService->findByID($userId);
        if(empty($userInfo)){
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '用户不存在!');
        }
        return $this->activityUserService->save($userInfo,$awardNum);
    }

    /**
     * 获取详情
     * @param $id
     * @return mixed
     * @throws BusinessException
     */
    public function getDetail($id)
    {
        $row = $this->activityUserService->findByID($id);
        if (empty($row)) {
            throw  new BusinessException(StatusCode::DATA_ERROR, '数据不存在!');
        }
        return $row;
    }

    /**
     * 删除
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        return $this->activityUserService->delete($id);
    }

}