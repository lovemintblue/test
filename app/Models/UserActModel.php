<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 用户行为统计
 * @package App\Models
 * @property string _id 编号
 * @property integer user_id 用户编号
 * @property string username 用户名
 * @property string channel_name 渠道
 * @property integer register_at 注册时间
 * @property array act 用户行为
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class UserActModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='user_act'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}