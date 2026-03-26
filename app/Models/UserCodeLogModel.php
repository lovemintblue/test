<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 用户兑换码日志
 * @package App\Models
 * @property integer _id 编号
 * @property string name 名称
 * @property string code 兑换码
 * @property string code_key 兑换码key
 * @property integer code_id 兑换码编号
 * @property integer object_id 资源id 用户组或金币
 * @property integer type 兑换码类型 用户组:group 金币:point
 * @property integer user_id 使用人编号
 * @property string username 用户名
 * @property integer add_num 增加数量 天数或金币数
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class UserCodeLogModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='user_code_log'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}