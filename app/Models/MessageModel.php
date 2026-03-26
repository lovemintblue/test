<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 消息管理
 * @package App\Models
 * @property string _id 编号
 * @property integer user_id 用户编号
 * @property string chat_id 会话编号
 * @property integer to_user_id 对方编号
 * @property string type 类型message
 * @property string content 消息内容
 * @property string ip 发送IP
 * @property string ext 扩展信息
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class MessageModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='message'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}