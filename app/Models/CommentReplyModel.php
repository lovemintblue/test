<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 评论回复表
 * @package App\Models
 * @property integer _id 回复编号
 * @property string comment_id 评论id
 * @property string reply_id 回复目标id
 * @property string reply_type 回复类型 针对评论:comment 针对恢复:reply(reply_id＝comment_id)
 * @property string object_type 资源类型
 * @property string content 内容
 * @property integer from_uid 回复用户id
 * @property integer to_uid 目标用户id
 * @property integer status 状态 正常1 删除0
 * @property integer time 时间 用于弹幕
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class CommentReplyModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='comment_reply'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}