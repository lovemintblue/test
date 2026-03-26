<?php

declare(strict_types=1);

namespace App\Repositories\Backend;

use App\Constants\CommonValues;
use App\Constants\StatusCode;
use App\Core\Repositories\BaseRepository;
use App\Exception\BusinessException;
use App\Services\ChannelAppService;

/**
 * 渠道管理
 * @package App\Repositories\Admin
 *
 * @property  ChannelAppService $channelAppService
 */
class ChannelAppRepository extends BaseRepository
{
    /**
     * 获取列表
     * @param $request
     * @return array
     */
    public function getList($request)
    {

        $page = $this->getRequest($request, 'page', 'int', 1);
        $pageSize = $this->getRequest($request, 'pageSize', 'int', 30);
        $sort     = $this->getRequest($request, 'sort', 'string', '_id');
        $order    = $this->getRequest($request, 'order', 'int', -1);
        $query = array();
        $filter = array();

        if ($request['code']) {
            $filter['code'] = $this->getRequest($request, 'code');
            $query['code'] = $filter['code'];
        }
        if ($request['type']) {
            $filter['type'] = $this->getRequest($request, 'type');
            $query['type'] = $filter['type'];
        }
        if ($request['is_need_verify']) {
            $filter['is_need_verify'] = $this->getRequest($request, 'code','int');
            $query['is_need_verify'] = $filter['is_need_verify'];
        }
        $skip = ($page - 1) * $pageSize;
        $fields = array();
        $count = $this->channelAppService->count($query);
        $items = $this->channelAppService->getList($query, $fields, array($sort => $order), $skip, $pageSize);
        foreach ($items as $index => $item) {
            $item['created_at'] = dateFormat($item['created_at']);
            $item['updated_at'] = dateFormat($item['updated_at']);
            $item['type_text'] =CommonValues::getChannelAppType($item['type']);
            $item['is_disabled'] = CommonValues::getIsDisabled($item['is_disabled']);
            $item['is_auto_download'] = $item['is_auto_download']?'是':'否';
            $item['is_need_verify'] = $item['is_need_verify']?'开启':'关闭';
            $item['check']      = strpos($item['link'],$item['code'])===false?0:1;
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
        if (empty($data['name']) || empty($data['code'])||empty($data['link'])) {
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '参数错误!');
        }
        $row = array(
            'name' => $this->getRequest($data, 'name'),
            'code' => trim($this->getRequest($data, 'code')),
            'link' => $this->getRequest($data, 'link'),
            'type' => $this->getRequest($data,'type'),
            'is_disabled' => $this->getRequest($data, 'is_disabled','int',0),
            'is_need_verify' => $this->getRequest($data, 'is_need_verify','int',0),
            'is_auto_download'=>$this->getRequest($data, 'is_auto_download','int',0)
        );
        if ($data['_id'] > 0) {
            $row['_id'] = $this->getRequest($data, '_id', 'int');
        }
        $checkRow = $this->channelAppService->findFirst(array('code' => $row['code']));
        if ($checkRow && $checkRow['_id'] != $row['_id']) {
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '渠道标识已存在!');
        }
        $result= $this->channelAppService->save($row);
        delCache('channel_app');
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
        $row = $this->channelAppService->findByID($id);
        if (empty($row)) {
            throw  new BusinessException(StatusCode::DATA_ERROR, '数据不存在!');
        }
        return $row;
    }

    /**
     * 更新数据
     * @param $data
     * @return bool|int|mixed
     */
    public function update($data)
    {
        $result= $this->channelAppService->save($data);
        delCache('channel_app');
        return $result;
    }

    /**
     * 删除订单
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        return $this->channelAppService->delete($id);
    }
}