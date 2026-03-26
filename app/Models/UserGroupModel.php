<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 用户组
 * @package App\Models
 * @property integer _id 编号
 * @property string name 名称
 * @property string description 描述
 * @property integer is_disabled 状态
 * @property integer sort 排序
 * @property integer level 等级
 * @property string group 分组 全部:all 基础:base 高级:other
 * @property integer promotion_type 促销类型
 * @property integer rate 购片折扣
 * @property integer coupon_num 折扣券张数
 * @property double price 价格
 * @property double old_price 原价
 * @property integer day_num 可用天数
 * @property integer gift_num 赠送金币
 * @property integer download_num 下载次数
 * @property string day_tips 天数提示
 * @property string price_tips 价格提示
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class UserGroupModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='user_group'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}