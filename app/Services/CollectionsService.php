<?php


namespace App\Services;


use App\Core\Services\BaseService;
use App\Models\CollectionsModel;

/**
 * Class CollectionsService
 * @property CollectionsModel $collectionsModel
 * @package App\Services
 */
class CollectionsService extends BaseService
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
        return $this->collectionsModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 求和
     * @param $query
     * @return integer
     */
    public function sum($query)
    {
        return $this->collectionsModel->aggregate($query);
    }


    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query)
    {
        return $this->collectionsModel->count($query);
    }


    /**
     * 返回第一条数据
     * @param array $query
     * @param array $fields
     * @return array
     */
    public function findFirst($query = array(), $fields = array())
    {
        return $this->collectionsModel->findFirst($query, $fields);
    }

    /**
     * 通过id查询
     * @param  $id
     * @return mixed
     */
    public function findByID($id)
    {
        return $this->collectionsModel->findByID(intval($id));
    }


    /**
     * 删除数据
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        return $this->collectionsModel->delete(array('_id' => intval($id)));
    }
}