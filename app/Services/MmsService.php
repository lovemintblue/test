<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\StatusCode;
use App\Core\Services\BaseService;
use App\Exception\BusinessException;
use App\Utils\CommonUtil;
use App\Utils\LogUtil;

/**
 * 管理系统
 * @package App\Services
 * @property ConfigService $configService
 * @property ApiService $apiService
 * @property H5Service $h5Service
 */
class MmsService extends BaseService
{
    /**
     * 请求
     * @param $url
     * @param array $data
     * @return mixed
     * @throws BusinessException
     */
    public function doRequest($url, $data = array())
    {
        $baseUrl = $this->configService->getConfig('mms_url');
        $appId = $this->configService->getConfig('mms_appid');
        $appKey = $this->configService->getConfig('mms_appkey');
        $data = json_encode($data);
        $requestData = array(
            'appid' => $appId,
            'data' => $data,
            'sign' => md5($data . $appKey)
        );
        $result = CommonUtil::httpPost($baseUrl . '/api/' . $url, $requestData);
        $result = json_decode($result, true);
        if ($result['status'] == 'y') {
            return $result['data'];
        }
        throw new BusinessException(StatusCode::DATA_ERROR, empty($result['error']) ? '当前网络繁忙,请稍后再试' : $result['error']);
    }

    /**
     * 获取支付方式
     * @param $type
     * @param $server
     * @return array
     */
    public function getPaymentList($type,$server)
    {
        try {
            switch ($server){
                case 'api':
                    $deviceType = $this->apiService->getDeviceType();
                    $deviceType = strtolower($deviceType)=='h5'?'ios':$deviceType;
                    break;
                case 'web':
                default:
                    $deviceType='ios';
                    break;
            }
            $keyName = 'payment_list_' . $deviceType . '_' . $type;
            $result = getCache($keyName);
            if (empty($result)) {
                $url = 'pay/list';
                if ($type == 'game') {
                    $url = 'game/payment';
                }
                $result = $this->doRequest($url, [
                    'alipay_sdk' => 'y',
                    'device_type' => $deviceType
                ]);
                setCache($keyName, $result, 35);
            }
            if($result){
                $result = json_decode($result, true);
                foreach ($result as $index=>$item) {
                    $deviceTypes  = explode(',',$item['device_type']);
                    if(!in_array($deviceType,$deviceTypes)){
                        unset($result[$index]);
                    }
                }
                return  array_values($result);
            }
            return [];
        } catch (\Exception $exception) {
            LogUtil::info($exception->getMessage());
        }
        return array();
    }

    /**
     * 上报日活
     * @param $data
     * @return boolean
     * @throws BusinessException
     */
    public function doReport($data = array())
    {
        $result = $this->doRequest("common/report", $data);
        return empty($result) ? false : true;
    }

    /**
     * 上报统计
     * @param array $data
     * @return bool
     * @throws BusinessException
     */
    public function doAnalysis($data = array())
    {
        $result = $this->doRequest("common/analysis", $data);
        return empty($result) ? false : true;
    }

    /**
     * 发送短信
     * @param $phone
     * @param $code
     * @param $ip
     * @param string $country
     * @param string $sigKey
     * @return bool
     * @throws BusinessException
     */
    public function sendSms($phone, $code, $ip, $country = '+86', $sigKey = '', $type = '')
    {
        $data = array(
            'phone' => $phone,
            'code' => $code,
            'ip' => $ip,
            'sign_key' => $sigKey,
            'country' => $country,
            'type'=> $type
        );

        $result = $this->doRequest('common/sendsms', $data);
        if ($result !== null) {
            return true;
        }
        return false;
    }

    /**
     * 创建支付链接
     * @param $data
     * @return array|null
     * @throws BusinessException
     */
    public function createPayLink($data)
    {
        $url = 'pay/do';
        if ($data['type'] == 'game') {
            $url = 'game/dopay';
        }
        $result = $this->doRequest($url, $data);
        if ($result !== null) {
            $result = json_decode($result, true);
            return $result;
        }
        return null;
    }

    /**
     * 校验通知
     * @param $orderSn
     * @param $tradeNo
     * @param $money
     * @param $payAt
     * @param $payRate
     * @param null $userId
     * @param null $paymentId
     * @return bool
     */
    public function notify(&$orderSn, &$tradeNo, &$money, &$payAt, &$payRate,&$userId=null,&$paymentId=null)
    {
        $status = $_REQUEST['status'];
        if ($status != 'y' || empty($_REQUEST['data'])) {
            return false;
        }
        $appKey = $this->configService->getConfig('mms_appkey');
        $data = $_REQUEST['data'];
        $sign = md5($data . $appKey);
        if ($sign != $_REQUEST['sign']) {
            return false;
        }
        $data = json_decode($data, true);
        if (empty($data['order_sn']) || empty($data['trade_sn']) || empty($data['amount'])) {
            return false;
        }
        $orderSn = $data['order_sn'];
        $tradeNo = $data['trade_sn'];
        $money = $data['real_amount'];
        $payAt = strtotime($data['pay_at']) ?: time();
        $payRate = $data['pay_rate'];
        $userId = $data['user_id'] * 1;
        $paymentId = $data['payment_id'] * 1;
        return true;
    }

    /**
     * 支付通知
     */
    public function stopNotify()
    {
        ob_clean();
        exit('success');
    }
}