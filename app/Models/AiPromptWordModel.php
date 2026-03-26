<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 提示词
 * @package App\Models
 * @property integer _id 编号
 * @property string name 名称
 * @property string en_name 英文名称
 * @property string group 分组
 * @property integer is_hot 是否热门 1是 0否
 * @property string position 位置
 * @property integer sort 排序
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class AiPromptWordModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='ai_prompt_word'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}