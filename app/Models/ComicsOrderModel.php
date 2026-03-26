<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 漫画购买
 * @package App\Models
 * @property string _id 编号
 * @property string comics_id 编号
 * @property integer user_id 用户编号
 * @property integer money 费用
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class ComicsOrderModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='comics_order'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}