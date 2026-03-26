<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\CacheKey;
use App\Core\Services\BaseService;
use App\Models\CartoonCategoryModel;
use App\Models\CartoonTagModel;
use App\Models\MovieCategoryModel;
use App\Models\MovieTagModel;

/**
 *  漫画标签
 * @package App\Services
 *
 * @property  MovieTagModel $movieTagModel
 */
class MovieTagService extends BaseService
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
        return $this->movieTagModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query=[])
    {
        return $this->movieTagModel->count($query);
    }


    /**
     * 返回第一条数据
     * @param array $query
     * @param array $fields
     * @return array
     */
    public function findFirst($query = array(), $fields = array())
    {
        return $this->movieTagModel->findFirst($query, $fields);
    }

    /**
     * 通过id查询
     * @param  $id
     * @return mixed
     */
    public function findByID($id)
    {
        return $this->movieTagModel->findByID(intval($id));
    }

    /**
     * 保存数据
     * @param $data
     * @return bool|int|mixed
     */
    public function save($data)
    {
        if ($data['_id']) {
            return $this->movieTagModel->update($data, array("_id" => $data['_id']));
        } else {
            return $this->movieTagModel->insert($data);
        }
    }

    /**
     * 插入数据
     * @param $data
     * @return bool|int
     */
    public function insert($data)
    {
        return $this->movieTagModel->insert($data);
    }

    /**
     * 删除数据
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        return $this->movieTagModel->delete(array('_id' => intval($id)));
    }

    /**
     * 获取所有
     * @param bool $hot
     * @param string $position
     * @return array
     */
    public function getAll($hot=false,$position='')
    {
        $cacheKey = 'movie_tags_'.$position.'_'.$hot;
        $result = getCache($cacheKey);
        if ($result == null) {
            $query = array();
            if($position){$query['series']=['$in'=>['all',$position]];}
            if($hot){$query['is_hot']=1;}
            $result=[];
            $items = $this->movieTagModel->find($query, array(), array("sort" => -1), 0, 1000);
            foreach ($items as $item) {
                $result[$item['_id']] = array(
                    'id' => $item['_id'],
                    'name' => $item['name'],
                );
            }
            setCache($cacheKey, $result, 120);
        }
        return $result;
    }

    /**
     * 获取分组属性
     * @return array
     */
    public function getGroupAttrAll()
    {
        $query = ['parent_id'=>0];
        $result=[];
        $items = $this->movieTagModel->find($query, array(), array("sort" => -1), 0, 1000);
        foreach ($items as $item) {
            $result[$item['attribute']][] = array(
                'id' => $item['_id'],
                'name' => $item['name'],
            );
        }
        return $result;
    }

    /**
     * 获取同级别标签
     * @param $name
     * @return string
     */
    public function findBrotherByName($name)
    {
        $tag = $this->movieTagModel->findFirst(['name'=>strtolower($name)]);
        if(empty($tag)){
            return '';
        }
        $ids = '';
        if($tag['parent_id']>0){
            //获取一级标签
            $parentTag = $this->movieTagModel->findByID($tag['parent_id']);
            $ids = $parentTag['_id'];
        }elseif ($tag['parent_id']==0){
            $ids = $tag['_id'];
        }
        return $ids;
    }
}