<?php

declare(strict_types=1);

namespace App\Repositories\Backend;

use App\Constants\CommonValues;
use App\Constants\StatusCode;
use App\Core\Repositories\BaseRepository;
use App\Exception\BusinessException;
use App\Services\BlockPositionService;

/**
 * 模块位置
 * @package App\Repositories\Backend
 *
 * @property  BlockPositionService $blockPositionService
 */
class BlockPositionRepository extends BaseRepository
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
        $count = $this->blockPositionService->count($query);
        $items = $this->blockPositionService->getList($query, $fields, array($sort => $order), $skip, $pageSize);
        $groupArr = CommonValues::getBlockPositionGroup();
        foreach ($items as $index => $item) {
            $item['created_at'] = dateFormat($item['created_at']);
            $item['updated_at'] = dateFormat($item['updated_at']);
            $item['group_text'] = $groupArr[$item['group']];
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
            'group' => $this->getRequest($data, 'group'),
            'sort' => $this->getRequest($data, 'sort', 'int', 0)
        );
        if (empty($row['code']) || empty($row['name'])) {
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '参数错误!');
        }
        $checkRow = $this->blockPositionService->findFirst(array('code' => $row['code']));
        if ($checkRow && $checkRow['_id'] != $data['_id']) {
            throw  new BusinessException(StatusCode::DATA_ERROR, '标识不能重复!');
        }

        if ($data['_id'] > 0) {
            $row['_id'] = $this->getRequest($data, '_id', 'int');
        }
        return $this->blockPositionService->save($row);
    }

    /**
     * 获取详情
     * @param $id
     * @return mixed
     * @throws BusinessException
     */
    public function getDetail($id)
    {
        $row = $this->blockPositionService->findByID($id);
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
        return $this->blockPositionService->delete($id);
    }

    /**
     * 所有分类
     * @return array
     */
    public function getAll()
    {
        return $this->blockPositionService->getAll();
    }

}