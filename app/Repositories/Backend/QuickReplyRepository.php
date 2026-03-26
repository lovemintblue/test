<?php

declare(strict_types=1);

namespace App\Repositories\Backend;

use App\Constants\StatusCode;
use App\Core\Repositories\BaseRepository;
use App\Exception\BusinessException;
use App\Services\QuickReplyService;

/**
 * 快捷回复
 * @package App\Repositories\Backend
 *
 * @property  QuickReplyService $quickReplyService
 */
class QuickReplyRepository extends BaseRepository
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
        $count  = $this->quickReplyService->count($query);
        $items  = $this->quickReplyService->getList($query, $fields, array($sort => $order), $skip, $pageSize);
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
            'name' => $this->getRequest($data, 'name', 'string'),
            'content' => $this->getRequest($data, 'content', 'string'),
            'sort' => $this->getRequest($data, 'sort', 'int', 0),
        );

        if (empty($row['name']) || empty($row['content'])) {
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '参数错误!');
        }

        if ($data['_id'] > 0) {
            $row['_id'] = $this->getRequest($data, '_id', 'int');
        }
        return $this->quickReplyService->save($row);
    }

    /**
     * 获取详情
     * @param $id
     * @return mixed
     * @throws BusinessException
     */
    public function getDetail($id)
    {
        $row = $this->quickReplyService->findByID($id);
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
        return $this->quickReplyService->delete($id);
    }


    /**
     * 所有
     * @return array
     */
    public function getAll()
    {
        return $this->quickReplyService->getAll();
    }

}