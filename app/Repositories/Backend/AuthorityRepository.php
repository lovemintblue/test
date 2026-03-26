<?php

declare(strict_types=1);

namespace App\Repositories\Backend;

use App\Constants\CommonValues;
use App\Constants\StatusCode;
use App\Core\Repositories\BaseRepository;
use App\Exception\BusinessException;
use App\Services\AuthorityService;

/**
 * 系统资源
 * @package App\Repositories\Backend
 *
 * @property  AuthorityService $authorityService
 */
class AuthorityRepository extends BaseRepository
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
            $query['name'] = array('$regex'=> $filter['name'],'$options'=>'i');
        }
        if ($request['parent_id'] !== null && $request['parent_id'] !== "") {
            $filter['parent_id'] = $this->getRequest($request, 'parent_id', 'int');
            $query['parent_id'] = $filter['parent_id'];
        }
        if ($request['is_menu'] !== null && $request['is_menu'] !== "") {
            $filter['is_menu'] = $this->getRequest($request, 'is_menu', 'int');
            $query['is_menu'] = $filter['is_menu'];
        }
        if ($request['key']) {
            $filter['key'] = $this->getRequest($request, 'key');
            $query['key'] = array('$regex'=>$filter['key'],'$options'=>'i');
        }

        $skip = ($page - 1) * $pageSize;
        $fields = array();
        $count = $this->authorityService->count($query);
        $items = $this->authorityService->getList($query, $fields, array($sort => $order), $skip, $pageSize);
        foreach ($items as $index => $item) {
            $item['created_at'] = dateFormat($item['created_at']);
            $item['updated_at'] = dateFormat($item['updated_at']);
            $item['is_menu'] = CommonValues::getIsMenus($item['is_menu']);
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
        if (empty($data['name']) || empty($data['key'])) {
            throw  new BusinessException(StatusCode::PARAMETER_ERROR);
        }
        
        $key = $this->getRequest($data, 'key');
        $checkItem = $this->authorityService->findFirst(array('key' => $key));
        if ($checkItem && $checkItem['_id'] != $data['_id']) {
            throw  new BusinessException(StatusCode::DATA_ERROR, '当前key已经存在!');
        }
        $row = array(
            'name' => $this->getRequest($data, 'name'),
            'key' => $this->getRequest($data, 'key'),
            'parent_id' => $this->getRequest($data, 'parent_id', 'int', 0),
            'sort' => $this->getRequest($data, 'sort', 'int', 0),
            'class_name' => $this->getRequest($data, 'class_name','string',''),
            'is_menu' => $this->getRequest($data, 'is_menu', 'int', 0),
            'link' => $this->getRequest($data, 'link','string',''),
        );
        if (!empty($data['_id'])) {
            $row['_id'] = $this->getRequest($data, '_id', 'int');
        }
        return $this->authorityService->save($row);
    }

    /**
     * 获取详情
     * @param $id
     * @return mixed
     * @throws BusinessException
     */
    public function getDetail($id)
    {
        $row = $this->authorityService->findByID($id);
        if(empty($row)){
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
        return $this->authorityService->delete($id);
    }

    /**
     * 获取资源树状
     * @return string
     */
    public function getTreeOptions()
    {
        return $this->authorityService->getTreeOptions();
    }

    /**
     * 获取资源树状
     * @return array
     */
    public function getTree()
    {
        return $this->authorityService->getTree();
    }
}