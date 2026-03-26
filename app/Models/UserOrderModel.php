<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 用户订单
 * @package App\Models
 * @property integer _id 编号
 * @property string order_sn 订单编号
 * @property integer user_id 用户编号
 * @property string device_type 设备类型
 * @property string username 用户名
 * @property string channel_name 渠道名称
 * @property integer register_at 注册日期
 * @property integer group_id 用户组
 * @property string group_name 用户组名称
 * @property integer level 用户等级
 * @property integer status 支付状态 0未支付 1已支付 -1支付失败
 * @property integer day_num 天数
 * @property integer gift_num 赠送金币
 * @property integer download_num 下载次数
 * @property integer discount_coupon 折扣券张数
 * @property integer group_rate 折扣率
 * @property integer price 价格
 * @property double real_price 真实价格
 * @property integer pay_id 支付编号
 * @property string pay_name 支付名称
 * @property integer pay_at 支付时间
 * @property double pay_rate 费率
 * @property string trade_sn 交易单号
 * @property string register_ip 注册ip
 * @property string created_ip 购买ip
 * @property string register_date 注册时间
 * @property integer is_new_user 是否新用户
 * @property string pay_date 支付日期
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class UserOrderModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='user_order'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}