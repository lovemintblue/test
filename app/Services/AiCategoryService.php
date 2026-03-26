<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Services\BaseService;
use App\Models\AiCategoryModel;

/**
 * Ai分类
 * @package App\Services
 *
 * @property  AiCategoryModel $aiCategoryModel
 */
class AiCategoryService extends BaseService
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
        return $this->aiCategoryModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query=[])
    {
        return $this->aiCategoryModel->count($query);
    }


    /**
     * 返回第一条数据
     * @param array $query
     * @param array $fields
     * @return array
     */
    public function findFirst($query = array(), $fields = array())
    {
        return $this->aiCategoryModel->findFirst($query, $fields);
    }

    /**
     * 通过id查询
     * @param  $id
     * @return mixed
     */
    public function findByID($id)
    {
        return $this->aiCategoryModel->findByID(intval($id));
    }

    /**
     * 保存数据
     * @param $data
     * @return bool|int|mixed
     */
    public function save($data)
    {
        if ($data['_id']) {
            return $this->aiCategoryModel->update($data, array("_id" => $data['_id']));
        } else {
            return $this->aiCategoryModel->insert($data);
        }
    }

    /**
     * 删除数据
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        return $this->aiCategoryModel->delete(array('_id' => intval($id)));
    }

    /**
     * 获取所有
     * @param bool $hot
     * @param string $position
     * @param string $isCache
     * @return array
     */
    public function getAll($hot=false,$position='',$isCache=false)
    {
        $cacheKey = 'ai_category_'.$position.'_'.$hot;
        $result = getCache($cacheKey);
        if ($result == null) {
            $query = array();
            if($position){$query['position']=['$in'=>['all',$position]];}
            if($hot){$query['is_hot']=1;}
            $result=[];
            $items = $this->aiCategoryModel->find($query, array(), array("sort" => -1), 0, 1000);
            foreach ($items as $item) {
                $result[$item['_id']] = array(
                    'id' => $item['_id'],
                    'name' => $item['name'],
                    'position' => $item['position'],
                );
            }
            if($isCache){
                setCache($cacheKey, $result, 120);
            }
        }
        return $result;
    }
}