<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 用户任务
 * @package App\Models
 * @property string _id 编号
 * @property string name 名称
 * @property string type 类型
 * @property string description 描述
 * @property integer max_num 单日最大
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class UserTaskModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='user_task'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}