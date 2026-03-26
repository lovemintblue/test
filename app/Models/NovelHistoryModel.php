<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 小说历史记录管理
 * @package App\Models
 * @property string _id 编号
 * @property integer user_id 用户id
 * @property string novel_id 资源编号
 * @property string chapter_id 章节编号
 * @property string label 日期
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class NovelHistoryModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='novel_history'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}