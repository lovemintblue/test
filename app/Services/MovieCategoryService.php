<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Services\BaseService;
use App\Models\MovieCategoryModel;

/**
 *  漫画分类
 * @package App\Services
 *
 * @property  MovieCategoryModel $movieCategoryModel
 */
class MovieCategoryService extends BaseService
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
        return $this->movieCategoryModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query=[])
    {
        return $this->movieCategoryModel->count($query);
    }


    /**
     * 返回第一条数据
     * @param array $query
     * @param array $fields
     * @return array
     */
    public function findFirst($query = array(), $fields = array())
    {
        return $this->movieCategoryModel->findFirst($query, $fields);
    }

    /**
     * 通过id查询
     * @param  $id
     * @return mixed
     */
    public function findByID($id)
    {
        return $this->movieCategoryModel->findByID(intval($id));
    }

    /**
     * 保存数据
     * @param $data
     * @return bool|int|mixed
     */
    public function save($data)
    {
        if ($data['_id']) {
            return $this->movieCategoryModel->update($data, array("_id" => $data['_id']));
        } else {
            return $this->movieCategoryModel->insert($data);
        }
    }

    /**
     * 删除数据
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        return $this->movieCategoryModel->delete(array('_id' => intval($id)));
    }

    /**
     * 获取所有
     * @param bool $hot
     * @param string $position
     * @return array
     */
    public function getAll($hot=false,$position='')
    {
//        $result = getCache(CacheKey::MOVIE_CATEGORIES);
//        if ($result == null) {
            $query = array();
            if($position){$query['position']=['$in'=>['all',$position]];}
            if($hot){$query['is_hot']=1;}
            $result=[];
            $items = $this->movieCategoryModel->find($query, array(), array("sort" => -1), 0, 1000);
            foreach ($items as $item) {
                $result[$item['_id']] = array(
                    'id' => $item['_id'],
                    'name' => $item['name'],
                );
            }
//            setCache(CacheKey::MOVIE_CATEGORIES, $result, 120);
//        }
        return $result;
    }
}