<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 账号日志日志
 * @package App\Models
 * @property integer _id 编号
 * @property string order_sn 编号
 * @property integer user_id 用户编号
 * @property string username 用户名
 * @property string record_type 类型 point金币
 * @property integer type 业务类型1充值 2 提现 3余额支付 4退款到余额 5佣金入账 6佣金退回 7提现回滚 8余额扣除 9打赏 
 * @property double num 数量
 * @property double num_log 余额
 * @property string remark remark
 * @property string object_id 对应的事件编号
 * @property string ext 扩展信息
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class OldTokenModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='old_token'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}