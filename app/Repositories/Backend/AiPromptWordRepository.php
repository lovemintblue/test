<?php

declare(strict_types=1);

namespace App\Repositories\Backend;

use App\Constants\CommonValues;
use App\Constants\StatusCode;
use App\Core\Repositories\BaseRepository;
use App\Exception\BusinessException;
use App\Services\AiPromptWordService;
use App\Utils\HanziConvert;

/**
 * 创意提示词
 *
 * @package App\Repositories\Backend
 *
 * @property  AiPromptWordService $aiPromptWordService
 */
class AiPromptWordRepository extends BaseRepository
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
        if ($request['group']) {
            $filter['group'] = $this->getRequest($request, 'group','string');
            $query['group'] = $filter['group'];
        }
        if ($request['is_hot']!==''&&$request['is_hot']!==null) {
            $filter['is_hot'] = $this->getRequest($request, 'is_hot','int');
            $query['is_hot']  = $filter['is_hot'];
        }

        $skip   = ($page - 1) * $pageSize;
        $fields = array();
        $count  = $this->aiPromptWordService->count($query);
        $items  = $this->aiPromptWordService->getList($query, $fields, array($sort => $order), $skip, $pageSize);
        foreach ($items as $index => $item) {
            $item['created_at'] = dateFormat($item['created_at']);
            $item['updated_at'] = dateFormat($item['updated_at']);
            $item['is_hot']   = CommonValues::getHot($item['is_hot']);
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
            'name'       => $this->getRequest($data, 'name','string'),
            'en_name'    => $this->getRequest($data, 'en_name','string'),
            'group'      => $this->getRequest($data, 'group','string'),
            'sort'       => $this->getRequest($data,'sort','int',0),
            'is_hot'     => $this->getRequest($data,'is_hot','int',0)
        );
        if (empty($row['name'])||empty($row['en_name'])||empty($row['group'])) {
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '参数错误!');
        }

        $where=['name'=>$row['name']];
        if ($data['_id']) {
            $row['_id'] = $this->getRequest($data, '_id', 'int');
            $where['_id']=['$ne'=>$row['_id']];
        }
        $checkRow=$this->aiPromptWordService->findFirst($where);
        if ($checkRow) {
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '名称不能重复!');
        }
        return $this->aiPromptWordService->save($row);
    }

    /**
     * 获取详情
     * @param $id
     * @return mixed
     * @throws BusinessException
     */
    public function getDetail($id)
    {
        $row = $this->aiPromptWordService->findByID($id);
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
        return $this->aiPromptWordService->delete($id);
    }

    /**
     * 获取所有分类
     * @param string $objectType movie post
     * @return array
     */
    public function getGroupAttrAll($objectType)
    {
        return $this->aiPromptWordService->getGroupAttrAll($objectType);
    }

}