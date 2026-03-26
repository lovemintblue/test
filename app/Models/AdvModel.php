<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 广告
 * @package App\Models
 * @property string _id 编号
 * @property string name 广告名称
 * @property string position_code 广告位标识
 * @property string type 广告类型 video视频 image图片
 * @property string c 权利 (全部:all 普通用户:normal 会员:vip)
 * @property string channel_code 渠道code
 * @property string content 广告内容
 * @property integer start_time 开始时间
 * @property integer end_time 结束
 * @property integer sort 排序
 * @property integer click 点击次数
 * @property string link 广告链接
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class AdvModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='adv'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}