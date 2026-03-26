<?php

declare(strict_types=1);

namespace App\Repositories\Backend;

use App\Constants\CommonValues;
use App\Constants\StatusCode;
use App\Core\Repositories\BaseRepository;
use App\Exception\BusinessException;
use App\Services\ComicsTagService;
use App\Services\movieTagService;
use App\Utils\HanziConvert;

/**
 * 标签
 * @package App\Repositories\Backend
 *
 * @property  ComicsTagService $comicsTagService
 */
class ComicsTagRepository extends BaseRepository
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
        if ($request['attribute']!==''&&$request['attribute']!==null) {
            $filter['attribute'] = $this->getRequest($request, 'attribute','string');
            $query['attribute'] = $filter['attribute'];
        }
        if ($request['is_hot']!==''&&$request['is_hot']!==null) {
            $filter['is_hot'] = $this->getRequest($request, 'is_hot','int');
            $query['is_hot']  = $filter['is_hot'];
        }
        $skip   = ($page - 1) * $pageSize;
        $fields = array();
        $count  = $this->comicsTagService->count($query);
        $items  = $this->comicsTagService->getList($query, $fields, array($sort => $order), $skip, $pageSize);
        foreach ($items as $index => $item) {
            $item['created_at'] = dateFormat($item['created_at']);
            $item['updated_at'] = dateFormat($item['updated_at']);
            $item['is_hot']     = CommonValues::getHot($item['is_hot']);
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
            'name'      => $this->getRequest($data, 'name'),
            'is_hot'    => $this->getRequest($data, 'is_hot','int'),
            'attribute' => $this->getRequest($data, 'attribute','string'),
        );
        if (empty($row['name'])||empty($row['attribute'])) {
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '参数错误!');
        }
        $row['name']    = strtolower($row['name']);
        $row['name']    = HanziConvert::convert($row['name']);

        $where=['name'=>$row['name']];
        if ($data['_id'] > 0) {
            $row['_id'] = $this->getRequest($data, '_id', 'int');
            $where['_id']=['$ne'=>$row['_id']];
        }
        $checkRow=$this->comicsTagService->findFirst($where);
        if ($checkRow) {
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '分类名称不能重复!');
        }
        return $this->comicsTagService->save($row);
    }

    /**
     * 获取详情
     * @param $id
     * @return mixed
     * @throws BusinessException
     */
    public function getDetail($id)
    {
        $row = $this->comicsTagService->findByID($id);
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
        return $this->comicsTagService->delete($id);
    }

    /**
     * 获取所有分类
     * @return array
     */
    public function getGroupAttrAll()
    {
        return $this->comicsTagService->getGroupAttrAll();
    }

}