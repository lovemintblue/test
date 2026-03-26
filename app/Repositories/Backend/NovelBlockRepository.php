<?php

declare(strict_types=1);

namespace App\Repositories\Backend;

use App\Constants\CommonValues;
use App\Constants\StatusCode;
use App\Core\Repositories\BaseRepository;
use App\Exception\BusinessException;
use App\Services\BlockPositionService;
use App\Services\NovelBlockService;

/**
 * 视频模块管理
 * @package App\Repositories\Backend
 *
 * @property  NovelBlockService $novelBlockService
 * @property BlockPositionService $blockPositionService
 */
class NovelBlockRepository extends BaseRepository
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
            $filter['position'] = $this->getRequest($request, 'position');
            $query['position']  = $filter['position'];
        }
        if (!empty($request['is_disabled'])) {
            $filter['is_disabled'] = $this->getRequest($request, 'is_disabled', 'int');
            $query['is_disabled']  = $filter['is_disabled'];
        }

        $skip   = ($page - 1) * $pageSize;
        $fields = array();
        $count  = $this->novelBlockService->count($query);
        $items  = $this->novelBlockService->getList($query, $fields, array($sort => $order), $skip, $pageSize);
        foreach ($items as $index => $item) {
            $position = $this->getPosition($item['position']);
            $item['created_at']     = dateFormat($item['created_at']);
            $item['updated_at']     = dateFormat($item['updated_at']);
            $item['ico']            = $item['ico']?:'-';
            $item['is_disabled']    = CommonValues::getIsDisabled($item['is_disabled']);
            $item['position']       = $position['code'].'|'.$position['name'].'('.$position['group_name'].')';
            $item['style']          = CommonValues::getComicsBlockStyles($item['style']);
            $item['is_hot']     = CommonValues::getHot($item['is_hot']*1);
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
            'style'         => $this->getRequest($data, 'style','int'),
            'sort'          => $this->getRequest($data, 'sort','int',0),
            'is_hot'        => $this->getRequest($data,'is_hot','int',0),
            'filter'        => $this->getRequest($data, 'filter'),
            'num'           => $this->getRequest($data, 'num','int',0),
            'position'      => $this->getRequest($data, 'position','string', ''),
            'is_disabled'   => $this->getRequest($data, 'is_disabled','int',0),
            'ico'           => $this->getRequest($data, 'ico','string',''),
        );
        if (empty($row['name']) || empty($row['style']) || empty($row['position']) || empty($row['filter'])) {
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '参数错误!');
        }
        $row['filter'] = stripcslashes($row['filter']);
        if (!json_decode($row['filter'], true)) {
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, 'json格式错误!');
        }
        if ($data['_id'] > 0) {
            $row['_id'] = $this->getRequest($data, '_id', 'int');
        }
        return $this->novelBlockService->save($row);
    }

    /**
     * 获取详情
     * @param $id
     * @return mixed
     * @throws BusinessException
     */
    public function getDetail($id)
    {
        $row = $this->novelBlockService->findByID($id);
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
        return $this->novelBlockService->delete($id);
    }

    /**
     * 返回模块显示位置
     * @param $key
     * @return array
     */
    public function getPosition($key='')
    {
        $position = $this->blockPositionService->getAll();
        return $key?$position[$key]:$position;
    }

}