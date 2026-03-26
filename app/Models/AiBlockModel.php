<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * AI功能模块
 * @package App\Models
 * @property integer _id 编号
 * @property string name 名称
 * @property string min_version 功能支持的最低版本
 * @property string ico 图标 hot热门
 * @property string position 位置
 * @property integer sort 排序
 * @property integer is_disabled 是否显示
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class AiBlockModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='ai_block'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}