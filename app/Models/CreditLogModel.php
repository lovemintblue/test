<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 积分日志
 * @package App\Models
 * @property integer _id 编号
 * @property integer user_id 用户编号
 * @property string username 用户名
 * @property integer type 业务类型 1签到 2兑换
 * @property integer item_type 获得物品类型 会员 vip 观影券 movie 裸聊 naked
 * @property double add_num 增加数量
 * @property double num 数量
 * @property double num_log 余额
 * @property string remark remark
 * @property integer object_id 积分兑换物品id
 * @property string ext 扩展信息
 * @property string label 签到日期
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class CreditLogModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='credit_log'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}