<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 视频专题
 * @package App\Models
 * @property integer _id 编号
 * @property string name 名称
 * @property string img 图片
 * @property string bg_img 背景图片
 * @property string position 位置
 * @property string description 描述
 * @property integer sort 排序
 * @property string filter 检索条件
 * @property integer is_disabled 是否显示
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class MovieSpecialModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='movie_special'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}