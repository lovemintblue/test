<?php

declare(strict_types=1);

namespace App\Repositories\Backend;

use App\Constants\CommonValues;
use App\Constants\StatusCode;
use App\Core\Repositories\BaseRepository;
use App\Exception\BusinessException;
use App\Services\AdminUserService;
use App\Services\UserTaskService;

/**
 * 用户任务
 * @package App\Repositories\Backend
 *
 * @property  UserTaskService $userTaskService
 * @property  AdminUserService $adminUserService
 */
class UserTaskRepository extends BaseRepository
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
            $query['name']  = array('$regex' => $filter['name'], '$options' => 'i');
        }
        if ($request['type']) {
            $filter['type'] = $this->getRequest($request, 'type');
            $query['type']  = $filter['type'];
        }

        $skip = ($page - 1) * $pageSize;
        $fields = array();
        $count = $this->userTaskService->count($query);
        $items = $this->userTaskService->getList($query, $fields, array($sort => $order), $skip, $pageSize);
        foreach ($items as $index => $item) {
            $item['created_at'] = dateFormat($item['created_at']);
            $item['updated_at'] = dateFormat($item['updated_at']);
            $item['type_text'] = CommonValues::getTaskTypes($item['type']);
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
            'name'          => $this->getRequest($data, 'name'),
            'type'          => $this->getRequest($data, 'type'),
            'description'   => $this->getRequest($data, 'description'),
            'link'          => $this->getRequest($data,'link'),
            'max_limit'           => $this->getRequest($data, 'max_limit', 'int', 1),
            'num'           => $this->getRequest($data, 'num', 'int', 1),
            'sort'          => $this->getRequest($data, 'sort', 'int', 0),
        );
        if(empty($row['name']) || empty($row['type']) || empty($row['description'])){
            throw  new BusinessException(StatusCode::DATA_ERROR, '参数错误!');
        }
        if (($row['type']=='comment' || $row['type']=='download' ||  $row['type']=='login') && $row['num']<1) {
            throw  new BusinessException(StatusCode::DATA_ERROR, '下载,评论,登陆需要加单次赠送积分数!');
        }
        if($row['type']=='download' && empty($row['link'])){
            throw  new BusinessException(StatusCode::DATA_ERROR, '下载任务的下载链接不能为空!');
        }
        if ($data['_id'] > 0) {
            $row['_id'] = $this->getRequest($data, '_id', 'int');
        }
        $result = $this->userTaskService->save($row);
        $this->adminUserService->addAdminLog(sprintf('操作用户任务 名称:%s,类型:%s,链接:%s,单日限制:%s 单次赠送:%s 编号:%s',
            $row['name'], $row['type'], $row['link'], $row['max_limit'], $row['num'],empty($row['_id']) ? $result : $row['_id']));
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
        $row = $this->userTaskService->findByID($id);
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
        return $this->userTaskService->delete($id);
    }

    /**
     * 获取所有分组
     * @return array
     */
    public function getAll()
    {
        return $this->userTaskService->getAll();
    }
}