<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 队列任务
 * @package App\Models
 * @property integer _id 编号
 * @property string uniqid 唯一编号
 * @property string job 任务
 * @property string exception 异常
 * @property integer failed_at 异常时间
 * @property integer plan_at 计划执行时间
 * @property integer status 状态 0等待 1执行中 2执行成功 -1执行失败
 * @property string level 等级 低low 中medium 高high
 * @property integer success_at 成功时间
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class JobModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='job'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}