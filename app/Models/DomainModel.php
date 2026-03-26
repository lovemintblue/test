<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 域名管理
 * @package App\Models
 * @property string _id 编号
 * @property string url 域名
 * @property integer status 0正常 -1已墙
 * @property string type 类型
 * @property string remark 备注
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class DomainModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='domain'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}