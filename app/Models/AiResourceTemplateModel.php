<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 资源模版
 * @package App\Models
 * @property integer _id 编号
 * @property string name 名称
 * @property array categories 分类id
 * @property integer money 金币
 * @property string aid ai系统返回数据的id
 * @property string img 图片地址
 * @property string video 视频地址
 * @property integer is_hot 是否热门 1是 0否
 * @property string position 位置
 * @property integer width 宽度
 * @property integer height 高度
 * @property integer buy 购买数量
 * @property integer sort 排序
 * @property integer is_porn 成人 1是 0否
 * @property integer is_disabled 是否禁用 1是 0否
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class AiResourceTemplateModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='ai_resource_template'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}