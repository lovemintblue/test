<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 用户任务日志
 * @package App\Models
 * @property string _id 编号
 * @property integer task_id 任务编号
 * @property string task_type 任务编号
 * @property integer user_id 用户编号
 * @property string date_label 日期编号
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class UserTaskLogModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='user_task_log'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}