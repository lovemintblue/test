<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * app统计数据-小时
 * @package App\Models
 * @property string _id 编号
 * @property integer dau 总日活
 * @property integer dau_android 安卓日活
 * @property integer dau_ios IOS日活
 * @property integer reg 注册
 * @property integer reg_android 安卓注册
 * @property integer reg_ios IOS注册
 * @property integer order 订单
 * @property integer order_success 成功订单数
 * @property integer order_money 订单金额
 * @property integer tav 客单价
 * @property integer apr 日付费率
 * @property integer payr 支付成功率
 * @property integer arpu 用户平均收入
 * @property string month 年月
 * @property string date 日期
 * @property string date_limit 时间范围
 * @property string pid 上级id
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class ReportHourLogModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='report_hour_log'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}