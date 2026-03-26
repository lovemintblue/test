<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 小说资源
 * @package App\Models
 * @property string _id 编号
 * @property string name 名称
 * @property string child_title 子标题
 * @property string author 作者
 * @property string img 封面
 * @property integer status 状态0待上架  1已上架
 * @property string description 描述
 * @property string cat_id 分类
 * @property integer chapter_count 总章节
 * @property integer issue_date 发行日期
 * @property string source_site 资源站点
 * @property string source_link 资源链接
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class NovelModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='novel'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}