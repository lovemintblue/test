<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 视频管理
 * @package App\Models
 * @property integer _id 编号
 * @property string mid 媒资库id {媒资库_mid} 例如 xb_1024
 * @property integer user_id 用户ID
 * @property integer categories 分类id
 * @property array tags 标签id
 * @property string name 名称 {剧集追加}-01 {xxx}-02 {xxx}-03
 * @property string name_tw 名称-繁体
 * @property string number 番号(厂牌视频,可用于视频关联,默认生成编号)
 * @property integer sort 排序
 * @property integer is_new 是否最新 1是 0否 无效
 * @property integer is_hot 是否热门 1是 0否
 * @property integer is_more_link 是否多剧集 1是 0否
 * @property string img_x 图片-横
 * @property string img_y 图片-竖
 * @property integer favorite 虚拟收藏数
 * @property integer real_favorite 真实收藏数
 * @property integer click 虚拟点击数
 * @property integer real_click 真实点击数
 * @property integer favorite_rate 收藏率
 * @property integer score 评分 0-100
 * @property integer buy 购买次数
 * @property integer comment 真实评论数
 * @property integer money 金币
 * @property string pay_type 购买类型 money<0免费 money=0vip money>0金币
 * @property string original_link 完整版地址
 * @property string original_duration 完整版时长
 * @property string preview_link 预览版地址
 * @property double original_size 完整版大写
 * @property double preview_size 预览版大小
 * @property string preview_duration 预览版时长
 * @property string width 宽度
 * @property string height 高度
 * @property string position 视频所属板块 动漫 视频 
 * @property string canvas 视频画布 long横 short竖
 * @property integer status 0未上架 1已上架 -1已下架
 * @property string description 描述
 * @property integer show_at 上架时间
 * @property integer async_at 同步时间
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class MovieModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='movie'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}