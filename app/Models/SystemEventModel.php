<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 系统事件
 * @package App\Models
 * @property string _id 编号
 * @property string type 类型
 * @property string message 数据
 * @property string source_data 原始数据
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class SystemEventModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='system_event'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}