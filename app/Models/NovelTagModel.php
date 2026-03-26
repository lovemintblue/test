<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 小说标签管理
 * @package App\Models
 * @property integer _id 编号
 * @property string name 名称
 * @property string attribute 所属系列 全部 动漫 视频
 * @property integer is_hot 是否热门 1是 0否
 * @property integer count 资源数量
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class NovelTagModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='novel_tag'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}