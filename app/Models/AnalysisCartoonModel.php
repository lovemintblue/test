<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 统计漫画
 * @package App\Models
 * @property integer _id 编号
 * @property integer cartoon_id 视频id
 * @property string date_label 日期
 * @property string time 时间
 * @property integer click 点击次数
 * @property integer favorite 收藏次数
 * @property integer buy_num 销售数量
 * @property integer buy_total 销售金额
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class AnalysisCartoonModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='analysis_cartoon'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}