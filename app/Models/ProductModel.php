<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 产品套餐
 * @package App\Models
 * @property string _id 编号
 * @property string name 套餐名称
 * @property string type 类型 point金币
 * @property integer num 数量
 * @property integer gift_num 赠送数量
 * @property integer vip_num 赠送vip天数
 * @property double price 价格
 * @property integer sort 排序
 * @property string description 描述
 * @property string price_tips 价格提示
 * @property integer is_disabled 是否禁用
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class ProductModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='product'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}