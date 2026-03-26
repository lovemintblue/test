<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 用户优惠券使用记录
 * @package App\Models
 * @property integer _id 编号
 * @property integer user_id 用户id
 * @property integer code_id 优惠券id
 * @property string code 优惠券
 * @property string label 使用日期
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class UserCouponLogModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='user_coupon_log'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}