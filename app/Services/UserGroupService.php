<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\CacheKey;
use App\Constants\CommonValues;
use App\Core\Services\BaseService;
use App\Models\UserGroupModel;

/**
 *  用户组
 * @package App\Services
 *
 * @property  UserGroupModel $userGroupModel
 */
class UserGroupService extends BaseService
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
        return $this->userGroupModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query)
    {
        return $this->userGroupModel->count($query);
    }


    /**
     * 返回第一条数据
     * @param array $query
     * @param array $fields
     * @return array
     */
    public function findFirst($query = array(), $fields = array())
    {
        return $this->userGroupModel->findFirst($query, $fields);
    }

    /**
     * 通过id查询
     * @param  $id
     * @return mixed
     */
    public function findByID($id)
    {
        return $this->userGroupModel->findByID(intval($id));
    }

    /**
     * 保存数据
     * @param $data
     * @return bool|int|mixed
     */
    public function save($data)
    {
        if ($data['_id']) {
            $result = $this->userGroupModel->update($data, array("_id" => $data['_id']));
        } else {
            $result = $this->userGroupModel->insert($data);
        }
        delCache(CacheKey::USER_GROUP);
        return $result;
    }

    /**
     * 删除数据
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        $result = $this->userGroupModel->delete(array('_id' => intval($id)));
        delCache(CacheKey::USER_GROUP);
        return $result;
    }

    /**
     * 获取所有
     * @return array
     */
    public function getAll()
    {
        $result = getCache(CacheKey::USER_GROUP);
        if ($result == null) {
            $items = $this->getList(array(), array(), array("sort" => -1), 0, 1000);
            $result = array();
            foreach ($items as $item) {
                $result[$item['_id']] = array(
                    'id' => strval($item['_id']),
                    'name' => $item['name'],
                    'description' => strval($item['description']),
                    'img' => strval($item['img']),
                    'group' => strval($item['group']),
                    'level' => strval($item['level'] * 1),
                    'promotion_type' => strval($item['promotion_type']?:0),
                    'rate' => strval(intval($item['rate'])),
                    'gift_num' => strval($item['gift_num']?:0),
                    'coupon_num' => strval($item['coupon_num']?:0),
                    'price' => strval($item['price'] * 1),
                    'old_price' => strval($item['old_price'] * 1),
                    'day_num' => strval($item['day_num'] * 1),
                    'download_num' => strval($item['download_num'] * 1),
                    'day_tips' => strval($item['day_tips']),
                    'price_tips' => strval($item['price_tips']),
                    'is_disabled' => $item['is_disabled'] ? 'y' : 'n',
                    'rights' => empty($item['rights'])?[]:$item['rights']
                );
            }
            setCache(CacheKey::USER_GROUP, $result, 180);
        }
        return $result;
    }

    /**
     * 获取所有可用用户套餐
     * @return array
     */
    public function getEnableAll()
    {
        $result = $this->getAll();
        foreach ($result as $index => $item) {
            if ($item['is_disabled'] == 'y') {
                unset($result[$index]);
            }
        }
        return $result?array_values($result):array();
    }

    /**
     * 获取用户组信息
     * @param $groupId
     * @return mixed|null
     */
    public function getInfo($groupId)
    {
        $groups = $this->getAll();
        return isset($groups[$groupId]) ? $groups[$groupId] : null;
    }


}