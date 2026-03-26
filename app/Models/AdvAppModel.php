<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 应用中心
 * @package App\Models
 * @property integer _id 编号
 * @property string name 名称
 * @property string image 图片
 * @property string download_url 下载地址
 * @property string download 下载次数,直接填写
 * @property string description 描述
 * @property integer sort 排序
 * @property integer is_self 是否自有
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class AdvAppModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='adv_app'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}