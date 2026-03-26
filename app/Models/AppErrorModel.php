<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 错误日志表
 * @package App\Models
 * @property integer _id 编号
 * @property string device_type 类型
 * @property string device_version 接口版本
 * @property string date 日期
 * @property string content 内容
 * @property string ip IP
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class AppErrorModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='app_error'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}