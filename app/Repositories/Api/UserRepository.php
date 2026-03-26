<?php


namespace App\Repositories\Api;


use App\Constants\CommonValues;
use App\Constants\StatusCode;
use App\Core\Repositories\BaseRepository;
use App\Exception\BusinessException;
use App\Services\AccountService;
use App\Services\AdvService;
use App\Services\ApiService;
use App\Services\ArticleService;
use App\Services\ChatService;
use App\Services\CommonService;
use App\Services\CustomerSystemService;
use App\Services\DataCenterService;
use App\Services\MessageService;
use App\Services\MovieService;
use App\Services\PaymentService;
use App\Services\PostService;
use App\Services\ProductService;
use App\Services\RechargeService;
use App\Services\UserCodeService;
use App\Services\UserFollowService;
use App\Services\UserGroupService;
use App\Services\UserOrderService;
use App\Services\UserService;
use App\Services\UserCouponService;
use App\Services\CreditLogService;
use App\Services\UserTaskService;
use App\Services\UserUpService;
use App\Utils\AesUtil;
use App\Utils\CommonUtil;

/**
 * Class UserRepository
 * @property UserService $userService
 * @property UserTaskService $userTaskService
 * @property UserCouponService $userCouponService
 * @property CreditLogService $creditLogService
 * @property UserCodeService $userCodeService
 * @property UserOrderService $userOrderService
 * @property UserGroupService $userGroupService
 * @property ProductService $productService
 * @property AccountService $accountService
 * @property CommonService $commonService
 * @property RechargeService $rechargeService
 * @property PaymentService $paymentService
 * @property ChatService $chatService
 * @property ArticleService $articleService
 * @property MessageService $messageService
 * @property UserFollowService $userFollowService
 * @property PostService $postService
 * @property MovieService $movieService
 * @property ApiService $apiService
 * @property UserUpService  $userUpService
 * @property  AdvService $advService
 * @property  CustomerSystemService $customerSystemService
 * @package App\Repositories\Api
 */
class UserRepository extends BaseRepository
{
    /**
     * 用户登录
     * @param $deviceId
     * @return array
     * @throws BusinessException
     */
    public function login($deviceId)
    {
        return $this->userService->loginByUserDevice($deviceId);
    }


    /**
     * 获取用户主页
     * @param $homeId
     * @param $userId
     * @return array
     * @throws BusinessException
     */
    public function getHome($homeId, $userId)
    {
        if (empty($userId)) $homeId = $userId;
        $homeUserInfo = $this->userService->getInfoFromCache($homeId);
        $this->userService->checkUser($homeUserInfo);
        $result = array(
            'user_id' => strval($homeUserInfo['id']),
            'img' => $this->commonService->getCdnUrl($homeUserInfo['img']),
            'username' => $homeUserInfo['username'],
            'nickname' => $homeUserInfo['nickname'],
            'sign' => strval($homeUserInfo['sign']),
            'is_up' => strval($homeUserInfo['is_up']),
            'level' => strval($homeUserInfo['level']),
            'level_name' => CommonValues::getUserLevelName($homeUserInfo['level'] * 1),
            'sex' => strval($homeUserInfo['sex']),
            'fans' => strval($homeUserInfo['fans']),
            'follow' => strval($homeUserInfo['follow']),
            'post_count' => strval($this->postService->count(array('user_id' => $homeId, 'status' => 1))),
            'movie_count' => strval($this->movieService->count(array('user_id' => $homeId, 'status' => 1))),
            'is_follow' => $this->userFollowService->has($userId, $homeId) ? 'y' : 'n',
            'is_vip' => $this->userService->isVip($homeUserInfo) ? 'y' : 'n',
        );
        return $result;
    }

    /**
     * @param $userId
     * @return array
     */
    public function getInfo($userId)
    {
        $userInfo = $this->userService->getInfoFromCache($userId);

        $result = [
            'user_id' => strval($userInfo['id']),
            'img' => $this->commonService->getCdnUrl($userInfo['img']),
            'username' => $userInfo['username'],
            'nickname' => $userInfo['nickname'],
            'sign' => strval($userInfo['sign']),
            'phone' => value(function () use ($userInfo) {
                if (strstr($userInfo['phone'], 'system_')) {
                    return '';
                }
                if (strstr($userInfo['phone'], 'device_')) {
                    return '';
                }
                if (strstr($userInfo['phone'], 'web_')) {
                    return '';
                }
                return strval($userInfo['phone']);
            }),
            'account_name' => strval($userInfo['phone']),
            'account_slat' => $userInfo['username'] . '==>' . $this->userService->getAccountSlat($userInfo['username']),
            'sex' => strval($userInfo['sex']),
            'is_vip' => strval($userInfo['is_vip']),
            'is_up' => strval($userInfo['is_up']),
            'level' => value(function () use ($userInfo) {
                if ($userInfo['is_vip'] == 'y') {
                    return strval($userInfo['level']);
                }
                return '0';
            }),
            'group_style' => value(function () use ($userInfo) {
                return strval($userInfo['level']);
            }),
            'parent_name' => strval($userInfo['parent_name']),
            'group_name' => value(function () use ($userInfo) {
                if ($userInfo['is_vip'] != 'y') {
                    return '游客';
                }
                if ($userInfo['group_end_time'] > strtotime("2028-10-01 00:00:00")) {
                    return '终身卡';
                }
                return strval($userInfo['group_name']);
            }),
            'vip_buy_tips' => '',
            'group_end_time' => dateFormat($userInfo['group_end_time'], 'Y-m-d'),
            'balance' => strval($userInfo['balance']),
            'income' => strval($userInfo['income'] * 1),
            'integral' => strval($userInfo['integral'] * 1),
            'is_new' => $this->userService->isNewUser($userInfo) ? 'y' : 'n',
            'new_user_end_time' => strval($this->userService->getNewUserTime($userInfo)),
            'banner' => $this->advService->getAll('user_home_banner',$userInfo['is_vip'],10),
            'ico_ads' => $this->advService->getAll('common_ico',$userInfo['is_vip'],15),
        ];
        if ($result['is_new'] == 'y' && $userInfo['register_at']) {
            $endTime = ($userInfo['register_at'] + 24 * 3600) - time();
            $result['vip_buy_tips'] = sprintf('新人VIP限时折扣||%s', $endTime > 0 ? $endTime : 0);
        }
        return $result;
    }

    /**
     * 随机获取分享链接
     * @param $username
     * @return string
     */
    public function getShareLink($username)
    {
        return $this->userService->getShareLink($username);
    }

    /**
     * 获取分享信息
     * @param $userId
     * @return array
     */
    public function getShareInfo($userId)
    {
        return $this->userService->getShareInfo($userId);
    }

    /**
     * 分享列表
     * @param $userId
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function getShareList($userId, $page = 1, $pageSize = 10)
    {
        $query = ['parent_id' => intval($userId)];
        $skip = ($page - 1) * $pageSize;
        $rows = $this->userService->getList($query, ['_id', 'nickname', 'img', 'register_at'], ['_id' => -1], $skip, $pageSize);
        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'id' => strval($row['_id']),
                'nickname' => strval($row['nickname']),
                'img' => strval($this->commonService->getCdnUrl($row['img'])),
                'register_at' => dateFormat($row['register_at'], 'm-d H:i'),
            ];
        }
        return $result;
    }

    /**
     * 分享图片
     * @param $username
     * @return string
     */
    public function getShareImg($username)
    {
        $username = AesUtil::encrypt($username, UserService::AES_KEY);
        return $this->commonService->getConfig('site_url') . '/image?type=share&username=' . $username . '&ext=.png&v=1.0';
    }

    /**
     * 生成账号凭证
     * @param $username
     * @return  mixed
     */
    public function getAccountImage($username)
    {
        $username = AesUtil::encrypt($username, UserService::AES_KEY);
        return $this->commonService->getConfig('site_url') . '/image?type=account&username=' . $username . '&ext=.png&v=1.0';
    }


    /**
     * 头像列表
     * @return array
     */
    public function getHeadImages()
    {
        $configs = getConfigs();
        $result = array();
        for ($i = 50; $i > 0; $i--) {
            $value = sprintf('%s/common_file/headico/ico121/%s.jpg', $configs['media_dir'], $i);
            $result[] = array(
                'value' => $value,
                'img' => $this->commonService->getCdnUrl($value)
            );
        }
        return $result;
    }

    /**
     * 绑定渠道
     * @param $userId
     * @param $channelName
     * @return bool
     * @throws BusinessException
     */
    public function bindChannel($userId, $channelName)
    {
        return $this->userService->doBindChannel($userId, $channelName);
    }

    /**
     * 绑定上级
     * @param $userId
     * @param $shareCode
     * @return bool
     * @throws BusinessException
     */
    public function bindParent($userId, $shareCode)
    {
        return $this->userService->doBindParent($userId, $shareCode);
    }

    /**
     * 绑定手机号
     * @param $userId
     * @param $phone
     * @return bool
     * @throws BusinessException
     */
    public function bindPhone($userId, $phone)
    {
        return $this->userService->doBindPhone($userId, $phone);
    }

    /**
     * 使用兑换码
     * @param $userId
     * @param $code
     * @return bool
     * @throws BusinessException
     */
    public function doCode($userId, $code)
    {
        return $this->userCodeService->doCode($userId, $code);
    }

    /**
     * 兑换记录
     * @param $userId
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function codeLog($userId, $page = 1, $pageSize = 15)
    {
        $skip = ($page - 1) * $pageSize;
        $rows = $this->userCodeService->getLogList(['user_id' => $userId], [], ['_id' => -1], $skip, $pageSize);
        foreach ($rows as &$row) {
            $row = [
                'id' => strval($row['_id']),
                'code' => strval($row['code']),
                'tips' => $row['type'] == 'group' ? strval("会员:{$row['add_num']}天") : "金币:{$row['add_num']}个",
                'label' => dateFormat($row['created_at'])
            ];
            unset($row);
        }
        return $rows;
    }

    /**
     * 个人信息修改
     * @param $userId
     * @param $field
     * @param $value
     * @return bool
     * @throws BusinessException
     */
    public function doSimpleUpdate($userId, $field, $value)
    {
        return $this->userService->doSimpleUpdate($userId, $field, $value);
    }

    /**
     * 账号凭证找回
     * @param $code
     * @param $userId
     * @return array
     * @throws BusinessException
     */
    public function doBackQR($userId, $code)
    {
        return $this->userService->doBackQR($userId, $code);
    }

    /**
     * 手机号找回
     * @param $userId
     * @param $phone
     * @return array
     * @throws BusinessException
     */
    public function doBackPhone($userId, $phone)
    {
        return $this->userService->doBackPhone($userId, $phone);
    }


    public function doBackAccount($userId, $accountName, $accountPassword, $type = 'login')
    {
        return $this->userService->doBackAccount($userId, $accountName, $accountPassword, $type);
    }

    /**
     * vip页面
     * @param $userId
     * @return array
     */
    public function vipInfo($userId)
    {
        $userInfo = $this->userService->getInfoFromCache($userId);
        $groups = $this->userGroupService->getEnableAll();
        $payments = $this->paymentService->getPaymentList('vip', 'api');
        $groupList = [];
        $isNewUser = $this->userService->isNewUser($userInfo);
        $rightsArr = CommonValues::getUserRights();
        foreach ($groups as $index => $group) {
            //过滤新人专享
            if ($group['promotion_type'] == 1 && $isNewUser == 0) {
                unset($groups[$index]);
                continue;
            }
            $endTime = '0';
            if ($group['promotion_type'] == 1 && $isNewUser) {
                $endTime = $userInfo['register_at'] + 3600 * 24;
                $endTime = strval($endTime - time());
            }
            $rights = [];
            foreach ($group['rights'] as $right) {
                $rights[] = [
                    'code' => $right,
                    'name' => strval($rightsArr[$right])
                ];
            }
            $item = [
                'id' => $group['id'],
                'name' => $group['name'],
                'img' => $this->commonService->getCdnUrl($group['img']),
                'day_tips' => $group['day_tips'],
                'description' => $group['description'],
                'price' => $group['price'],
                'level' => $group['level'],
                'old_price' => $group['old_price'],
                'end_time' => $endTime,
                'rights' => $rights,
                'payments' => value(function () use ($group, $payments) {
                    $result = [];
                    foreach ($payments as $payment) {
                        $canUse = false;
                        if ($payment['can_use_amount'] == 'unlimit') {
                            $canUse = true;
                        } else {
                            $amounts = explode(',', $payment['can_use_amount']);
                            if (in_array($group['price'], $amounts)) {
                                $canUse = true;
                            }
                        }
                        if ($canUse) {
                            $result[] = [
                                'payment_id' => $payment['payment_id'],
                                'payment_name' => $payment['payment_name'],
                                'payment_ico' => $this->commonService->getPaymentIco($payment['type']),
                                'type' => $payment['type']
                            ];
                        }
                    }
                    return $result;
                })
            ];
            $groupList[] = $item;
        }
        //按照level分组
        $groupLevelList = array(
            '1' => ['level' => '1', 'name' => '基础会员卡', 'items' => [], 'is_selected' => 'n'],
            '2' => ['level' => '2', 'name' => '高级会员卡', 'items' => [], 'is_selected' => 'y']
        );
        foreach ($groupList as $group) {
            $groupLevelList[$group['level']]['items'][] = $group;
        }
        $result = [
            'user_id' => strval($userId),
            'username' => $userInfo['username'],
            'vip_tips' => value(function () use ($userInfo) {
                if ($userInfo['group_end_time'] > strtotime("2031-01-01")) {
                    return 'VIP到期时间:永久';
                }
                if ($userInfo['group_end_time']) {
                    return 'VIP到期时间:' . date("Y-m-d H:i", $userInfo['group_end_time']);
                }
                return "VIP到期时间:游客";
            }),
            'group' => array_values($groupLevelList),
            'tips' => '支付成功后,一般在2-10分钟内到账,如果超时未到账,请联系在线客服为您解决!',
        ];
        return $result;
    }

    /**
     * 购买会员
     * @param $userId
     * @param $groupId
     * @param $paymentId
     * @return array
     * @throws BusinessException
     */
    public function doVip($userId, $groupId, $paymentId)
    {
        $userModel = $this->userService->findByID($userId);
        $this->userService->checkUser($userModel);
        $group = $this->userGroupService->getInfo($groupId);
        if (empty($group) || $group['is_disabled'] == 'y') {
            throw new BusinessException(StatusCode::DATA_ERROR, '当前套餐已下架!');
        }

        if (!$this->commonService->checkActionLimit("user_order_{$userId}", 10, 2)) {
            throw new BusinessException(StatusCode::DATA_ERROR, '慢点慢点,我受不了!');
        }

        //追加用户24小时内8单未支付就锁定
        if($this->userOrderService->count(['user_id' => intval($userId),'status' => 0,'created_at' => ['$gt'=>time()-24*3600]]) > 8){
            throw new BusinessException(StatusCode::DATA_ERROR, '慢点慢点,请客官不要着急!');
        }

        $registerDate = date('Y-m-d', $userModel['register_at'] * 1);
        $configs = getConfigs();
        $orderData = array(
            'order_sn' => $paymentId == -1 ? CommonUtil::createOrderNo('BL') : CommonUtil::createOrderNo($configs['order_prefix'].'O'),
            'user_id' => $userId,
            'device_type' => $userModel['device_type'],
            'username' => $userModel['username'],
            'channel_name' => strval($userModel['channel_name']),
            'register_at' => $userModel['register_at'] * 1,
            'group_id' => intval($group['id']),
            'group_name' => $group['name'],
            'level' => $group['level'] * 1,
            'status' => 0,
            'day_num' => $group['day_num'] * 1,
            'gift_num' => $group['gift_num'] * 1,
            'download_num' => $group['download_num'] * 1,
            'discount_coupon' => intval($group['coupon_num'] ?: 0),//代金券张数
            'group_rate' => intval($group['rate'] ?: 100),//折扣
            'price' => $group['price'] * 1,
            'real_price' => 0,
            'pay_id' => $paymentId,
            'pay_name' => value(function () use ($paymentId) {
                $paymentItems = $this->paymentService->getPaymentList('vip', 'api');
                return isset($paymentItems[$paymentId]['type']) ? $paymentItems[$paymentId]['type'] : '';
            }),
            'pay_at' => 0,
            'pay_rate' => 0,
            'trade_sn' => '',
            'register_ip' => $userModel['register_ip'],
            'created_ip' => getClientIp(),

            'register_date' => $registerDate,
            'is_new_user' => $this->userService->isNewUser($userModel) ? 1 : 0,
            'pay_date' => '',
            'created_at'=>time()
        );
        if ($paymentId == -1 && $userModel['balance'] < $orderData['price']) {
            throw new BusinessException(StatusCode::DATA_ERROR, '您的金币不足以支付订单!');
        }
        $orderId = $this->userOrderService->save($orderData);
        if (empty($orderId)) {
            throw new BusinessException(StatusCode::DATA_ERROR, '支付繁忙,请稍后再试!');
        }
        DataCenterService::doVipOrder($orderId,$orderData['group_id'],$orderData['group_name'],$orderData['price'],$orderData['created_at']);
        //余额支付
        if ($paymentId == -1) {
            $result = $this->accountService->reduceBalance($userModel, $orderData['order_sn'], $orderData['price'], 3, "购买会员:{$orderData['group_name']}");
            if ($result) {
                $this->userService->doChangeGroup($userModel, $orderData['day_num'], $orderData['group_id']);
                $this->userOrderService->save(['status' => 1, '_id' => $orderId, 'pay_at' => time()]);
            }
            return ['url' => '', 'type' => '', 'need_jump' => 'n', 'msg' => $result ? '金币支付成功' : '金币支付失败'];
        } else {
            $result = $this->paymentService->createPayLink('vip', $paymentId, $orderId, $orderData['price'], $orderData['order_sn'], $this->apiService->getDeviceType(), $userModel);
            /*$result['payment_id']=1000;
            $result['payment_url']='http://baidu.com';
            $result['payment_type']='alipay';*/
            $this->userOrderService->save(['pay_id' => $result['payment_id'] * 1, '_id' => $orderId]);
            return [
                'url' => $result['payment_url'],
                'type' => $result['payment_type'],
                'need_jump' => $result['payment_url'] ? 'y' : 'n',
                'msg' => $result['payment_url'] ? '' : '发起支付失败'
            ];
        }
    }

    public function getVipLogs($userId, $page = 1, $pageSize = 15)
    {
        $skip = ($page - 1) * $pageSize;
        $items = $this->userOrderService->getList(array('user_id' => intval($userId)), array(), array('_id' => -1), $skip, $pageSize);
        $result = array();
        foreach ($items as $item) {
            $result[] = array(
                'id' => strval($item['_id']),
                'order_sn' => strval($item['order_sn']),
                'group_id' => strval($item['group_id']),
                'group_name' => strval($item['group_name']),
                'day_num' => strval($item['day_num'] * 1),
                'pay_name' => CommonValues::getPaymentTypes($item['pay_name']),
                'price' => strval($item['price']),
                'created_at' => dateFormat($item['created_at']),
                'status' => strval($item['status'] * 1)
            );
        }
        return $result;
    }

    /**
     * 金币充值页面
     * @param $userId
     * @return array
     */
    public function rechargeInfo($userId)
    {
        $userInfo = $this->userService->findByID($userId);
        $groups = $this->productService->getEnableAll('point');
        $payments = $this->paymentService->getPaymentList('point', 'api');
        $products = [];
        foreach ($groups as $index => $group) {
            $item = [
                'id' => $group['id'],
                'num' => $group['num'],
                'gift_num' => strval(intval($group['gift_num'])),
                'price' => $group['price'] . '元',
                'payments' => value(function () use ($group, $payments) {
                    $result = [];
                    foreach ($payments as $payment) {
                        $canUse = false;
                        if ($payment['can_use_amount'] == 'unlimit') {
                            $canUse = true;
                        } else {
                            $amounts = explode(',', $payment['can_use_amount']);
                            if (in_array($group['price'], $amounts)) {
                                $canUse = true;
                            }
                        }
                        if ($canUse) {
                            $result[] = [
                                'payment_id' => $payment['payment_id'],
                                'payment_name' => $payment['payment_name'],
                                'payment_ico' => $this->commonService->getPaymentIco($payment['type']),
                                'type' => $payment['type']
                            ];
                        }
                    }
                    return $result;
                })
            ];
            $products[] = $item;
        }
        $result = [
            'user_id' => strval($userId),
            'balance' => strval($userInfo['balance']),
            'products' => $products,
            'point_tips' => '1金币/1元',
            'tips' => '支付成功后,一般在2-10分钟内到账,如果超时未到账,请联系在线客服为您解决!',
            'description' => "受支付渠道影响,单次大金额充值成功率较低\n如果购买服务需要较多金币,可用小额分批购买"
        ];
        return $result;
    }

    /**
     * 充值
     * @param $type
     * @param $userId
     * @param $productId
     * @param $paymentId
     * @return array
     * @throws BusinessException
     */
    public function doRecharge($type, $userId, $productId, $paymentId)
    {
        $userModel = $this->userService->findByID($userId);
        $this->userService->checkUser($userModel);
        $product = $this->productService->getInfo($productId);
        if (empty($product) || $product['is_disabled'] == 'y' || $product['type'] != $type) {
            throw new BusinessException(StatusCode::DATA_ERROR, '当前套餐已下架!');
        }
        if (!$this->commonService->checkActionLimit('user_recharge_' . $userId, 20, 2)) {
            throw new BusinessException(StatusCode::DATA_ERROR, '慢点慢点,我受不了!');
        }

        //追加用户24小时内8单未支付就锁定
        if($this->rechargeService->count(['user_id' => intval($userId),'status' => 0,'created_at' => ['$gt'=>time()-24*3600]]) > 8){
            throw new BusinessException(StatusCode::DATA_ERROR, '慢点慢点,请客官不要着急!');
        }

        $configs = getConfigs();
        $registerDate = date('Y-m-d', $userModel['register_at'] * 1);
        $data = array(
            'order_sn' => CommonUtil::createOrderNo($configs['order_prefix'].'R'),
            'user_id' => $userId,
            'device_type' => $userModel['device_type'],
            'username' => $userModel['username'],
            'status' => 0,
            'amount' => $product['price'] * 1,
            'real_amount' => 0,
            'product_id' => $productId,
            'give' => $product['gift_num'] * 1,//赠送金币
            'vip' => $product['vip_num'] * 1,//赠送vip
            'num' => $product['num'] * 1,
            'record_type' => $product['type'],
            'fee' => 0,
            'pay_id' => $paymentId,
            'pay_name' => value(function () use ($paymentId) {
                $paymentItems = $this->paymentService->getPaymentList('point', 'api');
                return isset($paymentItems[$paymentId]['type']) ? $paymentItems[$paymentId]['type'] : '';
            }),
            'pay_at' => 0,
            'pay_rate' => 0,
            'pay_date' => '',
            'trade_sn' => '',
            'channel_name' => strval($userModel['channel_name']),
            'register_at' => $userModel['register_at'] * 1,
            'register_date' => $registerDate,
            'is_new_user' => $this->userService->isNewUser($userModel) ? 1 : 0,
            'register_ip' => $userModel['register_ip'],
            'created_ip' => getClientIp(),
            'created_at' => time(),
        );

        $orderId = $this->rechargeService->save($data);
        if (empty($orderId)) {
            throw new BusinessException(StatusCode::DATA_ERROR, '支付繁忙,请稍后再试!');
        }

        DataCenterService::doRechargeOrder($orderId,$data['product_id'],$data['num'],$data['amount'],$data['created_at']);

        $result = $this->paymentService->createPayLink($data['record_type'], $paymentId, $orderId, $data['amount'], $data['order_sn'], $this->apiService->getDeviceType(), $userModel);
        $this->rechargeService->save(['pay_id' => $result['payment_id'] * 1, '_id' => $orderId]);
        return [
            'url' => $result['payment_url'],
            'type' => $result['payment_type'],
            'need_jump' => $result['payment_url'] ? 'y' : 'n',
            'msg' => $result['payment_url'] ? '' : '发起支付失败'
        ];
    }

    /**
     * @param $userId
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function getChats($userId, $page = 1, $pageSize = 15)
    {
        $result = array();
        $skip = ($page - 1) * $pageSize;
        $items = $this->chatService->getList(array('user_id' => $userId), array(), array('sort' => -1), $skip, $pageSize);
        $hasSystemMessage = false;
        foreach ($items as $item) {
            $user = $this->userService->getInfoFromCache($item['to_user_id']);
            if (empty($user)) {
                continue;
            }
            if ($item['to_user_id'] == -1) {
                $hasSystemMessage = true;
            }
            $result[] = array(
                'id' => $item['_id'],
                'user_id' => strval($item['to_user_id']),
                'nickname' => $user['nickname'],
                'head_img' => $this->commonService->getCdnUrl($user['img']),
                'content' => strval($item['content']),
                'status' => strval($item['status'] * 1),
                'time_label' => CommonUtil::ucTimeAgo($item['updated_at'])
            );
        }
        if ($page == 1 && !$hasSystemMessage) {
            array_unshift($result, $this->getDefaultChat());
        }
        return array_values($result);
    }

    /**
     * 获取默认的空回话
     * @return array
     */
    public function getDefaultChat()
    {
        $user = $this->userService->getInfoFromCache(-1);
        return array(
            'id' => strval($user['id']),
            'user_id' => strval($user['id']),
            'nickname' => $user['nickname'],
            'head_img' => $this->commonService->getCdnUrl($user['img']),
            'content' => $this->commonService->getConfig('welcome_msg'),
            'status' => '0',
            'time_label' => date('Y-m-d')
        );
    }

    /**
     * 获取快捷回复
     */
    public function getChatFaq()
    {
        $items = $this->articleService->getArticleList('faq', 1, 15);
//        array_push($items, [
//            'id' => '-1',
//            'title' => '转人工客服, 在线解答',
//            'content' => mt_rand(1, 6) . '号客服，为您服务，请直接描述您的问题',
//            'created_at' => '',
//        ]);
        return $items;
    }

    /**
     * 删除会话
     * @param $userId
     * @param $toUserId
     * @return bool
     */
    public function delChat($userId, $toUserId)
    {
        $this->chatService->delChat($userId, $toUserId);
        return true;
    }

    /**
     * 获取会话消息
     * @param $userId
     * @param $toUserId
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function getChatMessages($userId, $toUserId, $page = 1, $pageSize = 15)
    {
        $result = array();
        $userIds = array($userId, $toUserId);
        $chatId = md5(min($userIds) . '_' . max($userIds) . '_chat');
        $skip = ($page - 1) * $pageSize;
        $items = $this->messageService->getList(
            array('chat_id' => $chatId),
            array('_id', 'chat_id', 'user_id', 'to_user_id', 'type', 'content', 'ext', 'created_at', 'ip'),
            array('created_at' => -1),
            $skip,
            $pageSize
        );
        foreach ($items as $item) {
            if ($item['type'] == 'image') {
                $item['content'] = $this->commonService->getCdnUrl($item['content']);
            }
            $user = $this->userService->getInfoFromCache($item['user_id']);
            $row = array(
                'id' => strval($item['_id']),
                'is_my' => $item['user_id'] == $userId ? 'y' : 'n',
                'type' => $item['type'],
                'user_id' => strval($item['user_id']),
                'nickname' => strval($user['nickname']),
                'head_img' => $this->commonService->getCdnUrl($user['img']),
                'content' => strval($item['content']),
                'ext' => strval($item['ext']),
                'time_label' => CommonUtil::ucTimeAgo($item['created_at'])
            );
            $result[] = $row;
        }
        return $result;
    }

    /**
     * 发送消息
     * @param $userId
     * @param $toUserId
     * @param $type
     * @param $content
     * @param $ext
     * @return bool
     * @throws BusinessException
     */
    public function sendMessage($userId, $toUserId, $type, $content, $ext)
    {
        $user = $this->userService->getInfoFromCache($userId);
        if (empty($user)) {
            throw new BusinessException(StatusCode::DATA_ERROR, '用户不存在!');
        }
        if ($user['is_disabled'] && $toUserId != -1) {
            throw new BusinessException(StatusCode::DATA_ERROR, '当前用户已经被禁用!');
        }
        if ($user['is_vip'] != 'y' && $toUserId != -1) {
            throw new BusinessException(StatusCode::DATA_ERROR, '为防止广告和用户隐私安全,私信只对付费VIP开放!');
        }
        $this->chatService->send($userId, $toUserId, $type, $content, $ext);
        return true;
    }


    /**
     * 余额日志
     * @param $userId
     * @param $page
     * @return mixed
     */
    public function getAccountLogs($userId, $page)
    {
        return $this->userService->getAccountLogs($userId, $page);
    }

    /**
     * 去关注
     * @param $userId
     * @param $homeId
     * @return string
     * @throws BusinessException
     */
    public function doFollow($userId, $homeId)
    {
        return $this->userFollowService->do($userId, $homeId);
    }

    /**
     * 获取关注列表
     * @param $userId
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function getFollowList($userId, $page = 1, $pageSize = 20)
    {
        return $this->userFollowService->getFollowList($userId, $page, $pageSize);
    }

    /**
     * 获取粉丝
     * @param $userId
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function getFansList($userId, $page = 1, $pageSize = 20)
    {
        return $this->userFollowService->getFansList($userId, $page, $pageSize);
    }


    /**
     * 福利任务
     * @param $userId
     * @return array
     * @throws BusinessException
     */
    public function getTask($userId)
    {
        $user = $this->userService->getInfoFromCache($userId);
        if (empty($user)) {
            throw new BusinessException(StatusCode::DATA_ERROR, '用户不存在!');
        }
        if ($user['is_disabled']) {
            throw new BusinessException(StatusCode::DATA_ERROR, '当前用户已经被禁用!');
        }
        $configs = getConfigs();
        $result = array(
            'user' => array(
                'user_id' => strval($userId),
                'username' => $user['username'],
                'nickname' => $user['nickname'],
                'share_num' => strval($user['share_num'] * 1),
                'integral' => strval($user['integral'] * 1),
                'img' => $this->commonService->getCdnUrl($user['img']),
                'is_vip' => $this->userService->isVip($user) ? 'y' : 'n',
                'vip_tips' => value(function () use ($user) {
                    if (!$this->userService->isVip($user)) {
                        return '会员到期时间:游客';
                    }
                    if ($user['group_end_time'] > strtotime("2031-01-01")) {
                        return 'VIP到期时间:永久';
                    }
                    if ($user['group_end_time']) {
                        return 'VIP到期时间:' . date("Y-m-d H:i", $user['group_end_time']);
                    }
                    return "VIP到期时间:游客";
                })
            ),
            'task_tips' => '提示:如果状态未更新 请下拉刷新哦',
            'task_items' => array()
        );

        //签到
        $signedDays =empty($user['sign_days'])?[]:$user['sign_days'];
        $today = date('Y-m-d');
        $startTime = CommonUtil::getTodayEndTime()+1;
        $signDays = $this->getSignConfig($configs);
        foreach ($signDays as $day => $signItem) {
            $nextDay = $startTime - $day*24*3600;
            $nextDay = date('Y-m-d',$nextDay);
            $signDays[$day] = array(
                'day' => $signItem["day"],
                'name' => sprintf('第%s天', $signItem["day"]),
                'num' => $signItem["num"],
                'has_done' => in_array($nextDay,$signedDays)?'y':'n'
            );
        }
        $result['sign'] = array(
            'info' => sprintf('已经签到%s天',count($signedDays)),
            'has_done' => in_array($today,$signedDays)?'y':'n',
            'items' => array_values($signDays)
        );

        $taskList = $this->userTaskService->getAll();
        foreach ($taskList as $item) {
            $task = array(
                'id' => $item['id'],
                'type' => $item['type'],
                'name' => $item['name'],
                'description' => $item['description'],
                'status' => '0',
                'status_text' => '',
                'link' => $item['link']
            );
            if ($item['type'] == 'share') {
                $task['status_text'] = '去邀请';
            } elseif ($item['type'] == 'download') {
                $hasDone = $this->userTaskService->has($userId, $task['id']);
                if ($hasDone) {
                    $task['status'] = '2';
                    $task['status_text'] = '已领取';
                } else {
                    $logKey = $this->userTaskService->getTaskLogId($userId, $task['id']);
                    $hasClick = $this->getRedis()->get($logKey);
                    if ($hasClick) {
                        $task['status'] = '1';
                        $task['status_text'] = '去领取';
                    } else {
                        $task['status_text'] = '立即下载';
                    }
                }
            } elseif ($item['type'] == 'comment') {
                $logKey = $this->userTaskService->getTaskLogId($userId, $task['id']);
                $num = $this->getRedis()->get($logKey);
                $maxNum = empty($configs['user_task_comment_limit']) ? 3 : intval($configs['user_task_comment_limit']);
                if ($num < 1) {
                    $task['status_text'] = '未完成';
                } else {
                    if ($maxNum > $num) {
                        $task['status'] = '1';
                        $task['status_text'] = $num . '/' . $item['max_limit'];
                    } else {
                        $task['status'] = '2';
                        $task['status_text'] = '已领取';
                    }
                }
            } elseif ($item['type'] == 'login') {
                $hasDone = $this->userTaskService->has($userId, $task['id']);
                if ($hasDone) {
                    $task['status'] = '2';
                    $task['status_text'] = '已领取';
                } else {
                    $task['status'] = '1';
                    $task['status_text'] = '去领取';
                }
            } else {
                continue;
            }
            $result['task_items'][] = $task;
        }
        return $result;
    }

    /**
     * 记录点击事件
     * @param $userId
     * @param $taskId
     * @return bool
     * @throws BusinessException
     */
    public function doTaskLog($userId, $taskId)
    {
        return $this->userTaskService->doTaskLog($userId, $taskId);
    }

    /**
     * 执行福利任务
     * @param $userId
     * @param $taskId
     * @return bool
     * @throws BusinessException
     */
    public function doTask($userId, $taskId)
    {
        return $this->userTaskService->doTask($userId, $taskId);
    }

    /**
     * 获取签到金币信息
     * @param null $configs
     * @return array
     */
    public function getSignConfig($configs = null)
    {
        if (empty($configs)) {
            $configs = getConfigs();
        }
        $signItems = $configs['sign_configs'];
        $signItems = explode("\n", $signItems);
        $signDays = array();
        foreach ($signItems as $signItem) {
            $signItem = trim($signItem);
            if (empty($signItem)) {
                continue;
            }
            $signItem = explode('|', $signItem);
            $signDays[$signItem[0]] = array(
                'day' => $signItem[0],
                'num' => $signItem[1],
            );
        }
        return $signDays;
    }

    /**
     * 返回签到天数
     * @param $signDays
     * @param array $validDays
     * @return int
     */
    public function getSignDayNum($signDays, &$validDays = array())
    {
        $startDay = CommonUtil::getTodayZeroTime();
        $dayNum = 1;
        for ($day = 1; $day < 7; $day++) {
            $nextDay = date('Y-m-d', $startDay - $day * 24 * 3600);
            if (in_array($nextDay, $signDays)) {
                $dayNum = $day + 1;
                $validDays[] = $nextDay;
            } else {
                break;
            }
        }
        return $dayNum;
    }

    /**
     *  签到
     * @param $userId
     * @return bool
     * @throws BusinessException
     */
    public function doDaySign($userId)
    {
        $user = $this->userService->getInfoFromCache($userId);
        if (empty($user)) {
            throw new BusinessException(StatusCode::DATA_ERROR, '用户不存在!');
        }
        if ($user['is_disabled']) {
            throw new BusinessException(StatusCode::DATA_ERROR, '当前用户已经被禁用!');
        }
        $signDays = empty($user['sign_days']) ? [] : $user['sign_days'];
        $today = date('Y-m-d');
        if (in_array($today, $signDays)) {
            throw new BusinessException(StatusCode::DATA_ERROR, '今日已经签到!');
        }
        //已经签到7天了 开始下个轮询
        if (count($signDays) >= 7) {
            $signDays = [];
        }
        $signItems = $this->getSignConfig(getConfigs());
        $validDays = [];
        $dayNum = $this->getSignDayNum($signDays, $validDays);
        $integral = $signItems[$dayNum]['num'] * 1;
        if ($integral < 1) {
            throw new BusinessException(StatusCode::DATA_ERROR, '签到配置错误!');
        }
        array_unshift($validDays, $today);
        $this->userService->updateRaw(
            array(
                '$inc' => array('integral' => $integral),
                '$set' => array('sign_days' => array_values($validDays))
            ), array('_id' => $userId * 1));
        $this->userService->setInfoToCache($userId);
        return true;
    }

    /**
     * 获取积分兑换页面数据
     * @param $userId
     * @return array
     * @throws BusinessException
     */
    public function getExchangeInfo($userId)
    {
        $user = $this->userService->getInfoFromCache($userId);
        if (empty($user)) {
            throw new BusinessException(StatusCode::DATA_ERROR, '用户不存在!');
        }
        if ($user['is_disabled']) {
            throw new BusinessException(StatusCode::DATA_ERROR, '当前用户已经被禁用!');
        }
        $result = array(
            'user' => array(
                'user_id' => strval($userId),
                'username' => $user['username'],
                'nickname' => $user['nickname'],
                'share_num' => strval($user['share_num'] * 1),
                'integral' => strval($user['integral'] * 1),
                'img' => $this->commonService->getCdnUrl($user['img']),
                'is_vip' => $this->userService->isVip($user) ? 'y' : 'n',
                'vip_tips' => value(function () use ($user) {
                    if (!$this->userService->isVip($user)) {
                        return '会员到期时间:游客';
                    }
                    if ($user['group_end_time'] > strtotime("2031-01-01")) {
                        return 'VIP到期时间:永久';
                    }
                    if ($user['group_end_time']) {
                        return 'VIP到期时间:' . date("Y-m-d H:i", $user['group_end_time']);
                    }
                    return "VIP到期时间:游客";
                })
            ),
            'exchange_items' => array_values($this->userTaskService->getExchangeItems())
        );
        return $result;
    }

    /**
     * 积分兑换
     * @param $userId
     * @param $num
     * @return bool
     * @throws BusinessException
     */
    public function doExchange($userId, $num)
    {
        $userId = $userId * 1;
        $num = $num * 1;
        if ($userId < 1 || $num < 1) {
            throw new BusinessException(StatusCode::DATA_ERROR, '参数错误!');
        }
        $user = $this->userService->findByID($userId);
        if (empty($user)) {
            throw new BusinessException(StatusCode::DATA_ERROR, '用户不存在!');
        }
        if ($user['is_disabled']) {
            throw new BusinessException(StatusCode::DATA_ERROR, '当前用户已经被禁用!');
        }
        $exchangeItems = $this->userTaskService->getExchangeItems();
        if (empty($exchangeItems[$num])) {
            throw new BusinessException(StatusCode::DATA_ERROR, '兑换项不存在!');
        }
        $exchange = $exchangeItems[$num];
        if ($num > $user['integral']) {
            throw new BusinessException(StatusCode::DATA_ERROR, '可用积分不足!');
        }
        if (empty($exchange['day']) || empty($exchange['group'])) {
            throw new BusinessException(StatusCode::DATA_ERROR, '兑换错误!');
        }

        $this->userService->doChangeGroup($userId, $exchange['day'] * 1, $exchange['group'] * 1, true);
        $this->userService->updateRaw(array('$inc' => array('integral' => $num * -1)), array('_id' => $userId));
        return true;
    }

    /**
     *  获取up主列表
     * @param $userId
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function getUpList($userId,$page=1,$pageSize=12)
    {
        $userUps =  $this->userUpService->getList([],[],['sort'=>-1,'_id'=>1],($page-1)*$pageSize,$pageSize);
        $result = [];
        foreach ($userUps as $userUp)
        {
             $userInfo = $this->userService->getInfoFromCache($userUp['user_id']);
             if(!empty($userInfo)){
                 $result[] = array(
                     'id' => strval($userInfo['id']),
                     'username'=> $userInfo['username'],
                     'nickname' => strval($userInfo['nickname']),
                     'is_up' => $userInfo['is_up'],
                     'sign' => strval($userInfo['sign']),
                     'img' => $this->commonService->getCdnUrl($userInfo['img']),
                     'has_follow' => $this->userFollowService->has($userId,$userInfo['id'])?'y':'n',
                     'fans' => strval($userInfo['fans']*1),
                     'follow'=>strval($userInfo['follow']*1)
                 );
             }
        }
        return $result;
    }

    public function getCustomerUrl($userId)
    {
        $user                   = $this->userService->getInfoFromCache($userId);
        $user['device_version'] = $user['device_version'] ?? '1.0';
        return $this->customerSystemService->getUrl($user['id'], $user['device_type'], $user['device_version']);
    }
}