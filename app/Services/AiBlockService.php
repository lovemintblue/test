<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\CacheKey;
use App\Core\Services\BaseService;
use App\Models\AiBlockModel;

/**
 * AI功能模块
 * @package App\Services
 * @property AiBlockModel $aiBlockModel
 */
class AiBlockService extends BaseService
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
        return $this->aiBlockModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query)
    {
        return $this->aiBlockModel->count($query);
    }


    /**
     * 返回第一条数据
     * @param array $query
     * @param array $fields
     * @return array
     */
    public function findFirst($query = array(), $fields = array())
    {
        return $this->aiBlockModel->findFirst($query, $fields);
    }

    /**
     * 通过id查询
     * @param  $id
     * @return mixed
     */
    public function findByID($id)
    {
        return $this->aiBlockModel->findByID(intval($id));
    }

    /**
     * 保存数据
     * @param $data
     * @return bool|int|mixed
     */
    public function save($data)
    {
        if ($data['_id']) {
            $result = $this->aiBlockModel->update($data, array("_id" => $data['_id']));
        } else {
            $result = $this->aiBlockModel->insert($data);
        }
        delCache(CacheKey::AI_BLOCK);
        return $result;
    }

    /**
     * 删除数据
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        $result = $this->aiBlockModel->delete(array('_id' => intval($id)));
        delCache(CacheKey::AI_BLOCK);
        return $result;
    }

    /**
     * 获取所有模块
     * @return array|mixed
     */
    public function getAll()
    {
        $keyName=CacheKey::AI_BLOCK;
        $result = getCache($keyName);
        if (is_null($result)) {
            $items = $this->aiBlockModel->find(array('is_disabled'=>0), array(), array('sort' => -1), 0, 100);
            foreach ($items as $item) {
                $result[$item['_id']] = array(
                    'id'     => strval($item['_id']),
                    'name'   => strval($item['name']),
                    'url'   => strval($item['url']),
                    'sort'   => strval($item['sort']*1),
                    'ico'    => strval($item['ico']),
                    'img_x'  => strval($item['img_x']),
                    'position'=> strval($item['position']),
                    'min_version'=> strval($item['min_version']),
                );
            }
            setCache($keyName, $result, 350);
        }
        return $result;
    }
}