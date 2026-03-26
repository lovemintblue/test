<?php

declare(strict_types=1);

namespace App\Repositories\Backend;

use App\Constants\CommonValues;
use App\Constants\StatusCode;
use App\Core\Repositories\BaseRepository;
use App\Exception\BusinessException;
use App\Services\MovieSpecialService;

/**
 * 视频专题管理
 * @package App\Repositories\Backend
 *
 * @property  MovieSpecialService $movieSpecialService
 */
class MovieSpecialRepository extends BaseRepository
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

        if ($request['position']) {
            $filter['position'] = $this->getRequest($request, 'position','string');
            $query['position']  = $filter['position'];
        }
        $skip   = ($page - 1) * $pageSize;
        $fields = array();
        $count  = $this->movieSpecialService->count($query);
        $items  = $this->movieSpecialService->getList($query, $fields, array($sort => $order), $skip, $pageSize);
        foreach ($items as $index => $item) {
            $item['created_at'] = dateFormat($item['created_at']);
            $item['updated_at'] = dateFormat($item['updated_at']);
            $item['position'] = $this->movieSpecialService->position[$item['position']];
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
            'filter'        => $this->getRequest($data, 'filter'),
            'img'           => $this->getRequest($data, 'img','string', ''),
            'bg_img'        => $this->getRequest($data, 'bg_img','string', ''),
            'position'      => $this->getRequest($data, 'position','string'),
            'is_disabled'   => $this->getRequest($data, 'is_disabled','int', 0),
            'description'   => $this->getRequest($data, 'description','string', ''),
        );
        $row['filter'] = stripcslashes($row['filter']);
        if (empty($row['name']) || empty($row['filter'])) {
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '参数错误!');
        }

        if (!json_decode($row['filter'], true)) {
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, 'json格式错误!');
        }

        if ($data['_id'] > 0) {
            $row['_id'] = $this->getRequest($data, '_id', 'int');
        }
        return $this->movieSpecialService->save($row);
    }

    /**
     * 获取详情
     * @param $id
     * @return mixed
     * @throws BusinessException
     */
    public function getDetail($id)
    {
        $row = $this->movieSpecialService->findByID($id);
        if (empty($row)) {
            throw  new BusinessException(StatusCode::DATA_ERROR, '数据不存在!');
        }
        $row['filter'] = stripcslashes($row['filter']);
        return $row;
    }

    /**
     * 删除
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        return $this->movieSpecialService->delete($id);
    }

}