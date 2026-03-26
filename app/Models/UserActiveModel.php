<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 用户活跃
 * @package App\Models
 * @property integer _id 用户编号
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间-活跃时间
 */
class UserActiveModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='user_active'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}