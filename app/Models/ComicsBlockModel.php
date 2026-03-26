<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 漫画模块
 * @package App\Models
 * @property integer _id 编号
 * @property string name 名称
 * @property integer style 风格 样式1:1x1 样式2:1xN(横向滚动) 样式3:2x2 样式4:3x3
 * @property integer is_disabled 是否显示
 * @property integer sort 排序
 * @property string ico 图标
 * @property string filter 检索条件
 * @property integer num 展示数量
 * @property string position 位置
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class ComicsBlockModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='comics_block'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}