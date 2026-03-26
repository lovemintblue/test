<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 用户嗜好
 * @package App\Models
 * @property integer _id 编号
 * @property string name 名称
 * @property integer value 权重
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class UserHobbyModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='user_hobby'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}