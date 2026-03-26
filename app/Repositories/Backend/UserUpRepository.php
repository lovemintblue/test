<?php

declare(strict_types=1);

namespace App\Repositories\Backend;


use App\Constants\StatusCode;
use App\Core\Repositories\BaseRepository;
use App\Exception\BusinessException;
use App\Services\AdminUserService;
use App\Services\UserService;
use App\Services\UserUpService;

/**
 * 用户up
 * @package App\Repositories\Backend
 *
 * @property  UserUpService $userUpService
 * @property  UserService $userService
 * @property  AdminUserService $adminUserService
 */
class UserUpRepository extends BaseRepository
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

        if ($request['user_id']) {
            $filter['user_id'] = $this->getRequest($request, 'user_id','int');
            $query['user_id']  = $filter['user_id'];
        }

        $skip = ($page - 1) * $pageSize;
        $fields = array();
        $count = $this->userUpService->count($query);
        $items = $this->userUpService->getList($query, $fields, array($sort => $order), $skip, $pageSize);
        foreach ($items as $index => $item) {
            $item['created_at'] = dateFormat($item['created_at']);
            $item['updated_at'] = dateFormat($item['updated_at']);
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
            'user_id'          => $this->getRequest($data, 'user_id','int'),
            'sort'          => $this->getRequest($data, 'sort', 'int', 0),
        );
        if(empty($row['user_id'])){
            throw  new BusinessException(StatusCode::DATA_ERROR, '参数错误!');
        }
        $userInfo = $this->userService->getInfoFromCache($row['user_id']);
        if(empty($userInfo)){
            throw  new BusinessException(StatusCode::DATA_ERROR, '用户不存在!');
        }
        if($userInfo['is_up']!='y'){
            throw  new BusinessException(StatusCode::DATA_ERROR, '用户还不是UP主!');
        }
        $row['username'] = $userInfo['username'];
        if ($data['_id'] > 0) {
            $row['_id'] = $this->getRequest($data, '_id', 'int');
        }
        $result = $this->userUpService->save($row);
        return $result;
    }

    /**
     * 获取详情
     * @param $id
     * @return mixed
     * @throws BusinessException
     */
    public function getDetail($id)
    {
        $row = $this->userUpService->findByID($id);
        if (empty($row)) {
            throw  new BusinessException(StatusCode::DATA_ERROR, '数据不存在!');
        }
        return $row;
    }

    /**
     * 删除订单
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        return $this->userUpService->delete($id);
    }

}