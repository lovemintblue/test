<?php


namespace App\Constants;


class CommonValues
{
    const QUEUE_IDS_KYE = 'queue_ids';

    /**
     * 获取是否禁用
     * @param null $value
     * @return string|string[]
     */
    public static function getIsDisabled($value = null)
    {
        $arr = array(
            '1' => '禁用',
            '0' => '正常',
        );
        if ($value === null || $value === "") {
            return $arr;
        }
        return $arr[$value];
    }
    /**
     * 是否up主
     * @param null $value
     * @return string|string[]
     */
    public static function getIsUp($value = null)
    {
        $arr = array(
            '1' => '是',
            '0' => '否',
        );
        if ($value === null || $value === "") {
            return $arr;
        }
        return $arr[$value];
    }

    /**
     * 获取是否推荐
     * @param null $value
     * @return string|string[]
     */
    public static function getIsRecommend($value = null)
    {
        $arr = array(
            '1' => '是',
            '0' => '否',
        );
        if ($value === null || $value === "") {
            return $arr;
        }
        return $arr[$value];
    }
    /**
     * 获取是否ai模块
     * @param null $value
     * @return string|string[]
     */
    public static function getIsAi($value = null)
    {
        $arr = array(
            '1' => '是',
            '0' => '否',
        );
        if ($value === null || $value === "") {
            return $arr;
        }
        return $arr[$value];
    }

    /**
     * 获取是否菜单
     * @param null $value
     * @return string|string[]
     */
    public static function getIsMenus($value = null)
    {
        $arr = array(
            '1' => '是',
            '0' => '否',
        );
        if ($value === null || $value === "") {
            return $arr;
        }
        return $arr[$value];
    }

    /**
     * 获取设备类型
     * @param null $value
     * @return string|string[]
     */
    public static function getDeviceTypes($value = null)
    {
        $arr = array(
            'h5' => 'H5(ios)',
            'android' => 'Android'
        );
        if ($value === null || $value === "") {
            return $arr;
        }
        return $arr[$value];
    }

    /**
     * 会员权力
     * @param $value
     * @return array|mixed
     */
    public static function getUserRights($value='')
    {
        $arr = array(
            'anwang' => '暗网无限看',
            'vip_post' => 'VIP帖子无限看',
            'vip_movie' => 'VIP视频无限看',
            'vip_cartoon' => 'VIP动漫无限看',
            'vip_comics' => 'VIP漫画无限看',
            'vip_line' => '高速线路',
            'comment' => '评论吐槽',
            'nickname' => '修改昵称',
            'game' => '解锁游戏',
            'chat' => '解锁陪聊',
            'yuanjiao' => '解锁援交',
            'no_ad' => '免广告',
        );
        if ($value === null || $value === "") {
            return $arr;
        }
        return $arr[$value];
    }

    /**
     * movie 会员等级
     * @param  $val
     * @return string[]|string
     */
    public static function getUserLevel($val = null)
    {
        $arrs = array(
            '1' => '普通',
            '2' => '普通+暗网',
        );
        if ($val !== null) {
            return isset($arrs[$val]) ? $arrs[$val] : '';
        }
        return $arrs;
    }


    /**
     * movie 会员等级
     * @param  $val
     * @return string[]|string
     */
    public static function getUserLevelName($val = null)
    {
        $arrs = array(
            '0' => '',
            '1' => '白金卡',
            '2' => '黑金卡',
            '3' => '黑金卡',
        );
        if ($val !== null) {
            return isset($arrs[$val]) ? $arrs[$val] : '';
        }
        return $arrs;
    }


    /**
     * movie 状态
     * @param  $val
     * @return string[]|string
     */
    public static function getMovieStatus($val = null)
    {
        $arrs = array(
            '0' => '未上架',
            '1' => '已上架',
            '-1' => '已下架',
            '2' => '待审核',
        );
        if ($val !== null) {
            return isset($arrs[$val]) ? $arrs[$val] : '';
        }
        return $arrs;
    }


    /**
     * movieBlock 风格 1 2 3 4
     * @param  $val
     * @return string[]|string
     */
    public static function getMovieBlockStyles($val = null)
    {
        $arrs = array(
            '1' => '样式1 1大2小 横图',
            '2' => '样式2 2小 横图',
            '3' => '样式3 1大 横图',
            '4' => '样式4  2竖图',
            '5' => '样式5  竖图横滑',
            '6' => '样式6 横图横滑',
            '7' => '样式7 竖图3X3'
        );
        if ($val !== null) {
            return isset($arrs[$val]) ? $arrs[$val] : '';
        }
        return $arrs;
    }


    /**
     * 获取购买类型
     * @param $money
     * @return string
     */
    public static function getPayTypeByMoney($money)
    {
        if($money>0){
            return 'money';
        }elseif($money==0){
            return 'vip';
        }else{
            return 'free';
        }
    }

    /**
     * 获取购买类型
     * @param null $val
     * @return array|mixed|string
     */
    public static function getPayTypes($val=null)
    {
        $arrs = array(
            'money' => '金币',
            'vip' => 'VIP',
            'free' => '免费',
        );
        if ($val !== null) {
            return isset($arrs[$val]) ? $arrs[$val] : '';
        }
        return $arrs;
    }

    /**
     * 获取支付类型
     * @param null $val
     * @return array|mixed|string
     */
    public static function getPaymentTypes($val=null)
    {
        $arrs = array(
            'alipay' => '支付宝',
            'wechat' => '微信',
        );
        if ($val !== null) {
            return isset($arrs[$val]) ? $arrs[$val] : '';
        }
        return $arrs;
    }

    /**
     * 获取视频所属板块
     * @param null $val
     * @return array|mixed|string
     */
    public static function getBlockPositionGroup($val = null)
    {
        $arrs = array(
            'normal' => '视频',
            'dark' => '暗网',
            'cartoon'=>'动漫',
            'comics'=>'漫画',
            'novel'=>'小说',
            'short' => '短剧'
        );
        if ($val !== null) {
            return isset($arrs[$val]) ? $arrs[$val] : '';
        }
        return $arrs;
    }

    /**
     * 获取视频所属板块
     * @param null $val
     * @return array|mixed|string
     */
    public static function getMoviePosition($val = null)
    {
        $arrs = array(
            'normal' => '视频',
            'dark' => '暗网',
            'cartoon'=>'动漫',
            'short' => '短剧'
        );
        if ($val !== null) {
            return isset($arrs[$val]) ? $arrs[$val] : '';
        }
        return $arrs;
    }

    /**
     * 获取视频所属板块
     * @param null $val
     * @return array|mixed|string
     */
    public static function getMovieLinkType($val = null)
    {
        $arrs = array(
            '0' => '单集',
            '1' => '多集',
        );
        if ($val !== null) {
            return isset($arrs[$val]) ? $arrs[$val] : '';
        }
        return $arrs;
    }

    /**
     * 获取视频所属板块
     * @param null $val
     * @return array|mixed|string
     */
    public static function getPostPosition($val = null)
    {
        $arrs = array(
            'normal' => '社区',
            //'dark' => '暗圈'
        );
        if ($val !== null) {
            return isset($arrs[$val]) ? $arrs[$val] : '';
        }
        return $arrs;
    }

    /**
     * 获取视频所属板块
     * @param null $val
     * @return array|mixed|string
     */
    public static function getPostTypes($val = null)
    {
        $arrs = array(
            'files' => '文件',
            'image' => '图片',
            'video' => '视频'
        );
        if ($val !== null) {
            return isset($arrs[$val]) ? $arrs[$val] : '';
        }
        return $arrs;
    }

    /**
     * 获取视频分类所属板块
     * @param null $val
     * @return array|mixed|string
     */
    public static function getMovieCategoryPosition($val = null)
    {
        $arrs = array(
            'all' => '全部',
            'hot' => '热点',
            'video' => '视频',
            'media' => '传媒',
        );
        if ($val !== null) {
            return isset($arrs[$val]) ? $arrs[$val] : '';
        }
        return $arrs;
    }

    /**
     * 获取视频画布
     * @param null $val
     * @return array|mixed|string
     */
    public static function getMovieCanvas($val = null)
    {
        $arrs = array(
            'long' => '横屏',
            'short' => '竖屏',
        );
        if ($val !== null) {
            return isset($arrs[$val]) ? $arrs[$val] : '';
        }
        return $arrs;
    }

    /**
     * 获取用户支付订单状态
     * @param  $val
     * @return array|mixed|string
     */
    public static function getUserOrderStatus($val = null)
    {
        $arrs = array(
            '0' => '未支付',
            '1' => '已支付',
            '-1' => '支付失败',
        );
        if ($val !== null) {
            return isset($arrs[$val]) ? $arrs[$val] : '';
        }
        return $arrs;
    }

    /**
     * 充值状态
     * @param null $val
     * @return array|mixed|string
     */
    public static function getRechargeStatus($val = null)
    {
        $arrs = [
            0 => '处理中',
            1 => '已处理',
            -1 => '失败'
        ];
        if ($val !== null) {
            return isset($arrs[$val]) ? $arrs[$val] : '';
        }
        return $arrs;
    }


    /**
     * 获取余额类型
     * @param null $val
     * @return array|mixed|string
     */
    public static function getAccountLogsType($val = null)
    {
        $arrs = [
            1 => '充值',
            2 => '提现',
            3 => '余额支付',
            4 => '退款到余额',
            5 => '佣金入账',
            6 => '佣金退回',
            7 => '提现回滚',
            8 => '余额扣除',
            9 => '打赏',
            10 =>'视频分成',
            11 => '帖子分成'
        ];
        if ($val !== null) {
            return isset($arrs[$val]) ? $arrs[$val] : '';
        }
        return $arrs;
    }

    /**
     * 会员卡类型
     * @param null $val
     * @return string|string[]
     */
    public static function getPromotionType($val = null)
    {
        $arrs = [
            0 => '正常价格',
            1 => '新人专享',
//            3 => '老用户卡',
        ];
        if ($val !== null) {
            return isset($arrs[$val]) ? $arrs[$val] : '';
        }
        return $arrs;
    }


    /**
     * 提现方式
     * @param null $val
     * @return string|string[]
     */
    public static function getWithdrawPayments($val = null)
    {
        $arrs = [
            1=>'支付宝',
            2=>'银行卡',
            3=>'数字货币'
        ];
        if ($val !== null) {
            return isset($arrs[$val]) ? $arrs[$val] : '';
        }
        return $arrs;
    }

    /**
     * 提现状态
     * @param null $val
     * @return array|mixed|string
     */
    public static function getWithdrawStatus($val = null)
    {
        $arrs = [
            0 => '处理中',
            1 => '已处理',
            -1 => '失败'
        ];
        if ($val !== null) {
            return isset($arrs[$val]) ? $arrs[$val] : '';
        }
        return $arrs;
    }

    /**
     * 获取余额变动操作类型
     * @param null $val
     * @return array|mixed|string
     */
    public static function getAccountRecordType($val = null)
    {
        $arrs = [
            'point' => '金币',
            'vip'   => '会员',
        ];
        if ($val !== null) {
            return isset($arrs[$val]) ? $arrs[$val] : '';
        }
        return $arrs;
    }

    /**
     * 兑换码状态
     * @param null $val
     * @return string|string[]
     */
    public static function getUserCodeStatus($val = null)
    {
        $arrs = [
            '0'   => '未使用',
            '1'   => '已使用',
            '-1'  => '作废',
        ];
        if ($val !== null) {
            return isset($arrs[$val]) ? $arrs[$val] : '';
        }
        return $arrs;
    }


    /**
     * 会话状态
     * @param null $val
     * @return array|mixed|string
     */
    public static function getChatStatus($val = null)
    {
        $arrs = [
            0 => '处理中',
            1 => '已处理'
        ];
        if ($val !== null) {
            return isset($arrs[$val]) ? $arrs[$val] : '';
        }
        return $arrs;
    }

    /**
     * Transfer类型
     * @param  $val
     * @return string[]|string
     */
    public static function getTransferType($val = null)
    {
        $arrs = array(
            'recharge' => '充值',
            'withdraw' => '提现',
        );
        if ($val !== null && $val !== "") {
            return isset($arrs[$val]) ? $arrs[$val] : '';
        }
        return $arrs;
    }

    /**
     * 获取用户性别
     * @param null $val
     * @return array|mixed|string
     */
    public static function getUserSex($val=null)
    {
        $arrs = array(
            0 => '未知',
            1 => '男',
            2 => '女',
        );
        if ($val !== null && $val !== "") {
            return isset($arrs[$val]) ? $arrs[$val] : '';
        }
        return $arrs;
    }


    /**
     * cartoon 状态
     * @param  $val
     * @return string[]|string
     */
    public static function getCartoonStatus($val = null)
    {
        $arrs = array(
            '0' => '未上架',
            '1' => '已上架',
            '-1' => '已下架',
        );
        if ($val !== null) {
            return isset($arrs[$val]) ? $arrs[$val] : '';
        }
        return $arrs;
    }


    /**
     * cartoon 状态
     * @param  $val
     * @return string[]|string
     */
    public static function getCartoonEnd($val = null)
    {
        $arrs = array(
            '0' => '未完结',
            '1' => '已完结',
        );
        if ($val !== null) {
            return isset($arrs[$val]) ? $arrs[$val] : '';
        }
        return $arrs;
    }

    /**
     * 获取系列
     * @param null $val
     * @return array|mixed|string
     */
    public static function getSeries($val=null)
    {
        $arrs = array(
            'all' => '全部',
            'hot' => '热点',
            'video' => '视频',
            'media' => '传媒',
        );
        if ($val !== null && $val !== "") {
            return isset($arrs[$val]) ? $arrs[$val] : '';
        }
        return $arrs;
    }
    /**
     * 获取漫画标签属性
     * @param null $val
     * @return array|mixed|string
     */
    public static function getCartoonTagAttribute($val=null)
    {
        $arrs = array(
            'wz' => '未知',
            'fl' => '分类',
            'js' => '角色',
            'rm' => '热门',
            'wf' => '玩法',
            'sx' => '属性',
            'cj' => '出镜',
            'yz' => '原作',
        );
        if ($val !== null && $val !== "") {
            return isset($arrs[$val]) ? $arrs[$val] : '';
        }
        return $arrs;
    }

    /**
     * 获取视频标签属性
     * @param null $val
     * @return array|mixed|string
     */
    public static function getMovieTagAttribute($val=null)
    {
        $arrs = array(
            ''  => '默认',
            'wz' => '全部',
            'jq' => '主题',
            'sf' => 'UP',
            'fz' => '服装',
            'cj' => '出镜',
            'fq' => '综艺',
            'wf' => '玩法',
            'rw' => '属性',
            'yz' => '动漫',
            'xf' => '短视频',
            'js' => '角色',
        );
        if ($val !== null) {
            return isset($arrs[$val]) ? $arrs[$val] : $val;
        }
        return $arrs;
    }

    /**
     * 获取热门
     * @param null $val
     * @return array|mixed|string
     */
    public static function getHot($val=null)
    {
        $arrs = array(
            0 => '否',
            1 => '是',
        );
        if ($val !== null && $val !== "") {
            return isset($arrs[$val]) ? $arrs[$val] : '';
        }
        return $arrs;
    }
    /**
     * 获取最新
     * @param null $val
     * @return array|mixed|string
     */
    public static function getNew($val=null)
    {
        $arrs = array(
            0 => '否',
            1 => '是',
        );
        if ($val !== null && $val !== "") {
            return isset($arrs[$val]) ? $arrs[$val] : '';
        }
        return $arrs;
    }
    /**
     * 会员组分组
     * @param null $val
     * @return string|string[]
     */
    public static function getUserGroupType($val = null)
    {
        $arrs = [
            'all' => '全部',
            'base' => '基础',
            'other' => '高级',
        ];
        if ($val !== null) {
            return isset($arrs[$val]) ? $arrs[$val] : '';
        }
        return $arrs;
    }

    /**
     * 金币套餐类型
     * @param null $val
     * @return array|mixed|string
     */
    public static function getProductType($val = null)
    {
        $arrs = [
            'point' => '金币',
        ];
        if ($val !== null) {
            return isset($arrs[$val]) ? $arrs[$val] : '';
        }
        return $arrs;
    }

    /**
     * 兑换码类型
     * @param null $val
     * @return array|mixed|string
     */
    public static function getUserCodeType($val = null)
    {
        $arrs = [
            'group' => '用户组',
            'point' => '金币',
        ];
        if ($val !== null) {
            return isset($arrs[$val]) ? $arrs[$val] : '';
        }
        return $arrs;
    }


    /**
     * 获取置顶
     * @param null $val
     * @return array|mixed|string
     */
    public static function getTop($val=null)
    {
        $arrs = array(
            0 => '否',
            1 => '是',
        );
        if ($val !== null && $val !== "") {
            return isset($arrs[$val]) ? $arrs[$val] : '';
        }
        return $arrs;
    }

    /**
     * 帖子状态
     * @param  $val
     * @return string[]|string
     */
    public static function getPostStatus($val = null)
    {
        $arrs = array(
            '0' => '待审核',
            '1' => '正常',
            '2' => '拒绝',
            '3' => '处理中',
            '-1' => '禁用',
        );
        if ($val !== null) {
            return isset($arrs[$val]) ? $arrs[$val] : '';
        }
        return $arrs;
    }

    /**
     * 玩法状态
     * @param  $val
     * @return string[]|string
     */
    public static function getPlayStatus($val = null)
    {
        $arrs = array(
            '0' => '待上线',
            '1' => '正常',
            '2' => '已下线',
            '-1' => '禁用',
        );
        if ($val !== null) {
            return isset($arrs[$val]) ? $arrs[$val] : '';
        }
        return $arrs;
    }

    /**
     * 优惠券折扣等级
     * @param null $val
     * @return array|mixed|string
     */
    public static function getUserCouponMoney($val = null)
    {
        $arrs = [
            '5'    => '5元',
            '10'   => '10元',
            '20'   => '20元',
            '30'   => '30元',
        ];
        if ($val !== null) {
            return isset($arrs[$val]) ? $arrs[$val] : '';
        }
        return $arrs;
    }

    public static function getCouponType($val = null)
    {
        $arrs = [
            'movie'   => '观影',
            'naked'   => '裸聊',
        ];
        if ($val !== null) {
            return isset($arrs[$val]) ? $arrs[$val] : '';
        }
        return $arrs;
    }

    /**
     * 积分明细业务类型
     * @param null $val
     * @return array|mixed|string
     */
    public static function getCreditType($val = null)
    {
        $arrs = [
            '1'   => '签到',
            '2'   => '兑换',
        ];
        if ($val !== null) {
            return isset($arrs[$val]) ? $arrs[$val] : '';
        }
        return $arrs;
    }

    /**
     * 玩法资源类型
     * @param null $value
     * @return string|string[]
     */
    public static function getPlayTypes($value = null)
    {
        $arr = array(
            'movie' => '视频',
            'play' => '约炮',
        );
        if ($value === null || $value === "") {
            return $arr;
        }
        return $arr[$value];
    }

    /**
     * 渠道包管理
     * @param $value
     * @return array|mixed
     */
    public static function getChannelAppType($value='')
    {
        $arr = array(
            'china_line' => '国内线路',
            'oversea_line' => '海外线路',
            'channel_line' => '渠道线路'
        );
        if ($value === null || $value === "") {
            return $arr;
        }
        return $arr[$value];
    }

    /**
     * 域名类型
     * @param $value
     * @return array|mixed
     */
    public static function getDomainType($value='')
    {
        $arr = array(
            'h5_proxy' => 'H5代理',
            'h5_webview' => 'H5webview',
            'h5' => 'H5线路',
            'private' => '保护域名',
            'web' => '落地页域名',
            'channel' => '渠道域名',
            'api' => 'api域名',
            'public_channel_web' => '落地页域名-绑定渠道号',
            'public_channel_h5' => 'H5域名-绑定渠道号',
        );
        if ($value === null || $value === "") {
            return $arr;
        }
        return $arr[$value];
    }

    /**
     * 域名状态
     * @param $value
     * @return array|mixed
     */
    public static function getDomainStatus($value='')
    {
        $arr = array(
            '0' => '正常',
            '-1' => '已墙'
        );
        if ($value === null || $value === "") {
            return $arr;
        }
        return $arr[$value];
    }

    /**
     * 获取app状态
     * @param null $value
     * @return string|string[]
     */
    public static function getDomainStatusHtml($value = null)
    {
        $arr = array(
            '0' => '<span class="green">正常</span>',
            '-1' => '<span class="red">已墙</span>',
        );
        if ($value === null || $value === "") {
            return $arr;
        }
        return $arr[$value];
    }

    /**
     * 获取监控城市
     * @return array
     */
    public static function getMonitorCities()
    {
        $cities = container()->get('redis')->get('monitor_cities');
        if($cities){
            return json_decode($cities,true);
        }
        return array(
            'beijing','chengdu','hangzhou','shanghai','guangzhou','nanjing','chengdu2'
        );
    }

    /**
     * 获取福利任务类型
     * @param $value
     * @return array|string
     */
    public static function getTaskTypes($value='')
    {
        $arr = array(
            'login' => '登陆',
            'comment' => '评论',
            'share'   => '分享',
            'download' => '下载'
        );
        if ($value === null || $value === "") {
            return $arr;
        }
        return $arr[$value];
    }

    /**
     * 获取福利任务类型
     * @param $value
     * @return array
     */
    public static function getComicsStatus($value='')
    {
        $arr = array(
            0 => '未上架',
            1 => '已上架',
           -1 => '已下架',
        );
        if ($value === null || $value === "") {
            return $arr;
        }
        return $arr[$value];
    }

    /**
     * 获取分类
     * @param null $value
     * @return string|string[]
     */
    public static function getComicsCategories($value = null)
    {
        $arr = array(
            '韩漫' => '韩漫',
            '日漫' => '日漫',
            '国漫' => '国漫',
            '本子' => '本子',
            '色图' => '色图',
            '腐漫' =>'腐漫',
            'Cosplay' => 'Cosplay',
            '3D' => '3D',
            'CG' => 'CG',
            '欧美漫画' => '欧美漫画',
            '港台漫画' => '港台漫画',
            '真人漫画' => '真人漫画',
            '同人' => '同人',
            '写真' => '写真',
            'AI' => 'AI',
        );
        if ($value === null || $value === "") {
            return $arr;
        }
        return $arr[$value];
    }
    /**
     * 获取分类
     * @param null $value
     * @return string|string[]
     */
    public static function getComicsCategoriesCode($value = null)
    {
        $arr = array(
            '韩漫' => 'hanman',
            '日漫' => 'riman',
            '国漫' => 'guoman',
            '本子' => 'benzi',
            '色图' => 'setu',
            '腐漫' =>'fuman',
            'Cosplay' => 'cosplay',
            '3D' => '3d',
            'CG' => 'cg',
            '欧美漫画' => 'oumei',
            '港台漫画' => 'gangtai',
            '真人漫画' => 'zhenren',
            '同人' => 'tongren',
            '写真' => 'xiezhen',
            'AI' => 'ai',
        );
        if ($value === null || $value === "") {
            return $arr;
        }
        return $arr[$value];
    }
    /**
     * movieBlock 风格 1 2 3 4
     * @param  $val
     * @return string[]|string
     */
    public static function getComicsBlockStyles($val = null)
    {
        $arrs = array(
            '1' => '样式1 竖图滚动 竖图',
            '2' => '样式2 一行3个多行 竖图',
            '3' => '样式3 一行2个多行 竖图',
        );
        if ($val !== null) {
            return isset($arrs[$val]) ? $arrs[$val] : '';
        }
        return $arrs;
    }

    /**
     * movieBlock 风格 1 2 3 4
     * @param  $val
     * @return string[]|string
     */
    public static function getComicsUpdateStatus($val = null)
    {
        $arrs = array(
            '0' => '更新中',
            '1' => '已完结',
        );
        if ($val !== null) {
            return isset($arrs[$val]) ? $arrs[$val] : '';
        }
        return $arrs;
    }


    /**
     * 获取状态
     * @param $value
     * @return array
     */
    public static function getNovelStatus($value='')
    {
        $arr = array(
            0 => '未上架',
            1 => '已上架',
            -1 => '已下架',
        );
        if ($value === null || $value === "") {
            return $arr;
        }
        return $arr[$value];
    }

    /**
     *  获取更新状态
     * @param  $val
     * @return string[]|string
     */
    public static function getNovelUpdateStatus($val = null)
    {
        $arrs = array(
            '0' => '更新中',
            '1' => '已完结',
        );
        if ($val !== null) {
            return isset($arrs[$val]) ? $arrs[$val] : '';
        }
        return $arrs;
    }
    /**
     * 获取分类
     * @param null $value
     * @return string|string[]
     */
    public static function getNovelCategories($value = null)
    {
        $arr = array(
            '18R' => '成人',
            'normal' => '贤者',
            'audio' => '有声'
        );
        if ($value === null || $value === "") {
            return $arr;
        }
        return $arr[$value];
    }

    /**
     * 获取分类
     * @param null $value
     * @return string|string[]
     */
    public static function getWeek($value = null)
    {
        $arr = array(
            '周日' => 7,
            '周一' => 1,
            '周二' => 2,
            '周三' => 3,
            '周四' => 4,
            '周五' => 5,
            '周六' => 6,
        );
        if ($value === null || $value === "") {
            return $arr;
        }
        return $arr[$value]*1;
    }

    /**
     *   获取视频的图标类型
     * @param  $val
     * @return string[]|string
     */
    public static function getAppTrackTypes($val = null)
    {
        $arrs = array(
            'ad' => '广告点击',
            'app' => 'app点击',
            'enter_buy_vip' => '购买会员',
            'enter_buy_point' => '购买金币',
            'buy_vip' => '发起会员支付',
            'buy_point' => '发起金币支付',
            'share' => '点击分享'
        );
        if ($val !== null) {
            return isset($arrs[$val]) ? $arrs[$val] : '';
        }
        return $arrs;
    }

    /**
     * 用户行为
     * @param $val
     * @return string|string[]
     */
    public static function getUserActs($val = null)
    {
        $arrs = array(
            'enter_app' => '进入app',
            'close_ad' => '关闭弹窗广告',
            'close_appstore' => '关闭弹窗应用',
            'close_notice' => '关闭公告',
            'home_top_tab' => '首页顶部tab',
            'bottom_tab1' => '底部tab1',
            'bottom_tab2' => '底部tab2',
            'bottom_tab3' => '底部tab3',
            'bottom_tab4' => '底部tab4',
            'bottom_tab5' => '底部tab5',
            'bottom_tab6' => '底部tab6',
            'bottom_tab7' => '底部tab7',
            'movie_detail' => '视频详情',
            'comics_detail' => '漫画详情',
            'novel_detail' => '小说详情',
            'post_detail' => '帖子详情',
        );
        if ($val !== null) {
            return isset($arrs[$val]) ? $arrs[$val] : '';
        }
        return $arrs;
    }

    /**
     * 获取ai类型
     * @param null $val
     * @return array|mixed|string
     */
    public static function getAiPosition($val = null)
    {
        $arrs = array(
            'face_image' => '图片换脸',
            'face_video' => '视频换脸',
            'undress'    => 'AI去衣',
            'change'     => 'AI换装',
            'generate'   => 'AI绘画',
            'novel'      => 'AI小说',
//            'emoji'      => 'AI表情',
            'image_to_video' => '图生视频',
            'ai_girlfriend' => 'AI女友',
        );
        if ($val !== null) {
            return isset($arrs[$val]) ? $arrs[$val] : '';
        }
        return $arrs;
    }

    /**
     * 获取换脸类型
     * @param null $val
     * @return array|mixed|string
     */
    public static function getAiFaceType($val = null)
    {
        $arrs = array(
            'face_image' => '图片换脸',
            'face_video' => '视频换脸'
        );
        if ($val !== null) {
            return isset($arrs[$val]) ? $arrs[$val] : '';
        }
        return $arrs;
    }

    /**
     * 获取aiBlock类型
     * @param null $val
     * @return array|mixed|string
     */
    public static function getAiBlockPosition($val = null)
    {
        $arrs = array(
            'face'       => '换脸',
            'undress'    => 'AI去衣',
            'change'     => 'AI换装',
            'generate'   => 'AI绘画',
            'novel'      => 'AI小说',
            'image_to_video' => '图生视频',
            'ai_girlfriend' => 'AI女友',
        );
        if ($val !== null) {
            return isset($arrs[$val]) ? $arrs[$val] : '';
        }
        return $arrs;
    }

    /**
     * 获取ai状态
     * @param  $val
     * @return array|mixed|string
     */
    public static function getAiStatus($val = null)
    {
        $arrs = array(
            '0' => '数据/接口异常',
            '1' => '处理成功',
            '2' => '待处理',
            '3' => '处理中',
            '-1' => '处理失败',
        );
        if ($val !== null) {
            return isset($arrs[$val]) ? $arrs[$val] : '';
        }
        return $arrs;
    }

    /**
     * ai模块图标
     * @param  $val
     * @return string[]|string
     */
    public static function getAiBlockIcos($val = null)
    {
        $arrs = array(
            'hot'  => '热门'
        );
        if ($val !== null) {
            return isset($arrs[$val]) ? $arrs[$val] : '';
        }
        return $arrs;
    }

    /**
     * 获取视频来源
     * @param null $val
     * @return array|mixed|string
     */
    public static function getMovieSource($val = null)
    {
        $arrs = array(
//            'common' => '公共库',
            'laosiji' => '老司机库',
            'media' => '小组库',
        );
        if ($val !== null) {
            return isset($arrs[$val]) ? $arrs[$val] : '';
        }
        return $arrs;
    }

    /**
     * 获取余额类型
     * @param null $value
     * @return string|string[]
     */
    public static function getBalanceTypes($value = null)
    {
        $arr = array(
            'ai_girlfriend' => 'AI女友',
        );
        if ($value === null || $value === "") {
            return $arr;
        }
        return $arr[$value];
    }

    /**
     * 获取余额处理状态
     * @param null $value
     * @return string|string[]
     */
    public static function getBalanceStatus($value = null)
    {
        $arr = array(
            1 => '游戏中',
            2 => '下分处理中',
            3 => '处理失败',
        );
        if ($value === null || $value === "") {
            return $arr;
        }
        return $arr[$value];
    }

    /**
     * 留存数组
     * @param  $val
     * @return string[]|string
     */
    public static function getAppDay($val = null)
    {
        $arrs = array(
            '1'  => '1日留存',
            '3'  => '3日留存',
            '7'  => '7日留存',
            '15'  => '15日留存',
            '30'  => '30日留存',
        );
        if ($val !== null) {
            return isset($arrs[$val]) ? $arrs[$val] : '';
        }
        return $arrs;
    }

}