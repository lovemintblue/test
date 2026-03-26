<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 数据跟踪
 * @package App\Models
 * @property string _id 编号
 * @property string type 类型
 * @property string id id
 * @property string name 名称
 * @property integer num 数量
 * @property string date 日期
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class AppTrackModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='app_track'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}