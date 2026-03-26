<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Services\BaseService;
use App\Models\MessageModel;

/**
 * Class MessageService
 * @package App\Services
 * @property MessageModel $messageModel
 */
class MessageService extends BaseService
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
        return $this->messageModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query)
    {
        return $this->messageModel->count($query);
    }
}