<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\CacheKey;
use App\Core\Services\BaseService;
use App\Models\PostCategoryModel;

/**
 *  帖子板块
 * @package App\Services
 * @property CommonService $commonService
 * @property  PostCategoryModel $postCategoryModel
 */
class PostCategoryService extends BaseService
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
        return $this->postCategoryModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query)
    {
        return $this->postCategoryModel->count($query);
    }


    /**
     * 返回第一条数据
     * @param array $query
     * @param array $fields
     * @return array
     */
    public function findFirst($query = array(), $fields = array())
    {
        return $this->postCategoryModel->findFirst($query, $fields);
    }

    /**
     * 通过id查询
     * @param  $id
     * @return mixed
     */
    public function findByID($id)
    {
        return $this->postCategoryModel->findByID(intval($id));
    }

    /**
     * 保存数据
     * @param $data
     * @return bool|int|mixed
     */
    public function save($data)
    {
        if ($data['_id']) {
            $result= $this->postCategoryModel->update($data, array("_id" => $data['_id']));
        } else {
            $data['post_count']=0;
            $data['post_click']=0;
            $data['follow']    =0;
            $result= $this->postCategoryModel->insert($data);
        }
        delCache(CacheKey::POST_CATEGORY);
        return $result;
    }

    /**
     * 删除数据
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        $result= $this->postCategoryModel->delete(array('_id' => intval($id)));
        delCache(CacheKey::POST_CATEGORY);
        return $result;
    }

    /**
     * 获取所有分类
     * @return array
     */
    public function getAll()
    {
        $result = getCache(CacheKey::POST_CATEGORY);
        if ($result == null) {
            $result=[];
            $items = $this->postCategoryModel->find(array(), array(), array("sort" => -1), 0, 1000);
            foreach ($items as $item) {
                $result[$item['_id']] = array(
                    'id' => strval($item['_id']*1),
                    'name' => $item['name'],
                    'img'  => strval($item['img']),
                    'block_id' => strval($item['block_id']*1),
                    'block_name' => strval($item['block_name']),
                    'position' => strval($item['position']),
                    'post_count' => strval($item['post_count']*1),
                    'post_click' => strval($item['post_click']*1),
                    'follow' => strval($item['follow']*1),
                    'description' => strval($item['description'])
                );
            }
            setCache(CacheKey::POST_CATEGORY, $result, 120);
        }
        return $result;
    }
}