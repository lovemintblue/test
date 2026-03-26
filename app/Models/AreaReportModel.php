<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 地区报表
 * @package App\Models
 * @property string _id 编号
 * @property string area 编号
 * @property integer num 用户数
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class AreaReportModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='area_report'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}