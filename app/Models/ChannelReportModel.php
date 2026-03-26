<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 渠道报表
 * @package App\Models
 * @property string _id 编号
 * @property string code 渠道标识
 * @property string date 日期
 * @property integer user_reg 
 * @property integer android_reg 
 * @property integer web_reg 
 * @property integer ios_reg 
 * @property integer app_day 
 * @property integer yesterday_app_day 
 * @property integer today_app_day 今日日活
 * @property integer order_num 
 * @property integer point_num 
 * @property integer today_order_num 
 * @property integer yesterday_order_num 
 * @property integer game_order_num 
 * @property integer today_game_order_num 
 * @property integer yesterday_game_order_num 
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class ChannelReportModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='channel_report'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}