<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 社区分类管理
 * @package App\Models
 * @property integer _id 编号
 * @property string name 名称
 * @property string img 图标
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class PostCategoryModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='post_category'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}