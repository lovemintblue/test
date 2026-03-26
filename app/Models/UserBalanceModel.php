<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 用户余额
 * @package App\Models
 * @property integer _id 编号
 * @property string device_type 设备类型
 * @property string username 用户名
 * @property string type 余额类型 ai_girlfriend
 * @property integer balance 冻结余额
 * @property string info json字符串
 * @property integer status 状态 1游戏中 2下分处理中
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class UserBalanceModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='user_balance'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}