<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 渠道组
 * @package App\Models
 * @property string _id 编号
 * @property string code 渠道标识
 * @property string name 渠道名
 * @property string remark 
 * @property integer is_disabled 是否禁用
 * @property integer last_bind 
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class ChannelModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='channel'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}