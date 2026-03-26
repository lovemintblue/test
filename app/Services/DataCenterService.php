<?php

namespace App\Services;

use App\Utils\CommonUtil;
use App\Utils\LogUtil;
use App\Utils\CommonUtil as HTTP;

/**
 * 数据中心
 */
class DataCenterService
{
    private static $queueKey = '_data_center_queue';
    /**
     * @var \Redis
     */
    private static $redis;
    /**
     * @var string
     */
    private static $sessionId;

    /**
     * @var
     */
    private static $channelCode;
    /**
     * @var
     */
    private static $userAgent;
    /**
     * @var
     */
    private static $deviceType;
    /**
     * @var
     */
    private static $deviceId;

    /**
     * @var
     */
    private static $clientIp;
    /**
     * @var
     */
    private static $userId;

    /**
     * @var
     */
    private static $appid;

    /**
     * 设置Redis
     * @param \Redis $redis
     * @return void
     */
    public static function setRedis($redis): void
    {
        self::$redis = $redis;
    }

    /**
     * 设置会话ID,全局入口调用
     * @param string $sid 不为空=客户端会话id,为空=服务器会话id
     * @return void
     */
    public static function setSessionId(string $sid = ""): void
    {
        if (empty($sid)) {
            $sid = str_replace('-', '', self::uuidV4());
        }
        self::$sessionId = $sid;
    }

    /**
     * @param $ua
     * @return void
     */
    public static function setUserAgent($ua): void
    {
        self::$userAgent = $ua;
    }

    /**
     * @param mixed $deviceType
     */
    public static function setDeviceType($deviceType): void
    {
        self::$deviceType = strtolower($deviceType);
    }

    /**
     * @param mixed $deviceId
     */
    public static function setDeviceId($deviceId): void
    {
        self::$deviceId = $deviceId;
    }

    /**
     * @param mixed $clientIp
     */
    public static function setClientIp($clientIp): void
    {
        self::$clientIp = $clientIp;
    }

    /**
     * @param mixed $appid
     */
    public static function setAppid($appid): void
    {
        self::$appid = $appid;
    }

    /**
     * @param mixed $userId
     */
    public static function setUserId($userId): void
    {
        self::$userId = $userId;
    }

    /**
     * @param $channelCode
     * @return void
     */
    public static function setChannelCode($channelCode): void
    {
        self::$channelCode = $channelCode;
    }


    /**
     * 注册
     * @param string $accountType
     * @param string $traceId 跟踪id
     * @param int $createTime 注册时间
     * @return void
     */
    public static function doRegister(string $accountType, string $traceId,$createTime)
    {
        //phone(手机), deviceid(设备), email(邮箱),username(账号)
        if (!in_array($accountType, ['phone', 'deviceid', 'email', 'username'])) {
            throw new \Exception("错误的账户类型");
        }
        self::addQueue(
            "user_register",
            [
                'type' => strval($accountType),
                'trace_id' => strval($traceId),
                'create_time'=>intval($createTime),
            ]
        );
    }

    /**
     * 登录
     * @param string $accountType
     * @return void
     */
    public static function doLogin(string $accountType)
    {
        //phone(手机), deviceid(设备), email(邮箱),username(账号)
        self::addQueue(
            "user_login",
            [
                'type' => strval($accountType)
            ]
        );
    }

    /**
     * 在线人数
     * @param $total
     * @return void
     */
    public static function doOnlineTotal($total)
    {
        self::addQueue(
            "realtime_online",
            [
                'platform_quantity' => intval($total)
            ]
        );
    }

    /**
     * VIP订单创建
     * @param $orderId
     * @param $groupId
     * @param $groupName
     * @param $amount
     * @param $createTime
     * @param $refererPage
     * @param $refererPageName
     * @return void
     */
    public static function doVipOrder($orderId, $groupId, $groupName, $amount,$createTime, $refererPage = 'vip', $refererPageName = '个人中心')
    {
        self::addQueue(
            "order_created",
            [
                'order_id' => strval(self::$appid . '_' . $orderId),
                'order_type' => 'vip_subscription',
                'product_id' => strval($groupId),
                'product_name' => strval($groupName),
                'amount'    => intval($amount * 100),
                'currency'  => 'CNY',
                'coin_quantity' => 0,
                'vip_duration_type' => strval($groupId),
                'vip_duration_name' => strval($groupName),
                'source_page_key' => strval($refererPage),
                'source_page_name' => strval($refererPageName),
                'create_time'=>intval($createTime),
            ]
        );
    }


    /**
     * 金币订单创建
     * @param $orderId
     * @param $groupId
     * @param $groupName
     * @param $amount
     * @param $refererPage
     * @param $refererPageName
     * @param $createTime
     * @return void
     */
    public static function doRechargeOrder($orderId, $groupId, $groupName, $amount,$createTime, $refererPage = 'recharge', $refererPageName = '个人中心')
    {
        self::addQueue(
            "order_created",
            [
                'order_id' => strval(self::$appid . '_' . $orderId),
                'order_type' => 'coin_purchase',
                'product_id' => strval($groupId),
                'product_name' => strval($groupName),
                'amount'    => intval($amount * 100),
                'currency'  => 'CNY',
                'coin_quantity' => 0,
                'vip_duration_type' => strval($groupId),
                'vip_duration_name' => strval($groupName),
                'source_page_key' => strval($refererPage),
                'source_page_name' => strval($refererPageName),
                'create_time'=>intval($createTime),
            ]
        );
    }


    /**
     * 会员订单支付
     * @param $orderId
     * @param $groupId
     * @param $dayNum
     * @param $amount
     * @param $payName
     * @param $tradeSn
     * @param $createTime
     * @return void
     */
    public static function doVipOrderPay($orderId, $groupId, $dayNum, $amount, $payName, $tradeSn,$createTime)
    {
        self::addQueue(
            "order_paid",
            [
                'order_id' => strval(self::$appid . '_' . $orderId),
                'order_type' => 'vip_subscription',
                'product_id' => strval($groupId),
                'amount'    => intval($amount * 100),
                'currency'  => 'CNY',
                'coin_quantity' => 0,
                'vip_expiration_time' => time() + $dayNum * 86400,
                'pay_type' => $payName,
                'pay_channel' => '',
                'transaction_id' => strval($tradeSn),
                'create_time'=>intval($createTime),
            ]
        );
    }

    /**
     * 金币订单支付
     * @param $orderId
     * @param $groupId
     * @param $num
     * @param $amount
     * @param $payName
     * @param $tradeSn
     * @return void
     */
    public static function doRechargeOrderPay($orderId, $groupId, $num, $amount, $payName, $tradeSn,$createTime)
    {
        self::addQueue(
            "order_paid",
            [
                'order_id' => strval(self::$appid . '_' . $orderId),
                'order_type' => 'coin_purchase',
                'product_id' => strval($groupId),
                'amount'    => intval($amount * 100),
                'currency'  => 'CNY',
                'coin_quantity' => intval($num),
                'vip_expiration_time' => 0,
                'pay_type' => $payName,
                'pay_channel' => '',
                'transaction_id' => strval($tradeSn),
                'create_time'=>intval($createTime),
            ]
        );
    }

    /**
     * 金币消耗
     * @param $productId
     * @param $productName
     * @param $num
     * @param $oldNum
     * @param $newNum
     * @param $buyType
     * @param $createTime
     * @return void
     */
    public static function doReduceBalance($productId, $productName, $num, $oldNum, $newNum, $buyType,$orderSn,$createTime)
    {
        $map = [
            'video_unlock' => '视频解锁',
            'gift_send' => '礼物赠送',
            'content_purchase' => '内容购买'
        ];
        if (!in_array($buyType, array_keys($map))) {
            throw new \Exception("不支持的购买类型");
        }

        self::addQueue(
            "coin_consume",
            [
                'order_id'=>strval($orderSn),
                'product_id' => strval(self::$appid . '_' . $productId),
                'product_name' => strval($productName),
                'coin_consume_amount' => intval($num<0?$num*-1:$num),//转正数
                'coin_balance_before' => intval($oldNum),
                'coin_balance_after' => intval($newNum),
                'consume_reason_key' => $buyType,
                'consume_reason_name' => $map[$buyType],
                'create_time'=>intval($createTime),
            ]
        );
    }

    /**
     * 关键词搜索
     * @param $keyword
     * @param $count
     * @return void
     */
    public static function doKeywordSearch($keyword, $count)
    {
        self::addQueue(
            "keyword_search",
            [
                'keyword'  => strval($keyword),
                'search_result_count'  => intval($count)
            ]
        );
    }

    /**
     * 视频点赞
     * @param $movieId
     * @param $movieTitle
     * @param $catId
     * @param $catName
     * @param bool $status
     * @return void
     */
    public static function doMovieLove($movieId, $movieTitle, $catId, $catName, bool $status)
    {
        self::addQueue(
            "video_like",
            [
                'video_id'  => strval($movieId),
                'video_title'  => strval($movieTitle),
                'video_type_id' => strval($catId),
                'video_type_name' => strval($catName),
                'flag' => intval($status ? 1 : 2), //1(点赞), 2(取消点赞)
            ]
        );
    }

    /**
     * 视频收藏
     * @param $movieId
     * @param $movieTitle
     * @param $catId
     * @param $catName
     * @param bool $status
     * @return void
     */
    public static function doMovieFavorite($movieId, $movieTitle, $catId, $catName, bool $status)
    {
        self::addQueue(
            "video_collect",
            [
                'video_id'  => strval($movieId),
                'video_title'  => strval($movieTitle),
                'video_type_id' => strval($catId),
                'video_type_name' => strval($catName),
                'flag' => intval($status ? 1 : 2), //1(收藏), 2(取消收藏)
            ]
        );
    }

    /**
     * 买视频
     * @param $movieId
     * @param $movieTitle
     * @param $catId
     * @param $catName
     * @param $orderSn
     * @param $money
     * @return void
     */
    public static function doMovieBuy($movieId, $movieTitle, $catId, $catName, $orderSn, $money)
    {
        self::addQueue(
            "video_purchase",
            [
                'video_id'  => strval($movieId),
                'video_title'  => strval($movieTitle),
                'video_type_id' => strval($catId),
                'video_type_name' => strval($catName),
                'coin_quantity' => intval($money),
                'order_id' => strval($orderSn)
            ]
        );
    }


    /**
     * 视频评论
     * @param $movieId
     * @param $movieTitle
     * @param $catId
     * @param $catName
     * @param string $content
     * @return void
     */
    public static function doMovieComment($movieId, $movieTitle, $catId, $catName, string $content)
    {
        self::addQueue(
            "video_comment",
            [
                'video_id'  => strval($movieId),
                'video_title'  => strval($movieTitle),
                'video_type_id' => strval($catId),
                'video_type_name' => strval($catName),
                'comment_content' => strval($content)
            ]
        );
    }


    /**
     * 视频播放器事件
     * @param $movieId
     * @param $movieTitle
     * @param $catId
     * @param $catName
     * @param array $tagIds
     * @param array $tagNames
     * @param string $videoDuration 视频时长
     * @param string $playDuration 播放时长
     * @param string $behaviorKey 事件类型
     * @return void
     * @throws \Exception
     */
    public static function doMoviePlayEvent($movieId,$movieTitle,$catId,$catName,array $tagIds,array $tagNames,$videoDuration,$playDuration,$behaviorKey)
    {
        if (!in_array($behaviorKey,['video_view','video_play', 'video_pause', 'video_share', 'video_complete', 'video_forward', 'video_rewind'])) {
            throw new \Exception("事件错误");
        }
        self::addQueue(
            "video_event",
            [
                'video_id'  =>strval($movieId),
                'video_title'  =>strval($movieTitle),
                'video_type_id'=>strval($catId),
                'video_type_name'=>strval($catName),
                'video_tag_key'=>strval(join(',',$tagIds)),
                'video_tag_name'=>strval(join(',',$tagNames)),
                'video_duration'=>intval($videoDuration),
                'play_duration'=>intval($playDuration),
                'play_progress'=>intval($playDuration/$videoDuration),
                'video_behavior_key'=>strval($behaviorKey),
                'video_behavior_name'=>''
            ]
        );
    }

    /**
     * 广告展示
     * @param $advPos
     * @param $advIds
     * @param $pageKey
     * @return void
     */
    public static function doAdvShow($advPos,$advIds=[],$pageKey='')
    {
        if(empty($advIds)){
            return;
        }
        self::addQueue(
            "ad_impression",
            [
                'page_key'  =>$pageKey,
                'page_name' =>'',
                'ad_slot_key'=>strval($advPos),
                'ad_slot_name'=>'',
                'ad_id'     => strval(join(',',$advIds)),
                'ad_type'   =>'image'
            ]
        );
    }


    /**
     * 广告点击
     * @param $advId
     * @param $advPos
     * @return void
     */
    public static function doAdvClick($advId, $advPos,$advPosName)
    {
        self::addQueue(
            "ad_click",
            [
                'page_key'  => '',
                'page_name' => '',
                'ad_slot_key' => strval($advPos),
                'ad_slot_name' => strval($advPosName),
                'ad_id'     => strval($advId),
                'ad_type'   => 'image'
            ]
        );
    }

    /**
     * 广告点击
     * @param $advId
     * @param $advPos
     * @return void
     */
    public static function doAdvAppClick($advId, $advPos,$advPosName)
    {
        self::addQueue(
            "ad_click",
            [
                'page_key'  => '',
                'page_name' => '',
                'ad_slot_key' => strval('advapp_' . $advPos),
                'ad_slot_name' => strval($advPosName),
                'ad_id'     => strval($advId),
                'ad_type'   => 'image'
            ]
        );
    }




    /**
     * @param $eventKey
     * @param $eventData
     * @return void
     */
    private static function addQueue($eventKey, $eventData)
    {
        try {
            if (is_null(self::$redis)) {
                throw new \Exception("redis未能加载");
            }
            $reportData = self::getReportData($eventKey, $eventData);
            //废弃单条数据上报的扁平结构
            //            $reportData = array_merge($reportData,$eventData);
            self::$redis->lPush(self::$queueKey, json_encode($reportData, JSON_UNESCAPED_UNICODE));
        } catch (\Exception $e) {
            LogUtil::error(sprintf(__CLASS__ . ' %s in %s line %s', $e->getMessage(), $e->getFile(), $e->getLine()));
        }
    }

    /**
     * 执行任务
     * @param string $domain
     * @return void
     */
    public static function onQueue(string $domain)
    {
        $runTime = 297; // 可执行时间/秒
        $startTime = time();
        $reportData = [];

        // 批量上报策略
        $batchSize = 100;       // 数量触发阈值
        $batchSeconds = 5;      // 时间触发阈值
        $lastFlushTime = time(); // 最近一次上报的时间

        while (true) {
            if (time() - $startTime >= $runTime) {
                break;
            }
            if (is_null(self::$redis)) {
                LogUtil::error(__CLASS__ . " redis未能加载");
                sleep(1);
                continue;
            }
            $data = self::$redis->rPop(self::$queueKey);
            if (empty($data)) {
                // 即使没有新数据，也要检查时间触发
                if (!empty($reportData) && time() - $lastFlushTime >= $batchSeconds) {
                    self::doHttpRequest($domain . '/api/eventTracking/batchReport.json', $reportData);
                    $reportData = [];
                    $lastFlushTime = time();
                }
                sleep(1);
                continue;
            }
            $data = json_decode($data, true);
            if (!is_array($data)) {
                continue;
            }
            //丢弃老版本数据
            if (empty($data['payload'])) {
                continue;
            }
            //丢弃6个小时以上的数据
            if ((time() - $data['_timestamp']) > 60 * 60 * 6) {
                continue;
            }

            $reportData[] = $data;
            // 数量触发 或 时间触发
            if (count($reportData) >= $batchSize || (time() - $lastFlushTime >= $batchSeconds)) {
                self::doHttpRequest($domain . '/api/eventTracking/batchReport.json', $reportData);
                $reportData = [];
                $lastFlushTime = time();
            }
        }

        //  收尾：还剩余数据，必须上报
        if (!empty($reportData)) {
            self::doHttpRequest($domain . '/api/eventTracking/batchReport.json', $reportData);
        }
    }

    public static function getReportData($eventKey,$eventData)
    {
        return [
            'event'   =>strval($eventKey),
            'channel' =>strval(self::$channelCode),
            //事件唯一标识，用于数据去重, 字符与数字,不包含特殊符号,32位以内
            'event_id'=>value(function()use($eventKey,$eventData){
                if($eventKey=='user_register'){
                    $eventId = md5(self::$appid.self::$userId.$eventData['create_time']);
                }elseif(in_array($eventKey,['order_created,','order_paid'])){
                    $eventId = md5(self::$appid.self::$userId.$eventData['order_id'].$eventData['create_time']);
                }elseif($eventKey == 'coin_consume'){
                    $eventId = md5(self::$appid.self::$userId.$eventData['order_id']);
                }else{
                    $eventId = md5(self::$appid.(str_replace('-','',self::uuidV4())));
                }
                return strval($eventId);
            }),
            'app_id'  =>strval(self::$appid),
            "uid"     =>strval(self::$userId),
            "sid"     =>strval(self::$sessionId),
            "client_ts"=>time(),
            "device"  =>value(function (){
                if(in_array(self::$deviceType,['ios','web','h5'])){
                    return "IOS";
                }elseif(self::$deviceType=='android') {
                    return "Android";
                }
                return "PC";
            }),
            "device_id"=>strval(self::$deviceId),
            "user_agent"=>strval(self::$userAgent),
            "device_brand"=>"",
            "device_model"=>"",
            "ip"        =>strval(self::$clientIp),
            //批量上报接口用
            'payload'   =>$eventData,

            '_timestamp'=>time(),
        ];
    }

    /**
     * @return string
     */
    public static function uuidV4()
    {
        try {
            $data = random_bytes(16);
        } catch (\Exception $e) {
            $data = md5(uniqid('', true), true); // 非强随机，但总比挂掉强
        }
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return sprintf(
            '%s-%s-%s-%s-%s',
            bin2hex(substr($data, 0, 4)),
            bin2hex(substr($data, 4, 2)),
            bin2hex(substr($data, 6, 2)),
            bin2hex(substr($data, 8, 2)),
            bin2hex(substr($data, 10, 6))
        );
    }

    /**
     * @param string $requestUrl
     * @param array $requestData
     * @return false|mixed|null
     */
    public static function doHttpRequest(string $requestUrl, array $requestData)
    {
        try {
            $requestData = json_encode($requestData, JSON_UNESCAPED_UNICODE);
            LogUtil::info(sprintf(__CLASS__ . " Request url: %s query:%s", $requestUrl, $requestData));
            $result = Http::httpPost($requestUrl, $requestData, 3, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($requestData)
            ]);
            if (empty($result)) {
                throw new \Exception("请求错误");
            }
            $result = json_decode($result, true);
            if ($result["code"] != 0) {
                throw new \Exception($result['msg']);
            }
            if (empty($result['data'])) {
                return null;
            }
            return $result['data'];
        } catch (\Exception $e) {
            LogUtil::error(sprintf(__CLASS__ . ' %s in %s line %s', $e->getMessage(), $e->getFile(), $e->getLine()));
            return false;
        }
    }
}
