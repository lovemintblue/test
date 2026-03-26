<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 渠道包管理
 * @package App\Models
 * @property integer _id 编号
 * @property string name 名称
 * @property string code 渠道码
 * @property string link 下载链接
 * @property integer is_disabled 状态
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class ChannelAppModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='channel_app'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}