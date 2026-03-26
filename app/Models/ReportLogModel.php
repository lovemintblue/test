<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * app统计数据
 * @package App\Models
 * @property string _id 编号
 * @property string type 类型
 * @property integer value 值
 * @property string date 日期
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class ReportLogModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='report_log'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}