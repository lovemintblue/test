<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Services\BaseService;
use App\Models\AdminRoleModel;

/**
 * 用户角色
 * @package App\Services
 *
 * @property  AdminRoleModel $adminRoleModel
 */
class AdminRoleService extends BaseService
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
        return $this->adminRoleModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query=[])
    {
        return $this->adminRoleModel->count($query);
    }


    /**
     * 返回第一条数据
     * @param array $query
     * @param array $fields
     * @return array
     */
    public function findFirst($query = array(), $fields = array())
    {
        return $this->adminRoleModel->findFirst($query, $fields);
    }

    /**
     * 通过id查询
     * @param  $id
     * @return mixed
     */
    public function findByID($id)
    {
        return $this->adminRoleModel->findByID(intval($id));
    }

    /**
     * 保存数据
     * @param $data
     * @return bool|int|mixed
     */
    public function save($data)
    {
        if ($data['_id']) {
            return $this->adminRoleModel->update($data, array("_id" => $data['_id']));
        } else {
            return $this->adminRoleModel->insert($data);
        }
    }

    /**
     * 删除数据
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        return $this->adminRoleModel->delete(array('_id' => intval($id)));
    }

    /**
     * 获取所有角色
     * @return array
     */
    public function getRoles()
    {
        $items = $this->getList(array(),array(),array("sort"=>1),0,100);
        $result = array();
        $result[0] = array(
            '_id' => 0,
            'name' => '超级管理员',
            'rights'=>'',
            'is_disabled'=>0
        );
        foreach ($items as $item)
        {
            $result[$item['_id']] = array(
                '_id' => $item['_id'],
                'name' => $item['name'],
                'rights' => $item['rights'],
                'is_disabled'=> $item['is_disabled']
            );
        }
        return $result;
    }

}