<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\CacheKey;
use App\Constants\CommonValues;
use App\Core\Services\BaseService;
use App\Models\BlockPositionModel;

/**
 *  模块位置分类
 * @package App\Services
 *
 * @property  BlockPositionModel $blockPositionModel
 */
class BlockPositionService extends BaseService
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
        return $this->blockPositionModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query=[])
    {
        return $this->blockPositionModel->count($query);
    }


    /**
     * 返回第一条数据
     * @param array $query
     * @param array $fields
     * @return array
     */
    public function findFirst($query = array(), $fields = array())
    {
        return $this->blockPositionModel->findFirst($query, $fields);
    }

    /**
     * 通过id查询
     * @param  $id
     * @return mixed
     */
    public function findByID($id)
    {
        return $this->blockPositionModel->findByID(intval($id));
    }

    /**
     * 保存数据
     * @param $data
     * @return bool|int|mixed
     */
    public function save($data)
    {
        if ($data['_id']) {
            return $this->blockPositionModel->update($data, array("_id" => $data['_id']));
        } else {
            return $this->blockPositionModel->insert($data);
        }
    }

    /**
     * 删除数据
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        return $this->blockPositionModel->delete(array('_id' => intval($id)));
    }

    /**
     * 获取所有资源
     * @return array
     */
    public function getAll()
    {
        $result  = getCache(CacheKey::BLOCK_POSITION_KEY);
        if(empty($result)){
            $query = array();
            $items = $this->blockPositionModel->find($query, array(), array("sort" => -1), 0, 1000);
            $result = array();
            $groupArr = CommonValues::getBlockPositionGroup();
            foreach ($items as $item) {
                $result[$item['code']] = array(
                    'id' => $item['_id'],
                    'name' => $item['name'],
                    'code' => $item['code'],
                    'group' => $item['group'],
                    'group_name' => $groupArr[$item['group']],
                    'sort' => $item['sort'],
                    'filter'=>strval($item['filter'])
                );
            }
            setCache(CacheKey::BLOCK_POSITION_KEY,$result,120);
        }
        return $result;
    }

}