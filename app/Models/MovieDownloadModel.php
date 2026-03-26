<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 视频下载
 * @package App\Models
 * @property integer _id 编号
 * @property integer status 状态 1正常 -1删除
 * @property integer movie_id 视频编号
 * @property integer user_id 用户编号
 * @property string link 视频链接
 * @property string pay_type 视频购买类型
 * @property string label 日期时间
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class MovieDownloadModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='movie_download'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}