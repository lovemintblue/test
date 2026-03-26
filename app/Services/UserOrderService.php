<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Services\BaseService;
use App\Models\UserOrderModel;

/**
 *  VIP用户订单
 * @package App\Services
 *
 * @property  UserOrderModel $userOrderModel
 */
class UserOrderService extends BaseService
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
        return $this->userOrderModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * @param $pipeline
     * @return mixed
     */
    public function sum($pipeline)
    {
        return $this->userOrderModel->aggregate($pipeline);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query)
    {
        return $this->userOrderModel->count($query);
    }

    /**
     * 通过id查询
     * @param  $id
     * @return mixed
     */
    public function findByID($id)
    {
        return $this->userOrderModel->findByID(intval($id));
    }

    /**
     * 保存数据
     * @param $data
     * @return bool|int|mixed
     */
    public function save($data)
    {
        if ($data['_id']) {
            $this->userOrderModel->update($data, array("_id" => $data['_id']));
            $id = $data['_id'];
        } else {
            $id = $this->userOrderModel->insert($data);
        }
        return $id;
    }
}