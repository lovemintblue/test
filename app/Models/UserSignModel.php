<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 用户签到
 * @package App\Models
 * @property string _id 编号
 * @property integer user_id 用户编号
 * @property array exchanges 用户兑换情况
 * @property array days 当月签到（[1,2,3,4]）
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class UserSignModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='user_sign'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}