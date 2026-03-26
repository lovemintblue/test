<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\StatusCode;
use App\Core\Services\BaseService;
use App\Exception\BusinessException;
use App\Jobs\Center\CenterDataJob;
use App\Jobs\Common\UserPayJob;
use App\Models\CollectionsModel;
use App\Models\PaymentLogModel;
use App\Models\RechargeModel;
use App\Models\UserOrderModel;
use App\Utils\LogUtil;

/**
 * Class PaymentService
 * @package App\Services
 * @property MmsService $mmsService
 * @property CommonService $commonService
 * @property ApiService $apiService
 * @property UserService $userService
 * @property UserOrderModel $userOrderModel
 * @property RechargeModel $rechargeModel
 * @property AccountService $accountService
 * @property UserCouponService $userCouponService
 * @property CollectionsModel $collectionsModel
 * @property PaymentLogModel $paymentLogModel
 * @property QueueService $queueService
 * @property UserAgentService $userAgentService
 * @property ConfigService $configService
 * @property WssService $wssService
 * @property JobService $jobService
 */
class PaymentService extends BaseService
{
    /**
     * 获取支付方式列表
     * @param $type
     * @param $server
     * @return array
     */
    public function getPaymentList($type,$server)
    {
        $result = array();
        $payments = $this->mmsService->getPaymentList($type,$server);
        foreach ($payments as $payment) {
            $paymentUseType = $payment['can_use_type'];
            if ($paymentUseType != 'all' && strpos($paymentUseType, $type) === false) {
                continue;
            }
            $result[$payment['payment_id']] = array(
                'payment_id'    => strval($payment['payment_id']),
                'payment_name'  => strval($payment['payment_name']),
                'payment_ico'   => $this->commonService->getCdnUrl($payment['payment_ico']),
                'can_use_amount'=> strval($payment['can_use_amount']),
                'type'          => strval($payment['type'])
            );
        }
        return $result;
    }

    /**
     * 创建支付
     * @param $type
     * @param $paymentId
     * @param $orderId
     * @param $price
     * @param $orderSn
     * @param $deviceType
     * @param $user
     * @return array|null
     * @throws BusinessException
     */
    public function createPayLink($type, $paymentId, $orderId, $price, $orderSn,$deviceType, $user)
    {
        if (is_numeric($user)) {
            $user = $this->userService->findByID($user);
        }
        $notifyHost = $this->commonService->getConfig('pay_notice_url');
        $notifyUrl = createUrl('/payment/notify/' . $paymentId . '/' . $orderId . '/' . $type,array(),'Api');
        $notifyUrl = $notifyHost . $notifyUrl;
        if (!in_array($type, array('vip', 'point'))) {
            throw new BusinessException(StatusCode::DATA_ERROR, '创建支付错误!');
        }
        $deviceType= strtolower($deviceType)=='h5'?'ios':$deviceType;
        if (!in_array($deviceType, array('android', 'ios'))) {
            throw new BusinessException(StatusCode::DATA_ERROR, '创建支付错误!');
        }
        if ($type == 'point') {
            $type = 'recharge';
        }
        $data = array(
            'payment_id'    => $paymentId,
            'order_sn'      => $orderSn,
            'amount'        => $price,
            'user_id'       => $user['_id'],
            'username'      => $user['username'],
            'ip'            => getClientIp(),
            'notice_url'    => $notifyUrl,
            'device_type'   => $deviceType,
            'type'          => $type,
            'can_sdk'       => '1',
            'channel'       => $user['channel_name'],
            'reg_at'        => $user['register_at'],
            'reg_ip'        => $user['register_ip'],
        );
        $result = $this->mmsService->createPayLink($data);
        LogUtil::debug("userId:{$user['_id']} orderSn:{$orderSn} payId:".($result?$result['payment_id']:$paymentId). " res:".($result?"ok url:{$result['payment_url']}":"error "));
        if (empty($result)) {
//            $this->wssService->joinActionQueue($user['id'], 'do_pay', sprintf('%s||%s||%s', $type == 'recharge' ? 'point' : $type, $paymentId, ''), $data['ip']);
            throw new BusinessException(StatusCode::DATA_ERROR, '创建支付错误!');
        } else {
//            $this->wssService->joinActionQueue($user['id'], 'do_pay', sprintf('%s||%s||%s', $type == 'recharge' ? 'point' : $type, $result['payment_id'], $result['payment_url']), $data['ip']);
        }
        return $result;
    }

    /**
     * 支付通知处理
     * @param $paymentId
     * @param $orderId
     * @param $type
     */
    public function notify($paymentId, $orderId, $type)
    {
        $tradeNo = "";
        $money = "";
        $orderSn = "";
        $payAt = "";
        $payRate = "";
        $result = $this->mmsService->notify($orderSn, $tradeNo, $money, $payAt, $payRate);
        $doResult = false;
        if ($result && $tradeNo && $money > 0) {
            $doResult = $this->addPaymentLogs($type, $orderId, $orderSn, $tradeNo, $money, $payAt, $payRate);
        }
        if ($doResult) {
            $this->mmsService->stopNotify();
        }
        exit('error');
    }

    /**
     * 添加到付款日志表
     * @param $type
     * @param $orderId
     * @param $orderSn
     * @param $tradeNo
     * @param $money
     * @param $payAt
     * @param $payRate
     * @return bool
     */
    public function addPaymentLogs($type, $orderId, $orderSn, $tradeNo, $money, $payAt, $payRate)
    {
        $uniqueId = md5($type . '_' . $orderId);
        $count = $this->paymentLogModel->count(array('unique_id' => $uniqueId));
        if ($count > 0) {
            return true;
        }
        $data = array(
            'unique_id' => $uniqueId,
            'type'      => $type,
            'order_id'  => $orderId,
            'order_sn'  => $orderSn,
            'status'    => 0,
            'trade_no'  => $tradeNo,
            'money'     => doubleval($money),
            'pat_at'    => intval($payAt),
            'pay_rate'  => doubleval($payRate)
        );
        $this->paymentLogModel->insert($data);
        return true;
    }

    /**
     * 执行支付
     */
    public function doPaidJob()
    {
        $this->paymentLogModel->updateRaw(['$set'=>['status'=>0]],['status'=>-1]);
        $items = $this->paymentLogModel->find(array('status' => 0), array('_id','type'), array('_id' => -1), 0, 50);
        foreach ($items as $item) {
            $res=$this->doPaidOrder($item['_id']);
            LogUtil::info(sprintf('Do paid order: %s=>%s res:%s',$item['type'],$item['_id'],$res?'ok':'error'));
        }
    }

    /**
     * 处理支付
     * @param $id
     * @return bool
     */
    public function doPaidOrder($id)
    {
        $item = $this->paymentLogModel->findAndModify(['_id' => $id, 'status' => 0], ['$set' => ['status' => 1]]);
        if (empty($item) || $item['status'] != 0) {
            return false;
        }
        $doResult = false;
        $lastError = "";
        try {
            $type = $item['type'];
            $orderId = $item['order_id'];
            $tradeNo = $item['trade_no'];
            $money = $item['money'] * 1;
            $payAt = $item['pat_at'] * 1;
            $payRate = $item['pay_rate'] * 1;
            switch ($type) {
                case 'vip':
                    $doResult = $this->doPaidVipOder($orderId, $tradeNo, $money, $payAt, $payRate,true);
                    break;
                case 'point':
                    $doResult = $this->doPaidPointOrder($orderId, $tradeNo, $money, $payAt, $payRate,true);
                    break;
                default:
                    $lastError = '不识别的内容!';
                    break;
            }
            if ($doResult) {
                return true;
            }
        } catch (BusinessException $e) {
            $lastError = $e->getMessage();
            LogUtil::error(sprintf('%s in %s line %s',$e->getMessage(), $e->getFile(),$e->getLine()));
        } catch (\Exception $e) {
            $lastError = $e->getMessage();
            LogUtil::error(sprintf('%s in %s line %s',$e->getMessage(), $e->getFile(),$e->getLine()));
        }
        $this->paymentLogModel->updateRaw(array('$set' => array('status' => -1, 'error_msg' => $lastError)), array('_id' => $item['_id']));
        return false;
    }

    /**
     * 金币订单
     * @param $orderId
     * @param $tradeNo
     * @param $money
     * @param $payAt
     * @param $payRate
     * @param bool $isAdmin
     * @return bool
     * @throws BusinessException
     */
    public function doPaidPointOrder($orderId, $tradeNo, $money, $payAt, $payRate,$isAdmin=false)
    {
        $orderId = intval($orderId);
        $keyName = 'paid_point_order_' . $orderId;
        if ($isAdmin==false&&!$this->commonService->checkActionLimit($keyName, 30, 1)) {
            throw  new BusinessException(StatusCode::DATA_ERROR, '请求频繁!');
        }
        $order = $this->rechargeModel->findByID($orderId);
        if (empty($order) || $order['status'] == -1 || $order['record_type'] != 'point') {
            throw  new BusinessException(StatusCode::DATA_ERROR, '订单不存在');
        }
        if ($order['status'] == 1) {
            return true;
        }
        $checkMoney = ($money - $order['amount']);
        if ($checkMoney > 5 || $checkMoney < -5) {
            throw  new BusinessException(StatusCode::DATA_ERROR, '订单金额不匹配');
        }
        $user = $this->userService->findByID($order['user_id'] * 1);
        if (empty($user)) {
            throw  new BusinessException(StatusCode::DATA_ERROR, '用户不存在！');
        }
        $this->rechargeModel->getConnection()->startTransaction();
        try {
            $result1 = $this->rechargeModel->findAndModify(
                array('_id' => $orderId, 'status' => 0),
                array('$set' => array(
                    'status' => 1,
                    'pay_at' => $payAt * 1,
                    'pay_rate' => $payRate * 1,
                    'pay_date' => date('Y-m-d', $payAt),
                    'trade_sn' => $tradeNo,
                    'real_amount' => $money,
                    'updated_at' => time()
                )), array('_id'),
                false
            );
            $result2 = $this->accountService->addBalance($user, $order['order_sn'], intval($order['num']+$order['give']), 1, '充值金币', 'recharge_' . $orderId);
            //是否赠送vip
            if($order['vip']>0){
                $this->userService->doChangeGroup($user, $order['vip'], 1);
            }
            $data = array(
                'order_sn'  => $order['order_sn'],
                'trade_sn'  => $tradeNo,
                'user_id'   => $order['user_id'],
                'device_type'=> $order['device_type'],
                'price'     => $order['amount'],
                'real_price'=> $money,
                'record_type'=> 'point',
                'object_id' => $orderId,
                'pay_id'    => $order['pay_id'],
                'pay_name'  => $order['pay_name'],
                'pay_at'    => $payAt,
                'pay_date'  => date('Y-m-d', $payAt),
                'channel_name' => strval($order['channel_name']),
                'register_at' => $order['register_at'],
                'order_at'  => $order['created_at'],
                'is_new_user' => $order['is_new_user'],
                'register_date' => $order['register_date'],
            );
            $result3 = $this->collectionsModel->insert($data);
            if ($result1 && $result2 && $result3) {
                $this->rechargeModel->getConnection()->commitTransaction();
                //增加累计充值
                $this->userService->updateRaw(['$inc'=>['money_count'=>doubleval($order['amount'])],'$set'=>['is_valid'=>1]],['_id'=>intval($order['user_id'])]);
                $this->userService->setInfoToCache($order['user_id']);
                //投递任务
//                $this->jobService->create(new UserPayJob($order['user_id']),'mongodb');
                try {
                    $configs = CenterDataJob::getCenterConfig('data');
                    DataCenterService::setRedis(container()->get('redis'));
                    DataCenterService::setSessionId();
                    DataCenterService::setDeviceType($user['device_type']);
                    DataCenterService::setDeviceId($user['device_id']);
                    DataCenterService::setClientIp($order['created_ip']);
                    DataCenterService::setAppid($configs['appid']);
                    DataCenterService::setUserId($order['user_id']);
                    DataCenterService::setUserAgent('');
                    DataCenterService::setChannelCode($order['channel_name']);
                    DataCenterService::doRechargeOrderPay($orderId,$order['product_id'],$order['num'],$money,$order['pay_name'],$tradeNo,$payAt);
                }catch (\Exception $exception){
                }
                return true;
            }
        } catch (\Exception $exception) {
            $this->rechargeModel->getConnection()->abortTransaction();
            throw new \Exception($exception->getMessage());
        }
    }

    /**
     * vip订单
     * @param $orderId
     * @param $tradeNo
     * @param $money
     * @param $payAt
     * @param $payRate
     * @param bool $isAdmin
     * @return bool
     * @throws BusinessException
     */
    public function doPaidVipOder($orderId, $tradeNo, $money, $payAt, $payRate,$isAdmin=false)
    {
        $orderId = intval($orderId);
        $keyName = 'paid_point_order_' . $orderId;
        if ($isAdmin==false&&!$this->commonService->checkActionLimit($keyName, 30, 1)) {
            throw  new BusinessException(StatusCode::DATA_ERROR, '请求频繁!');
        }
        $order = $this->userOrderModel->findByID($orderId);
        if (empty($order) || $order['status'] == -1) {
            throw  new BusinessException(StatusCode::DATA_ERROR, '订单不存在');
        }
        if ($order['status'] == 1) {
            return true;
        }
        $checkMoney = ($money - $order['price']);
        if ($checkMoney > 7 || $checkMoney < -5) {
            throw  new BusinessException(StatusCode::DATA_ERROR, '订单金额不匹配');
        }
        $user = $this->userService->findByID($order['user_id'] * 1);
        if (empty($user)) {
            throw  new BusinessException(StatusCode::DATA_ERROR, '用户不存在！');
        }
        $this->userOrderModel->getConnection()->startTransaction();
        try {
            $result1 = $this->userOrderModel->findAndModify(
                ['_id' => $orderId, 'status' => 0],
                [
                    '$set'    =>[
                        'status'    => 1,
                        'pay_at'    => $payAt * 1,
                        'pay_rate'  => $payRate * 1,
                        'pay_date'  => date('Y-m-d', $payAt),
                        'trade_sn'  => $tradeNo,
                        'real_price'=> doubleval($money)
                    ]
                ], ['_id'], false
            );
            $result2 = $this->userService->doChangeGroup($user, $order['day_num'], $order['group_id']);
            //是否赠送金币
            if($order['gift_num']>0){
                $this->accountService->addBalance($user, $order['order_sn'], $order['gift_num'], 1, '购买会员,赠送金币', 'order_' . $orderId);
            }
            $data = [
                'order_sn'  => $order['order_sn'],
                'trade_sn'  => $tradeNo,
                'user_id'   => $order['user_id'],
                'device_type'=> $order['device_type'],
                'price'     => intval($order['price']),
                'real_price'=> doubleval($money),
                'record_type'=> 'vip',
                'object_id' => $orderId,
                'pay_id'    => $order['pay_id'],
                'pay_name'  => $order['pay_name'],
                'pay_at'    => $payAt,
                'pay_date'  => date('Y-m-d', $payAt),
                'channel_name'  => strval($order['channel_name']),
                'register_at'   => $order['register_at'],
                'order_at'      => $order['created_at'],
                'is_new_user'   => $order['is_new_user'],
                'register_date' => $order['register_date'],
            ];
            $result3 = $this->collectionsModel->insert($data);
            if ($result1 && $result2 && $result3) {
                $this->userOrderModel->getConnection()->commitTransaction();
                //是否赠送观影券
                if($order['discount_coupon']>0){
                    $this->userCouponService->toUser($order['user_id'],$order['discount_coupon'],'movie',20);
                }
                //增加累计充值
                $this->userService->updateRaw(['$inc'=>['money_count'=>doubleval($order['price'])],'$set'=>['is_valid'=>1]],['_id'=>intval($order['user_id'])]);
                $this->userService->setInfoToCache($order['user_id']);
//                DataCenterService::doVipOrderPay($orderId,$order['group_id'],$order['day_num'],$money,$order['pay_name'],$order['trade_sn']);
                //投递任务
//                $this->jobService->create(new UserPayJob($order['user_id']),'mongodb');
//                $this->userAgentService->orderMLM($order['user_id'],$order['price']);
                try {
                    # 数据中心初始化-由于支付是异步回调,所以需要单独设置
                    $configs = CenterDataJob::getCenterConfig('data');
                    DataCenterService::setRedis(container()->get('redis'));
                    DataCenterService::setSessionId();
                    DataCenterService::setDeviceType($user['device_type']);
                    DataCenterService::setDeviceId($user['device_id']);
                    DataCenterService::setClientIp($order['created_ip']);
                    DataCenterService::setAppid($configs['appid']);
                    DataCenterService::setUserId($order['user_id']);
                    DataCenterService::setUserAgent('');
                    DataCenterService::setChannelCode($order['channel_name']);
                    DataCenterService::doVipOrderPay($orderId,$order['group_id'],$order['day_num'],$money,$order['pay_name'],$tradeNo,$payAt);
                }catch (\Exception $exception){
                }
                return true;
            }
        } catch (\Exception $exception) {
            $this->userOrderModel->getConnection()->abortTransaction();
            throw new \Exception($exception->getMessage());
        }
    }

    /**
     * 订单回退
     * 取消订单拉黑用户
     * @param $orderSn
     * @return array
     * @throws \Exception
     */
    public function doBack($orderSn)
    {
        $mmsUrl = $this->configService->getConfig('mms_url');
        $mmsUrl = str_replace('http://','',$mmsUrl);
        $mmsUrl = str_replace('https://','',$mmsUrl);
        if(getClientIp()!=$mmsUrl){
            throw new \Exception('请求不被允许');
        }
        if(empty($orderSn)){
            throw new \Exception('参数错误');
        }
        $order = $this->collectionsModel->findFirst(['order_sn'=>strval($orderSn)]);
        if(empty($order)){
            throw new \Exception("订单 {$orderSn} 不存在");
        }
        $user = $this->userService->findByID($order['user_id']);
        if(empty($user)){
            throw new \Exception("订单 {$orderSn} 用户不存在");
        }
        //拉黑用户
        $this->userService->doDisabled($order['user_id'],'恶意退款,系统自动拉黑');
        return [
            'user_id'=>$user['_id'],
            'nickname'=>$user['nickname']
        ];
    }
}