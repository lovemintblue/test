<?php

declare(strict_types=1);

namespace App\Repositories\Backend;

use App\Constants\CommonValues;
use App\Constants\StatusCode;
use App\Core\Repositories\BaseRepository;
use App\Exception\BusinessException;
use App\Services\AiBlockService;

/**
 * AI功能模块
 * @package App\Repositories\Backend
 *
 * @property  AiBlockService $aiBlockService
 */
class AiBlockRepository extends BaseRepository
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
        if (!empty($request['is_disabled'])) {
            $filter['is_disabled'] = $this->getRequest($request, 'is_disabled', 'int');
            $query['is_disabled']  = $filter['is_disabled'];
        }

        $skip   = ($page - 1) * $pageSize;
        $fields = array();
        $count  = $this->aiBlockService->count($query);
        $items  = $this->aiBlockService->getList($query, $fields, array($sort => $order), $skip, $pageSize);
        foreach ($items as $index => $item) {
            $item['created_at']     = dateFormat($item['created_at']);
            $item['updated_at']     = dateFormat($item['updated_at']);
            $item['updated_at']     = dateFormat($item['updated_at']);
            $item['ico']            = $item['ico']?:'-';
            $item['is_disabled']    = CommonValues::getIsDisabled($item['is_disabled']);
            $items[$index] = $item;
        }

        return array(
            'filter'    => $filter,
            'items'     => empty($items) ? array() : array_values($items),
            'count'     => $count,
            'page'      => $page,
            'pageSize'  => $pageSize
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
            'url'           => $this->getRequest($data, 'url','string',''),
            'position'      => $this->getRequest($data, 'position','string',''),
            'min_version'   => $this->getRequest($data, 'min_version','string',''),
            'img_x'         => $this->getRequest($data, 'img_x','string',''),
            'ico'           => $this->getRequest($data, 'ico','string',''),
            'sort'          => $this->getRequest($data, 'sort','int',0),
            'is_disabled'   => $this->getRequest($data, 'is_disabled','int',0),
        );
        if (empty($row['name']) || empty($row['position'])) {
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '参数错误!');
        }

        if ($data['_id'] > 0) {
            $row['_id'] = $this->getRequest($data, '_id', 'int');
        }
        return $this->aiBlockService->save($row);
    }

    /**
     * 获取详情
     * @param $id
     * @return mixed
     * @throws BusinessException
     */
    public function getDetail($id)
    {
        $row = $this->aiBlockService->findByID($id);
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
        return $this->aiBlockService->delete($id);
    }

}