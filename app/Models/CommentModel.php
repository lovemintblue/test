<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 评论表
 * @package App\Models
 * @property integer _id 评论编号
 * @property integer from_uid 发表评论的用户id
 * @property integer object_id 资源编号
 * @property string object_type 类型 漫画:cartoon 视频:movie
 * @property string content 内容
 * @property string ip IP
 * @property integer love 点赞个数
 * @property integer status 状态 正常1 删除0
 * @property integer time 时间 用于弹幕
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class CommentModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='comment'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}