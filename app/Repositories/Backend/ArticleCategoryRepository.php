<?php

declare(strict_types=1);

namespace App\Repositories\Backend;

use App\Constants\StatusCode;
use App\Core\Repositories\BaseRepository;
use App\Exception\BusinessException;
use App\Services\ArticleCategoryService;

/**
 * 文章分类
 * @package App\Repositories\Backend
 *
 * @property  ArticleCategoryService $articleCategoryService
 */
class ArticleCategoryRepository extends BaseRepository
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
        if ($request['code']) {
            $filter['code'] = $this->getRequest($request, 'code');
            $query['code'] = $filter['code'];
        }

        $skip = ($page - 1) * $pageSize;
        $fields = array();
        $count = $this->articleCategoryService->count($query);
        $items = $this->articleCategoryService->getList($query, $fields, array($sort => $order), $skip, $pageSize);
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
            'name' => $this->getRequest($data, 'name'),
            'code' => $this->getRequest($data, 'code'),
            'img' => $this->getRequest($data, 'img'),
            'sort' => $this->getRequest($data, 'sort', 'int', 0),
            'parent_id' => $this->getRequest($data, 'parent_id', 'int', 0),
        );
        if (empty($row['code']) || empty($row['name'])) {
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '参数错误!');
        }

        $checkRow = $this->articleCategoryService->findFirst(array('code' => $row['code']));
        if ($checkRow && $checkRow['_id'] != $data['_id']) {
            throw  new BusinessException(StatusCode::DATA_ERROR, '分类标识不能重复!');
        }

        if ($data['_id'] > 0) {
            $row['_id'] = $this->getRequest($data, '_id', 'int');
        }
        return $this->articleCategoryService->save($row);
    }

    /**
     * 获取详情
     * @param $id
     * @return mixed
     * @throws BusinessException
     */
    public function getDetail($id)
    {
        $row = $this->articleCategoryService->findByID($id);
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
        return $this->articleCategoryService->delete($id);
    }

    /**
     * 所有分类
     * @return array
     */
    public function getAll()
    {
        return $this->articleCategoryService->getAll();
    }

    /**
     * 所有分类
     * @return string
     */
    public function getTreeOptions()
    {
        return $this->articleCategoryService->getTreeOptions();
    }

    /**
     * 所有分类
     * @return string
     */
    public function getTreeCodeOptions()
    {
        return $this->articleCategoryService->getTreeCodeOptions();
    }

}