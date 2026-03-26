<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 充值表
 * @package App\Models
 * @property integer _id 编号
 * @property string order_sn 订单编号
 * @property string trade_sn 支付编号
 * @property integer user_id 用户编号
 * @property string device_type 设备类型
 * @property string username 用户名
 * @property string record_type 类型 point金币
 * @property integer status 状态0处理中 1成功 -1失败
 * @property double amount 金额
 * @property double real_amount 实际金额
 * @property integer product_id 产品编号
 * @property integer give 赠送数量
 * @property integer vip 赠送VIP天数
 * @property integer num 数量
 * @property double fee 费率
 * @property integer pay_id 支付方式编号
 * @property string pay_name 支付方式
 * @property integer pay_at 支付时间
 * @property double pay_rate 支付费率
 * @property string pay_date 支付日期
 * @property string channel_name 渠道
 * @property integer register_at 注册时间
 * @property string register_date 注册时间
 * @property integer is_new_user 是否新用户
 * @property string register_ip 注册ip
 * @property string created_ip ip
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class RechargeModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='recharge'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}