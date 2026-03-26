<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 社区帖子
 * @package App\Models
 * @property integer _id 编号
 * @property string title 标题
 * @property string color 标题色值
 * @property string categories 分类id
 * @property string content 内容
 * @property array images 图片
 * @property array files 附件
 * @property integer user_id 用户编号
 * @property integer click 虚拟点击次数
 * @property integer real_click 真实点击次数
 * @property integer love 虚拟点赞次数
 * @property integer real_love 真实点赞次数
 * @property integer favorite 虚拟收藏次数
 * @property integer real_favorite 真实收藏次数
 * @property integer comment 评论次数
 * @property integer last_comment 最后评论
 * @property integer money 金币
 * @property string pay_type 购买类型 money<0免费 money=0vip money>0金币
 * @property integer is_top 是否置顶 1是 0否
 * @property integer is_hot 是否热门 1是 0否
 * @property string ip ip
 * @property string province ip获取的省份
 * @property string city ip获取的城市
 * @property integer sort 排序
 * @property integer status 状态 0待审核 1正常 2审核不通过 -1禁用
 * @property string deny_msg 审核未通过原因
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class PostModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='post'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}