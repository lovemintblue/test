<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 每日推荐
 * @package App\Models
 * @property integer _id 编号
 * @property string movie_id 视频ID
 * @property string label 日期
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class MovieDayModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='movie_day'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}