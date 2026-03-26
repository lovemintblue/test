<?php

declare(strict_types=1);

namespace App\Repositories\Backend;

use App\Constants\StatusCode;
use App\Core\Repositories\BaseRepository;
use App\Exception\BusinessException;
use App\Services\ComicsKeywordsService;

/**
 * 视频关键字管理
 * @package App\Repositories\Admin
 *
 * @property  ComicsKeywordsService $comicsKeywordsService
 */
class ComicsKeywordsRepository extends BaseRepository
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

        if ($request['is_hot'] !== null && $request['is_hot'] !== '') {
            $filter['is_hot'] = $this->getRequest($request, 'is_hot', 'int');
            $query['is_hot']  = $filter['is_hot'];
        }
        if ($request['position'] !== null && $request['position'] !== '') {
            $filter['position'] = $this->getRequest($request, 'position', 'string');
            $query['position']  = $filter['position'];
        }

        $skip   = ($page - 1) * $pageSize;
        $fields = array();
        $count  = $this->comicsKeywordsService->count($query);
        $items  = $this->comicsKeywordsService->getList($query, $fields, array($sort => $order), $skip, $pageSize);
        foreach ($items as $index => $item) {
            $item['created_at'] = dateFormat($item['created_at']);
            $item['updated_at'] = dateFormat($item['updated_at']);
            $item['is_hot_text'] = $item['is_hot'] ? '是' : '否';
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
     * 获取详情
     * @param $id
     * @return mixed
     * @throws BusinessException
     */
    public function getDetail($id)
    {
        $row = $this->comicsKeywordsService->findByID($id);
        if (empty($row)) {
            throw  new BusinessException(StatusCode::DATA_ERROR, '数据不存在!');
        }
        return $row;
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
            'is_hot' => $this->getRequest($data, 'is_hot', 'int', 0),
            'num' => $this->getRequest($data, 'num', 'int', 0),
            'sort' => $this->getRequest($data, 'sort', 'int', 0),
        );
        if (empty($row['name'])) {
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '参数错误!');
        }
        $row['_id']=md5($row['name']);

        if ($this->comicsKeywordsService->count(['_id'=>$row['_id']])) {
            return $this->comicsKeywordsService->save($row);
        }else{
            return $this->comicsKeywordsService->comicsKeywordsModel->insert($row);
        }
    }

    /**
     * 更新数据
     * @param $data
     * @return bool|int|mixed
     */
    public function update($data)
    {
        return $this->comicsKeywordsService->save($data);
    }

    /**
     * 删除
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        return $this->comicsKeywordsService->delete($id);
    }

}