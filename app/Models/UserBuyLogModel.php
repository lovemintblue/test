<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 用户购买记录(视频,漫画)
 * @package App\Models
 * @property integer _id 编号
 * @property string order_sn 订单编号
 * @property integer user_id 用户id
 * @property integer username 用户名
 * @property integer channel_name 渠道名称
 * @property integer object_id 资源id
 * @property string object_img 资源图片
 * @property string object_type 资源类型 视频:movie 游戏:game
 * @property integer object_money 资源金额
 * @property integer object_money_old 资源金额-原价
 * @property integer register_at 注册日期
 * @property string label 购买日期
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class UserBuyLogModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='user_buy_log'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}