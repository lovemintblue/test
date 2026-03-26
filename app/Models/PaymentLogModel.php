<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 支付日志
 * @package App\Models
 * @property integer _id 编号
 * @property string unique_id 唯一标识
 * @property string type 类型
 * @property string order_id 订单编号
 * @property integer status 订单状态
 * @property string trade_no 交易单号
 * @property double money 金额
 * @property integer pat_at 支付时间
 * @property double pay_rate 支付比例
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class PaymentLogModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='payment_log'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}