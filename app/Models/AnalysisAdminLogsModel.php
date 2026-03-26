<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 统计后台操作日志
 * @package App\Models
 * @property string _id 编号
 * @property integer admin_id 管理员编号
 * @property string admin_name 管理员名称
 * @property string date_label 日期
 * @property integer num 总操作次数
 * @property array content 内容
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class AnalysisAdminLogsModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='analysis_admin_logs'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}