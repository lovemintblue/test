<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 日志-应用广告点击
 * @package App\Models
 * @property int _id 编号
 * @property int adv_id 广告id
 * @property string name 广告名称
 * @property string label 日期
 * @property string channel_name 渠道码
 * @property int click 点击次数
 * @property int created_at 创建时间
 * @property int updated_at 更新时间
 */
class ReportAdvAppLogModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='report_adv_app_log'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}