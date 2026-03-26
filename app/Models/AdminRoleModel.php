<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 管理角色
 * @package App\Models
 * @property string _id 编号
 * @property string name 角色名
 * @property string rights 权限
 * @property integer is_disabled 是否禁用
 * @property string description 描述
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class AdminRoleModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='admin_role'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}