<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Services\BaseService;
use App\Models\AuthorityModel;
use App\Utils\TreeUtil;

/**
 * 配置操作
 * @package App\Services
 *
 * @property  AuthorityModel $authorityModel
 */
class AuthorityService extends BaseService
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
        return $this->authorityModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query=[])
    {
        return $this->authorityModel->count($query);
    }


    /**
     * 返回第一条数据
     * @param array $query
     * @param array $fields
     * @return array
     */
    public function findFirst($query = array(), $fields = array())
    {
        return $this->authorityModel->findFirst($query, $fields);
    }

    /**
     * 通过id查询
     * @param  $id
     * @return mixed
     */
    public function findByID($id)
    {
        return $this->authorityModel->findByID(intval($id));
    }

    /**
     * 保存数据
     * @param $data
     * @return bool|int|mixed
     */
    public function save($data)
    {
        if ($data['_id']) {
            return $this->authorityModel->update($data, array("_id" => $data['_id']));
        } else {
            return $this->authorityModel->insert($data);
        }
    }

    /**
     * 删除数据
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        return $this->authorityModel->delete(array('_id' => intval($id)));
    }

    /**
     * 获取所有资源
     * @return array
     */
    public function getAll()
    {
        $query = array();
        $items = $this->authorityModel->find($query, array(), array("sort" => -1), 0, 1000);
        $data = array();
        foreach ($items as $item) {
            $data[] = array(
                'id' => $item['_id'],
                'name' => $item['name'],
                'parent_id' => $item['parent_id'],
                'key' => $item['key'],
                'class_name' => $item['class_name'],
                'is_menu' => $item['is_menu'],
                'link' => $item['link']
            );
        }
        return $data;
    }


    /**
     * 获取html
     * @return string
     */
    public function getTreeOptions()
    {
        $data = $this->getAll();
        $treeUtil = new TreeUtil($data,'id','name','parent_id');
        return $treeUtil->getHtmlOptions('','&nbsp;&nbsp;&nbsp;');
    }


    /**
     * 获取树形结构权限
     * @return array
     */
    public function getTree()
    {
        $data = $this->getAll();
        $treeUtil = new TreeUtil($data,'id','name','parent_id');
        return $treeUtil->getTree();
    }

}