<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 系统资源
 * @package App\Models
 * @property string _id 编号
 * @property string name 名称
 * @property string parent_id 上级id
 * @property integer sort 排序
 * @property string key 唯一标识符
 * @property string class_name 样式名
 * @property integer is_menu 是否菜单
 * @property string link 链接
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class AuthorityModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='authority'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}