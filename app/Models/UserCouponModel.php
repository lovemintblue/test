<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 用户优惠券
 * @package App\Models
 * @property integer _id 编号
 * @property string name 名称
 * @property integer type 优惠券类型 观影:movie 裸聊:naked
 * @property integer user_id 用户id
 * @property string code_key 优惠券key
 * @property string code 优惠券
 * @property integer status 状态 0未使用 1已使用 -1作废
 * @property integer money 金额
 * @property integer can_use_num 使用次数
 * @property integer used_num 已使用次数
 * @property integer expired_at 有效期 过期无效
 * @property string label 使用日期
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class UserCouponModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='user_coupon'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}