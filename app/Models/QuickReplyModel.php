<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 快捷回复
 * @package App\Models
 * @property integer _id 编号
 * @property string name 名称
 * @property string content 内容
 * @property integer sort 排序
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class QuickReplyModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='quick_reply'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}