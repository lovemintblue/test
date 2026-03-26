<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 视频历史记录管理
 * @package App\Models
 * @property integer _id 编号
 * @property integer user_id 用户id
 * @property integer movie_id 资源编号
 * @property string label 日期
 * @property integer status 状态 1正常 0删除
 * @property integer count 状态 1统计 0不统计
 * @property integer num 观看次数
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class MovieHistoryModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='movie_history'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}