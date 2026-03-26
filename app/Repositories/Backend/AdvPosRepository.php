<?php

declare(strict_types=1);

namespace App\Repositories\Backend;

use App\Constants\CommonValues;
use App\Constants\StatusCode;
use App\Core\Repositories\BaseRepository;
use App\Exception\BusinessException;
use App\Services\AdminUserService;
use App\Services\AdvPosService;

/**
 * 广告位置
 * @package App\Repositories\Backend
 *
 * @property  AdvPosService $advPosService
 * @property  AdminUserService $adminUserService
 */
class AdvPosRepository extends BaseRepository
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
        $count = $this->advPosService->count($query);
        $items = $this->advPosService->getList($query, $fields, array('created_at' => -1), $skip, $pageSize);
        foreach ($items as $index => $item) {
            $item['created_at'] = dateFormat($item['created_at']);
            $item['updated_at'] = dateFormat($item['updated_at']);
            $item['is_disabled_text'] = CommonValues::getIsDisabled($item['is_disabled']);
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
        $code = $this->getRequest($data, 'code');
        $name = $this->getRequest($data, 'name');
        if (empty($code) || empty($name)) {
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '参数错误!');
        }
        $checkRow = $this->advPosService->findFirst(array('code' => $data['code']));
        if ($checkRow && $checkRow['_id'] != $data['_id']) {
            throw  new BusinessException(StatusCode::DATA_ERROR, '广告位标识不能重复!');
        }
        $row = array(
            'name' => $name,
            'code' => $code,
            'is_disabled' => $this->getRequest($data, 'is_disabled', 'int', 0),
            'width' => $this->getRequest($data, 'width', 'int',0),
            'height' => $this->getRequest($data, 'height', 'int',0),
        );
        if ($data['_id'] > 0) {
            $row['_id'] = $this->getRequest($data, '_id', 'int');
        }
        $result=$this->advPosService->save($row);
        $this->adminUserService->addAdminLog(sprintf('保存广告位 名称%s,Code%s,编号%s', $name, $code, empty($row['_id']) ? $result : $row['_id']));
        return $result;
    }

    /**
     * 获取详情
     * @param $id
     * @return mixed
     * @throws BusinessException
     */
    public function getDetail($id)
    {
        $row = $this->advPosService->findByID($id);
        if (empty($row)) {
            throw  new BusinessException(StatusCode::DATA_ERROR, '数据不存在!');
        }
        return $row;
    }

    /**
     * 删除订单
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        $this->adminUserService->addAdminLog(sprintf('删除广告位 编号%s', $id));
        return $this->advPosService->delete($id);
    }

    /**
     * 获取所有广告
     * @return array
     */
    public function getAll()
    {
        return $this->advPosService->getAll();
    }

}