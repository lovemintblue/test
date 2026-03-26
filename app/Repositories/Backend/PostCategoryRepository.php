<?php

declare(strict_types=1);

namespace App\Repositories\Backend;

use App\Constants\CommonValues;
use App\Constants\StatusCode;
use App\Core\Repositories\BaseRepository;
use App\Exception\BusinessException;
use App\Services\PostBlockService;
use App\Services\PostCategoryService;

/**
 * 帖子板块
 * @package App\Repositories\Backend
 *
 * @property  PostCategoryService $postCategoryService
 * @property  PostBlockService $postBlockService
 */
class PostCategoryRepository extends BaseRepository
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

        if ($request['name']) {
            $filter['name'] = $this->getRequest($request, 'name');
            $query['name']  = array('$regex' => $filter['name'], '$options' => 'i');
        }

        $skip   = ($page - 1) * $pageSize;
        $fields = array();
        $count  = $this->postCategoryService->count($query);
        $items  = $this->postCategoryService->getList($query, $fields, array($sort => $order), $skip, $pageSize);
        $posArr = CommonValues::getPostPosition();
        foreach ($items as $index => $item) {
            $item['created_at'] = dateFormat($item['created_at']);
            $item['updated_at'] = dateFormat($item['updated_at']);
            $item['position'] = $posArr[$item['position']];
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
            'sort'          => $this->getRequest($data, 'sort','int',0),
            'img'           => $this->getRequest($data, 'img','string', ''),
            'block_id'      => $this->getRequest($data,'block_id','int',0),
            'description'       => $this->getRequest($data,'description')
        );
        if (empty($row['name']) || empty($row['block_id']) || empty($row['description'])) {
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '参数错误!');
        }

        $blockInfo = $this->postBlockService->findByID($row['block_id']);
        if(empty($blockInfo)){
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '所属模块信息错误!');
        }
        $row['position'] = $blockInfo['position'];
        $row['block_name'] = $blockInfo['name'];

        if ($data['_id'] > 0) {
            $row['_id'] = $this->getRequest($data, '_id', 'int');
        }
        return $this->postCategoryService->save($row);
    }

    /**
     * 获取详情
     * @param $id
     * @return mixed
     * @throws BusinessException
     */
    public function getDetail($id)
    {
        $row = $this->postCategoryService->findByID($id);
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
        return $this->postCategoryService->delete($id);
    }

    /**
     * 获取所有分类
     * @return array
     */
    public function getAll()
    {
        return $this->postCategoryService->getAll();
    }

    /**
     * 获取所有模块
     * @return array|mixed
     */
    public function getAllBlocks()
    {
        return $this->postBlockService->getAll();
    }
}