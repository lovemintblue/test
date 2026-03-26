<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\CacheKey;
use App\Core\Services\BaseService;
use App\Models\ProductModel;

/**
 *  金币套餐
 * @package App\Services
 *
 * @property  ProductModel $productModel
 */
class ProductService extends BaseService
{

    /**
     * 获取列表
     * @param array $query
     * @param array $fields
     * @param array $sort
     * @param int $skip
     * @param int $limit
     * @return array
     */
    public function getList($query = array(), $fields = array(), $sort = array(), $skip = 0, $limit = 10)
    {
        return $this->productModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query)
    {
        return $this->productModel->count($query);
    }


    /**
     * 返回第一条数据
     * @param array $query
     * @param array $fields
     * @return array
     */
    public function findFirst($query = array(), $fields = array())
    {
        return $this->productModel->findFirst($query, $fields);
    }

    /**
     * 通过id查询
     * @param  $id
     * @return mixed
     */
    public function findByID($id)
    {
        return $this->productModel->findByID(intval($id));
    }

    /**
     * 保存数据
     * @param $data
     * @return bool|int|mixed
     */
    public function save($data)
    {
        if ($data['_id']) {
            $result = $this->productModel->update($data, array("_id" => $data['_id']));
        } else {
            $result = $this->productModel->insert($data);
        }
        delCache(CacheKey::PRODUCT);
        return $result;
    }

    /**
     * 删除数据
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        $result = $this->productModel->delete(array('_id' => intval($id)));
        delCache(CacheKey::PRODUCT);
        return $result;
    }

    /**
     * 获取所有
     * @return array
     */
    public function getAll()
    {
        $result = getCache(CacheKey::PRODUCT);
        if ($result == null) {
            $items = $this->getList(array(), array(), array("sort" => 1), 0, 1000);
            $result = array();
            foreach ($items as $item) {
                $result[$item['_id']] = array(
                    'id' => strval($item['_id']),
                    'name' => strval($item['name']),
                    'type' => strval($item['type']),
                    'num' => strval($item['num'] * 1),
                    'gift_num' => strval($item['gift_num'] * 1),
                    'vip_num' => strval($item['vip_num'] * 1),
                    'description' => strval($item['description']),
                    'price_tips' => strval($item['price_tips']),
                    'promotion_type' => strval($item['promotion_type']),
                    'price' => strval($item['price'] * 1),
                    'is_disabled' => $item['is_disabled'] ? 'y' : 'n'
                );
            }
            setCache(CacheKey::PRODUCT, $result, 180);
        }
        return $result;
    }

    /**
     * 获取所有可用产品
     * @param string $type
     * @return array
     */
    public function getEnableAll($type = 'point')
    {
        $result = $this->getAll();
        foreach ($result as $index => $item) {
            if ($item['is_disabled'] == 'y' || $item['type'] != $type) {
                unset($result[$index]);
            }
        }
        return array_values($result);
    }

    /**
     * 获取产品组信息
     * @param $productId
     * @return mixed|null
     */
    public function getInfo($productId)
    {
        $products = $this->getAll();
        return isset($products[$productId]) ? $products[$productId] : null;
    }
}