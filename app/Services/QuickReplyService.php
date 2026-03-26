<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Services\BaseService;
use App\Models\QuickReplyModel;

/**
 * Class QuickReplyService
 * @package App\Services
 * @property QuickReplyModel $quickReplyModel
 */
class QuickReplyService extends BaseService
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
        return $this->quickReplyModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query)
    {
        return $this->quickReplyModel->count($query);
    }


    /**
     * 通过id查询
     * @param  $id
     * @return mixed
     */
    public function findByID($id)
    {
        return $this->quickReplyModel->findByID(intval($id));
    }

    /**
     * 保存数据
     * @param $data
     * @return bool|int|mixed
     */
    public function save($data)
    {
        if ($data['_id']) {
            return $this->quickReplyModel->update($data, array("_id" => $data['_id']));
        } else {
            return $this->quickReplyModel->insert($data);
        }
    }

    /**
     * 删除数据
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        return $this->quickReplyModel->delete(array('_id' => intval($id)));
    }


    /**
     * 获取所有
     * @return array
     */
    public function getAll()
    {
        return $this->quickReplyModel->find(array(), array(), array('sort' => -1), 0, 1000);
    }
}