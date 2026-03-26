<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 收款单表
 * @package App\Models
 * @property integer _id 编号
 * @property string order_sn 订单编号
 * @property string trade_sn 支付编号
 * @property integer user_id 用户编号
 * @property string device_type 设备类型
 * @property double price 金额
 * @property double real_price 实际金额
 * @property string record_type 类型vip point金币 game游戏
 * @property integer object_id 产品编号
 * @property integer pay_id 支付方式编号
 * @property string pay_name 支付方式
 * @property integer pay_at 支付时间
 * @property double pay_rate 支付手续费
 * @property string pay_date 支付日期
 * @property string channel_name 渠道名称
 * @property integer register_at 注册时间
 * @property integer order_at 订单时间
 * @property string register_date 注册日期
 * @property integer is_new_user 是否新用户
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class CollectionsModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='collections'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}