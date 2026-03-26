<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * APP日志
 * @package App\Models
 * @property string _id 编号
 * @property integer user_id 用户编号
 * @property string date 日期
 * @property string month 日期
 * @property string channel_name 渠道
 * @property string device_type 设备类型
 * @property string register_date 注册日期
 * @property integer is_new_user 是否新用户
 * @property string ip ip
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class AppLogModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='app_log'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}