<?php

declare(strict_types=1);

namespace App\Services;


use App\Core\Services\BaseService;
use App\Models\UserUpModel;

/**
 *  Up
 * @package App\Services
 *
 * @property  UserUpModel $userUpModel
 * @property UserService $userService
 */
class UserUpService extends BaseService
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
        return $this->userUpModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query=[])
    {
        return $this->userUpModel->count($query);
    }


    /**
     * 返回第一条数据
     * @param array $query
     * @param array $fields
     * @return array
     */
    public function findFirst($query = array(), $fields = array())
    {
        return $this->userUpModel->findFirst($query, $fields);
    }

    /**
     * 通过id查询
     * @param  $id
     * @return mixed
     */
    public function findByID($id)
    {
        return $this->userUpModel->findByID(intval($id));
    }

    /**
     * 保存数据
     * @param $data
     * @return bool|int|mixed
     */
    public function save($data)
    {
        if ($data['_id']) {
            $result= $this->userUpModel->update($data, array("_id" => $data['_id']));
        } else {
            $result= $this->userUpModel->insert($data);
        }
        return $result;
    }

    /**
     * 删除数据
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        $result= $this->userUpModel->delete(array('_id' => intval($id)));
        return $result;
    }


}