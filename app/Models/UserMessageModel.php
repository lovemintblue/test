<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 用户消息
 * @package App\Models
 * @property string _id 编号
 * @property integer user_id 用户编号
 * @property string title 标题
 * @property string content 内容
 * @property string type 类型 文本:text 链接:link
 * @property string link 跳转地址
 * @property string date_label 日期
 * @property integer read_status 阅读状态
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class UserMessageModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='user_message'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}