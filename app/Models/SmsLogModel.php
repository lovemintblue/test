<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 短信日志
 * @package App\Models
 * @property string _id 编号
 * @property string phone 手机号码
 * @property string content 短信内容
 * @property string error_info 错误内容
 * @property string ip ip
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class SmsLogModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='sms_log'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}