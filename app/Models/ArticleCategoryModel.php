<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 文章分类
 * @package App\Models
 * @property string _id 编号
 * @property string code 唯一标识
 * @property string name 名称
 * @property string img 图片
 * @property string language 语言
 * @property integer sort 排序
 * @property integer parent_id 上级编号
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class ArticleCategoryModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='article_category'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}