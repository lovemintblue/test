<?php

declare(strict_types=1);

namespace App\Repositories\Backend;

use App\Constants\StatusCode;
use App\Core\Repositories\BaseRepository;
use App\Exception\BusinessException;
use App\Services\AdminUserService;
use App\Services\AdvPosService;
use App\Services\AdvService;
use App\Services\ConfigService;

/**
 * 广告
 * @package App\Repositories\Backend
 *
 * @property  AdvPosService $advPosService
 * @property  AdvService $advService
 * @property  ConfigService $configService
 * @property  AdminUserService $adminUserService
 */
class AdvRepository extends BaseRepository
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
        if ($request['position_code']) {
            $filter['position_code'] = $this->getRequest($request, 'position_code');
            $query['position_code'] = $filter['position_code'];
        }
        if ($request['channel_code']) {
            $filter['channel_code'] = $this->getRequest($request, 'channel_code');
            $query['channel_code'] = $filter['channel_code'];
        }
        $skip = ($page - 1) * $pageSize;
        $fields = array();
        $count = $this->advService->count($query);
        $items = $this->advService->getList($query, $fields, array($sort => $order), $skip, $pageSize);

        $posArr = $this->advPosService->getAll();
        foreach ($items as $index => $item) {
            $item['created_at'] = dateFormat($item['created_at']);
            $item['updated_at'] = dateFormat($item['updated_at']);
            $item['start_time'] = dateFormat($item['start_time']);
            $item['end_time'] = dateFormat($item['end_time']);
            $item['position_name'] = strval($posArr[$item['position_code']]['name']);
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
            'position_code' => $this->getRequest($data, 'position_code'),
            'type' => $this->getRequest($data, 'type'),
            'right' => $this->getRequest($data, 'right'),
            'channel_code' => $this->getRequest($data, 'channel_code','string','main'),
            'content' => $this->getRequest($data, 'content'),
            'start_time' => $this->getRequest($data, 'start_time'),
            'end_time' => $this->getRequest($data, 'end_time'),
            'link' => $this->getRequest($data, 'link','string', ''),
            'language' => $this->getRequest($data, 'language','string', 'cn'),
            'sort' => $this->getRequest($data, 'sort', 'int', 0),
            'click' => $this->getRequest($data, 'click', 'int', 0),
            'is_vip' => $this->getRequest($data, 'is_vip', 'int', 0),
            'show_time' => $this->getRequest($data, 'show_time', 'int', 5),
        );
        if (empty($row['name']) || empty($row['position_code'])||empty($row['right'])) {
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '必填数据错误!');
        }
        if (empty($row['start_time']) || empty($row['end_time'])) {
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '开始时间或者结束时间错误!');
        }

        $row['start_time'] = strtotime($row['start_time']);
        $row['end_time'] = strtotime($row['end_time']);

        if ($data['_id'] !=='') {
            $row['_id'] = $this->getRequest($data, '_id', 'string');
        }
        $result = $this->advService->save($row);
        $this->adminUserService->addAdminLog(sprintf('操作广告,广告链接%s,广告位置%s,到期时间%s,广告编号%s',
            $row['link'], $row['position_code'], date('Y-m-d H:i:s', $row['end_time']), empty($row['_id']) ? $result : $row['_id']));
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
        $row = $this->advService->findByID($id);
        if (empty($row)) {
            throw  new BusinessException(StatusCode::DATA_ERROR, '数据不存在!');
        }
        $row['start_time'] = dateFormat($row['start_time']);
        $row['end_time'] = dateFormat($row['end_time']);
        $row['created_at'] = dateFormat($row['created_at']);
        $row['updated_at'] = dateFormat($row['updated_at']);
        return $row;
    }

    /**
     * 删除订单
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        $this->adminUserService->addAdminLog(sprintf('删除广告,广告编号%s', $id));
        return $this->advService->delete($id);
    }

}