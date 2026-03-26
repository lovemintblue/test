<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 漫画章节
 * @package App\Models
 * @property string _id 编号
 * @property string name 名称
 * @property string img 封面
 * @property string content 内容
 * @property integer sort 排序
 * @property string comics_id 漫画编号
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class ComicsChapterModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='comics_chapter'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}