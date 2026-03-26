<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\CacheKey;
use App\Core\Services\BaseService;
use App\Models\CartoonCategoryModel;
use App\Models\CartoonTagModel;
use App\Models\ComicsTagModel;
use App\Models\MovieCategoryModel;
use App\Models\MovieTagModel;

/**
 *  漫画标签
 * @package App\Services
 *
 * @property  ComicsTagModel  $comicsTagModel
 */
class ComicsTagService extends BaseService
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
        return $this->comicsTagModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query=[])
    {
        return $this->comicsTagModel->count($query);
    }


    /**
     * 返回第一条数据
     * @param array $query
     * @param array $fields
     * @return array
     */
    public function findFirst($query = array(), $fields = array())
    {
        return $this->comicsTagModel->findFirst($query, $fields);
    }

    /**
     * 通过id查询
     * @param  $id
     * @return mixed
     */
    public function findByID($id)
    {
        return $this->comicsTagModel->findByID(intval($id));
    }

    /**
     * 保存数据
     * @param $data
     * @return bool|int|mixed
     */
    public function save($data)
    {
        if ($data['_id']) {
            return $this->comicsTagModel->update($data, array("_id" => $data['_id']));
        } else {
            return $this->comicsTagModel->insert($data);
        }
    }

    /**
     * 插入数据
     * @param $data
     * @return bool|int
     */
    public function insert($data)
    {
        return $this->comicsTagModel->insert($data);
    }

    /**
     * 删除数据
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        return $this->comicsTagModel->delete(array('_id' => intval($id)));
    }

    /**
     * 获取所有
     * @param bool $hot
     * @return array
     */
    public function getAll($hot=false)
    {
        $cacheKey = 'comics_tags_'.'_'.$hot;
        $result = getCache($cacheKey);
        if ($result == null) {
            $query = array();
            if($hot){$query['is_hot']=1;}
            $result=[];
            $items = $this->comicsTagModel->find($query, array(), array("sort" => -1), 0, 1000);
            foreach ($items as $item) {
                $result[$item['_id']] = array(
                    'id' => $item['_id'],
                    'name' => $item['name'],
                    'count'=> strval($item['count']*1)
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
        $result=[];
        $items = $this->comicsTagModel->find(array(), array(), array("sort" => -1), 0, 1000);
        foreach ($items as $item) {
            $result[$item['attribute']][] = array(
                'id' => $item['_id'],
                'name' => $item['name'],
            );
        }
        return $result;
    }
}