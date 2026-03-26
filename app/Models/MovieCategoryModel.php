<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 视频分类管理
 * @package App\Models
 * @property integer _id 编号
 * @property string name 名称
 * @property string position 位置
 * @property integer is_hot 是否热门 1是 0否
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class MovieCategoryModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='movie_category'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}