<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 会话管理
 * @package App\Models
 * @property string _id 编号
 * @property integer user_id 用户编号
 * @property string chat_id 会话编号
 * @property integer to_user_id 目标用户编号
 * @property integer status 状态 0未读 1已读
 * @property string type 类型 text image
 * @property string content 消息内容
 * @property string ip ip
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class ChatModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='chat'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}