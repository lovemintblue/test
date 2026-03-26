<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 用户
 * @package App\Models
 * @property integer _id 编号
 * @property string nickname 昵称
 * @property string username 用户名
 * @property string phone 手机号码
 * @property string country_code 国家代码
 * @property string device_id 设备编号
 * @property string device_type 设备类型
 * @property double device_version 设备版本号
 * @property string device_ext tf名称
 * @property string password 密码
 * @property string slat 盐
 * @property double balance 可用余额
 * @property double credit 可用积分
 * @property string sing 个性签名
 * @property integer is_disabled 是否禁用 1是 0否
 * @property string error_msg 禁用原因
 * @property string img 头像
 * @property string bg_img 背景图
 * @property integer group_id 用户组
 * @property integer group_rate 用户折扣
 * @property string group_name 用户组名称
 * @property integer group_start_time 组开始时间
 * @property integer group_end_time 组结束时间
 * @property integer level 用户等级
 * @property integer sex 性别 1男 2女
 * @property string parent_name 推荐人
 * @property integer parent_id 推荐人编号
 * @property string channel_name 渠道
 * @property integer register_at 注册时间
 * @property integer register_date 注册日
 * @property string register_ip 注册ip
 * @property integer login_num 登录次数
 * @property integer last_at 最后登录时间
 * @property integer last_date 最后登录日
 * @property string last_ip 最近登录ip
 * @property integer share_num 分享人数
 * @property integer fans 粉丝数量
 * @property integer follow 关注数量
 * @property double gift_count 累计收益 (视频销售 帖子打赏 用户打赏)
 * @property double money_count 累计充值
 * @property double send_count 累计送出
 * @property string country 国家
 * @property string province 省
 * @property string city 城市
 * @property string location 地址
 * @property string register_area 注册地址
 * @property integer is_china 是否中国用户
 * @property array withdraw_info 提现信息数组 保存上一次提现的信息,解冻则清空该字段
 * @property array tag 用户标签数组
 * @property integer is_system 是否系统生成
 * @property integer has_buy 是否已经购买了的
 * @property array right 权利 (金币免费:money)
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 */
class UserModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='user'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}