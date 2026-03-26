<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\CacheKey;
use App\Core\Services\BaseService;
use App\Models\PostBlockModel;

/**
 *  视频模块
 * @package App\Services
 * @property CommonService $commonService
 * @property PostBlockModel $postBlockModel
 */
class PostBlockService extends BaseService
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
        return $this->postBlockModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query)
    {
        return $this->postBlockModel->count($query);
    }


    /**
     * 返回第一条数据
     * @param array $query
     * @param array $fields
     * @return array
     */
    public function findFirst($query = array(), $fields = array())
    {
        return $this->postBlockModel->findFirst($query, $fields);
    }

    /**
     * 通过id查询
     * @param  $id
     * @return mixed
     */
    public function findByID($id)
    {
        return $this->postBlockModel->findByID(intval($id));
    }

    /**
     * 保存数据
     * @param $data
     * @return bool|int|mixed
     */
    public function save($data)
    {
        if ($data['_id']) {
            $result = $this->postBlockModel->update($data, array("_id" => $data['_id']));
        } else {
            $result = $this->postBlockModel->insert($data);
        }
        delCache(CacheKey::POST_BLOCK);
        return $result;
    }

    /**
     * 删除数据
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        $result = $this->postBlockModel->delete(array('_id' => intval($id)));
        delCache(CacheKey::POST_BLOCK);
        return $result;
    }

    /**
     * 获取所有模块
     * @param string $position
     * @return array|mixed
     */
    public function getAll($position='')
    {
        $keyName=CacheKey::POST_BLOCK;
        $result = getCache($keyName);
        if (is_null($result)) {
            $items = $this->postBlockModel->find(array('is_disabled'=>0), array(), array('sort' => -1), 0, 100);
            foreach ($items as $item) {
                $result[$item['_id']] = array(
                    'id'     => strval($item['_id']),
                    'name'   => strval($item['name']),
                    'position' => strval($item['position']),
                    'is_ai'  => empty($item['is_ai'])?'n':'y',
                    'sort'  => strval($item['sort']*1)
                );
            }
            setCache($keyName, $result, 350);
        }
        if($position){
            foreach ($result as $index=>$item)
            {
                if($item['position']!=$position){
                    unset($result[$index]);
                }
            }
        }
        return $result;
    }
}