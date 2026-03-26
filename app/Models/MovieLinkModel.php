<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 剧集
 * @package App\Models
 * @property string _id 编号
 * @property string name 名称
 * @property string movie_id 视频编号
 * @property string movie_name 视频名称
 * @property string preview_m3u8_url 线路
 * @property string m3u8_url 线路
 * @property string mp4_path 来源站链接
 * @property integer sort 排序
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class MovieLinkModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='movie_link'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}