<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Services\BaseService;
use App\Models\ArticleCategoryModel;
use App\Utils\TreeUtil;

/**
 *  文章分类
 * @package App\Services
 *
 * @property  ArticleCategoryModel $articleCategoryModel
 */
class ArticleCategoryService extends BaseService
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
        return $this->articleCategoryModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query=[])
    {
        return $this->articleCategoryModel->count($query);
    }


    /**
     * 返回第一条数据
     * @param array $query
     * @param array $fields
     * @return array
     */
    public function findFirst($query = array(), $fields = array())
    {
        return $this->articleCategoryModel->findFirst($query, $fields);
    }

    /**
     * 通过id查询
     * @param  $id
     * @return mixed
     */
    public function findByID($id)
    {
        return $this->articleCategoryModel->findByID(intval($id));
    }

    /**
     * 保存数据
     * @param $data
     * @return bool|int|mixed
     */
    public function save($data)
    {
        if ($data['_id']) {
            return $this->articleCategoryModel->update($data, array("_id" => $data['_id']));
        } else {
            return $this->articleCategoryModel->insert($data);
        }
    }

    /**
     * 删除数据
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        return $this->articleCategoryModel->delete(array('_id' => intval($id)));
    }

    /**
     * 获取所有资源
     * @return array
     */
    public function getAll()
    {
        $query = array();
        $items = $this->articleCategoryModel->find($query, array(), array("sort" => -1), 0, 1000);
        $data = array();
        foreach ($items as $item) {
            $data[$item['code']] = array(
                'id' => $item['_id'],
                'code' => $item['code'],
                'parent_id' => $item['parent_id'],
                'name' => $item['name'],
                'img' => $item['img'],
                'sort' => $item['sort']
            );
        }
        return $data;
    }

    /**
     * 获取资源树状
     * @return array
     */
    public function getTree()
    {

        $data = $this->getAll();
        $treeUtil = new TreeUtil($data);
        return  $treeUtil->getTree('child');
    }

    /**
     * 获取资源树状
     * @return string
     */
    public function getTreeOptions()
    {

        $data = $this->getAll();
        $treeUtil = new TreeUtil(array_values($data));
        return  $treeUtil->getHtmlOptions();
    }

    /**
     * 获取资源树状
     * @return string
     */
    public function getTreeCodeOptions()
    {
        $data = $this->getAll();
        $treeUtil = new TreeUtil(array_values($data));
        return  $treeUtil->getHtmlOptions('','&nbsp;&nbsp;',false,'code');
    }
}