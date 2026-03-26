<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 玩法管理
 * @package App\Models
 * @property integer _id 编号
 * @property string title 标题
 * @property string number 约炮、裸聊任务编号
 * @property string tag 约炮、裸聊标签
 * @property string city 约炮、裸聊地址
 * @property string type 类型 game游戏 luoliao裸聊 yuepao约炮
 * @property string description 描述
 * @property string img_x 封面
 * @property string video 视频
 * @property array images 图集
 * @property array params 游戏参数
 * @property string download_link 游戏下载链接
 * @property integer comment 评论次数
 * @property integer last_comment 最后评论
 * @property integer money 金币
 * @property string pay_type 购买类型 money<0免费 money=0vip money>0金币
 * @property integer buy 购买次数
 * @property integer sort 排序
 * @property string score 推荐指数
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class PlayModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='play'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}