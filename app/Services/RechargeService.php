<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\CommonValues;
use App\Core\Services\BaseService;
use App\Models\RechargeModel;
use App\Utils\LogUtil;

/**
 *  用户充值
 * @package App\Services
 *
 * @property  RechargeModel $rechargeModel
 */
class RechargeService extends BaseService
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
        return $this->rechargeModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 求和
     * @param $query
     * @return integer
     */
    public function sum($query)
    {
        return $this->rechargeModel->aggregate($query);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query)
    {
        return $this->rechargeModel->count($query);
    }


    /**
     * 返回第一条数据
     * @param array $query
     * @param array $fields
     * @return array
     */
    public function findFirst($query = array(), $fields = array())
    {
        return $this->rechargeModel->findFirst($query, $fields);
    }

    /**
     * 通过id查询
     * @param  $id
     * @return mixed
     */
    public function findByID($id)
    {
        return $this->rechargeModel->findByID(intval($id));
    }

    /**
     * 保存数据
     * @param $data
     * @return bool|int|mixed
     */
    public function save($data)
    {
        if ($data['_id']) {
            return $this->rechargeModel->update($data, array("_id" => $data['_id']));
        } else {
            return $this->rechargeModel->insert($data);
        }
    }
}