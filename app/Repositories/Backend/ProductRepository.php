<?php

declare(strict_types=1);

namespace App\Repositories\Backend;

use App\Constants\CommonValues;
use App\Constants\StatusCode;
use App\Core\Repositories\BaseRepository;
use App\Exception\BusinessException;
use App\Services\AdminUserService;
use App\Services\ProductService;

/**
 * 金币套餐
 * @package App\Repositories\Backend
 *
 * @property  ProductService $productService
 * @property AdminUserService $adminUserService
 */
class ProductRepository extends BaseRepository
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
        if ($request['type']) {
            $filter['type'] = $this->getRequest($request, 'type');
            $query['type']  = $filter['type'];
        }
        if ($request['is_disabled'] !== null) {
            $filter['is_disabled'] = $this->getRequest($request, 'is_disabled', 'int');
            $query['is_disabled']  = $filter['is_disabled'];
        }

        $skip   = ($page - 1) * $pageSize;
        $fields = array();
        $count  = $this->productService->count($query);
        $items  = $this->productService->getList($query, $fields, array($sort => $order), $skip, $pageSize);
        foreach ($items as $index => $item) {
            $item['created_at'] = dateFormat($item['created_at']);
            $item['updated_at'] = dateFormat($item['updated_at']);
            $item['type_text'] = CommonValues::getAccountRecordType($item['type']);
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
        $row = array(
            'name'          => $this->getRequest($data, 'name'),
            'type'          => $this->getRequest($data, 'type'),
            'num'           => $this->getRequest($data, 'num','int',0),
            'gift_num'      => $this->getRequest($data, 'gift_num','int',0),
            'vip_num'       => $this->getRequest($data, 'vip_num','int',0),
            'price'         => $this->getRequest($data, 'price','double',0),
            'sort'          => $this->getRequest($data, 'sort','int',0),
            'price_tips'    => $this->getRequest($data, 'price_tips','string',''),
            'description'   => $this->getRequest($data, 'description','string',''),
            'is_disabled'   => $this->getRequest($data, 'is_disabled','int',0),
        );
        if (empty($row['name']) || empty($row['type']) || empty($row['num']) || empty($row['price'])) {
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '参数错误!');
        }

        if ($data['_id'] > 0) {
            $row['_id'] = $this->getRequest($data, '_id', 'int');
        }
        $result =  $this->productService->save($row);
        $this->adminUserService->addAdminLog(sprintf('操作充值套餐 名称%s,数量%s,价格%s,赠送%s 编号%s',
            $row['name'], $row['num'], $row['price'], $row['gift_num'], empty($row['_id']) ? $result : $row['_id']));
        return  $result;
    }

    /**
     * 获取详情
     * @param $id
     * @return mixed
     * @throws BusinessException
     */
    public function getDetail($id)
    {
        $row = $this->productService->findByID($id);
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
        $this->adminUserService->addAdminLog(sprintf('删除充值套餐  编号%s',$id));
        return $this->productService->delete($id);
    }

    /**
     * 获取有效的分组
     * @return array
     */
    public function getEnableAll()
    {
        return $this->productService->getEnableAll();
    }

}