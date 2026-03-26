<?php

declare(strict_types=1);

namespace App\Repositories\Backend;

use App\Constants\CommonValues;
use App\Constants\StatusCode;
use App\Core\Repositories\BaseRepository;
use App\Exception\BusinessException;
use App\Services\ChannelAppService;
use App\Services\DomainService;

/**
 * 域名管理
 * @package App\Repositories\Admin
 *
 * @property  DomainService $domainService
 */
class DomainRepository extends BaseRepository
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

        if ($request['url']) {
            $filter['url'] = $this->getRequest($request, 'url');
            $query['url'] = $filter['url'];
        }
        if ($request['type']) {
            $filter['type'] = $this->getRequest($request, 'type');
            $query['type'] = $filter['type'];
        }
        if (isset($request['status']) && $request['status']!=="") {
            $filter['status'] = $this->getRequest($request, 'status','int');
            $query['status'] = $filter['status'];
        }

        $skip = ($page - 1) * $pageSize;
        $fields = array();
        $count = $this->domainService->count($query);
        $items = $this->domainService->getList($query, $fields, array($sort => $order), $skip, $pageSize);
        $cities = CommonValues::getMonitorCities();
        foreach ($items as $index => $item) {
            $item['created_at'] = dateFormat($item['created_at'],'m-d H:i');
            $item['updated_at'] = dateFormat($item['updated_at'],'m-d H:i');
            $item['channel_code'] =$item['channel_code']?:'-';
            $item['type_text'] =CommonValues::getDomainType($item['type']);
            $item['status_text'] = CommonValues::getDomainStatusHtml($item['status']);
            foreach ($cities as $city){
                $item[$city] = CommonValues::getDomainStatusHtml($item[$city]*1);
            }
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
        if (empty($data['url']) || empty($data['type'])) {
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '参数错误!');
        }
        $row = array(
            'url' => $this->getRequest($data, 'url'),
            'type' => trim($this->getRequest($data, 'type')),
            'status' => $this->getRequest($data, 'status','int',0),
            'remark' => $this->getRequest($data,'remark','string',''),
            'channel_code' => $this->getRequest($data,'channel_code','string',''),
            'count_code' => $data['count_code']
        );
        if ($data['_id'] > 0) {
            $row['_id'] = $this->getRequest($data, '_id', 'int');
        }
        $checkRow = $this->domainService->findFirst(array('url' => $row['url']));
        if ($checkRow && $checkRow['_id'] != $row['_id']) {
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '链接已存在!');
        }
        return $this->domainService->save($row);
    }

    /**
     * 获取详情
     * @param $id
     * @return mixed
     * @throws BusinessException
     */
    public function getDetail($id)
    {
        $row = $this->domainService->findByID($id);
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
        return $this->domainService->save($data);
    }

    /**
     * 删除订单
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        return $this->domainService->delete($id);
    }
}