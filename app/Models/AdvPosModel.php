<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 广告位置
 * @package App\Models
 * @property string _id 编号
 * @property string name 广告位名称
 * @property string code 广告位标识
 * @property string is_disabled 是否禁用0 1禁用
 * @property integer width 宽
 * @property integer height 高
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class AdvPosModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='adv_pos'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}