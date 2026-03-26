<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 用户关注
 * @package App\Models
 * @property integer _id 编号
 * @property integer user_id 编号
 * @property integer home_id 编号
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class UserFollowModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='user_follow'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}