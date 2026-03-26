<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * AI订单
 * @package App\Models
 * @property integer _id 编号
 * @property string order_sn 订单编号
 * @property integer user_id 用户编号
 * @property string device_type 设备类型
 * @property string username 用户名
 * @property array extra 订单需求
 * @property array out_data 返回处理结果
 * @property double money 金额
 * @property double real_money 实际支付金额
 * @property string position 位置
 * @property integer status 状态 异常0 成功1 待处理2 处理中3 失败退款-1 已删除-2
 * @property string channel_name 渠道
 * @property integer register_at 注册时间
 * @property string register_date 注册时间
 * @property integer is_new_user 是否新用户
 * @property string register_ip 注册ip
 * @property string created_ip ip
 * @property integer is_disabled 是否显示
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class AiModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='ai'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}