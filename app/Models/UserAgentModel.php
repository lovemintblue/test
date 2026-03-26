<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 用户代理
 * @package App\Models
 * @property integer _id 编号(即用户ID)
 * @property string username 用户名
 * @property double balance 余额
 * @property double amount 总收益,只增不减
 * @property double bill 总业绩,只增不减
 * @property integer level1_num 一级代理数量
 * @property integer level2_num 二级代理数量
 * @property integer level3_num 三级代理数量
 * @property double level1_bill 一级代理业绩
 * @property double level2_bill 二级代理业绩
 * @property double level3_bill 三级代理业绩
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class UserAgentModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='user_agent'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}