<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 管理员日志
 * @package App\Models
 * @property string _id 编号
 * @property string admin_id 管理员编号
 * @property string admin_name 管理员名称
 * @property string content 内容
 * @property string ip ip
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class AdminLogModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='admin_log'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}