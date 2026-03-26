<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 用户找回记录
 * @package App\Models
 * @property integer _id 编号
 * @property integer user_id 用户id
 * @property integer to_user_id 目标用户id
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class UserFindLogModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='user_find_log'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}