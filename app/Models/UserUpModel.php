<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 热门UP
 * @package App\Models
 * @property string _id 编号
 * @property integer user_id 用户编号
 * @property integer username 用户名
 * @property string sort 排序
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class UserUpModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='user_up'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}