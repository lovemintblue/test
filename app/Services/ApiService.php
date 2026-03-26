<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\StatusCode;
use App\Core\Services\BaseService;
use App\Core\Services\RequestService;
use App\Exception\BusinessException;
use App\Jobs\Center\CenterDataJob;
use App\Models\AppLogModel;
use App\Utils\AesUtil;
use App\Utils\CommonUtil;
use App\Utils\LogUtil;

/**
 * Class ApiService
 * @package App\Services
 * @property RequestService
 * @property TokenService $tokenService
 * @property AppLogModel $appLogModel
 * @property UserService $userService
 * @property UserBalanceService $userBalanceService
 * @property ChannelReportService $channelReportService
 */
class ApiService extends BaseService
{
    const IOS_KEY = "8ed2a631a6ab789a";
    const ANDROID_KEY = "a31b32364ce19c18";
    const DEBUG_KEY = "8ed1a631a6ab789a34256b44ff571476";
    const HEADER_KEYWORDS = 'Dart';

    protected $version;
    protected $deviceType;
    protected $token;
    protected $deviceId;
    protected $time;
    protected $isDebug = false;
    protected $encodeKey;
    protected $tokenInfo = array();

    protected $dayReports = array(
        'comics/detail',
        'movie/detail',
        'post/detail',
        'user/info',
        'comics/home',
    );

    /**
     * 获取当地
     * @return ApiService
     */
    public static function factory()
    {
        return getAutoClass(ApiService::class);
    }

    /**
     * 加密数据
     * @param $data
     * @return bool|string
     */
    public function encryptData($data)
    {
        if ($this->isDebug) {
            return $data;
        }
        $data = json_encode($data);
        if($this->deviceType=='h5'){
            return AesUtil::encryptBase64($data, $this->encodeKey);
        }else{
            return AesUtil::encryptRaw($data, $this->encodeKey);
        }
    }


    /**
     * 监听数据
     * @throws BusinessException
     */
    public function handler()
    {
        $url=$_REQUEST['_url'];
        $this->version = getHeaderLine('version');
        $this->deviceType = getHeaderLine('deviceType');
        $this->time = getHeaderLine('time');
        $debugKey = getHeaderLine('debugKey');
        $this->getEncodeKey();
        if (empty($this->version) || empty($this->deviceType) || empty($this->time)) {
            throw new BusinessException(StatusCode::DATA_ERROR, '数据封装错误!');
        }
        if (!in_array($this->deviceType, array('ios', 'android','h5'))) {
            throw new BusinessException(StatusCode::DATA_ERROR, '设备信息错误!');
        }
        if (!empty($debugKey) && $debugKey == self::DEBUG_KEY) {
            $this->isDebug = true;
        }
        $this->time = strtotime($this->time);
        $this->checkRequestSafe();
        $_REQUEST['_url']=$url;
        $this->addAppLogs();
        $this->balanceTransfer();

        $this->addDataCenter();
    }

    /**
     * 增加app记录
     */
    public function addAppLogs()
    {
        $controllerName = $this->getDispatcher()->getControllerName();
        $actionName = $this->getDispatcher()->getActionName();
        $urlKey = $controllerName . '/' . $actionName;
        if (in_array($urlKey, $this->dayReports)) {
            $token = $this->getToken();
            if (empty($token)) {
                return;
            }
            $userId = $token['user_id'];
            if (empty($userId)) {
                return;
            }

            $user = $this->userService->getInfoFromCache($userId);
            if (empty($user)) {
                return;
            }

            //记录pv
            $this->channelReportService->doPV($user['channel_name']);

            $keyName = 'app_log_' . date('Y-m-d') . $userId;
            $keyName = md5($keyName);
            $result = getCache($keyName);
            if (empty($result)) {
//                $user = $this->userService->getInfoFromCache($userId);
//                if (empty($user)) {
//                    return;
//                }
                $this->channelReportService->doUV($user['channel_name'], $userId);

                $registerDate = date('Y-m-d', $user['register_at'] * 1);
                $isNew = $registerDate == date('Y-m-d');
                $data = array(
                    '_id' => $keyName,
                    'date' => date('Y-m-d'),
                    'user_id' => $userId,
                    'ip' => getClientIp(),
                    'month' => date('Y-m'),
                    'channel_name' => strval($user['channel_name']),
                    'device_type' => $user['device_type'],
                    'register_date' => $registerDate,
                    'is_new_user' => $isNew?1:0,
                    'is_valid' => 0,
                    'created_at' => time(),
                    'updated_at' => time(),
                );
                $this->appLogModel->findAndModify(array('_id' => $keyName), $data, array(), true);
                $endTime = CommonUtil::getTodayEndTime() - time() + 180;
                setCache($keyName, 1, $endTime);
            }
        }
    }


    /**
     * 检查app安全
     * @throws BusinessException
     */
    public function checkRequestSafe()
    {
        if ($this->isDebug) {
            $this->token = getHeaderLine('token');
            $this->deviceId = getHeaderLine('deviceId');
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            throw new BusinessException(StatusCode::DATA_ERROR, '安全性错误R!');
        }
        if($this->deviceType!='h5'){
            if (empty($_SERVER['HTTP_USER_AGENT']) || strpos($_SERVER['HTTP_USER_AGENT'], ApiService::HEADER_KEYWORDS) === false) {
                throw new BusinessException(StatusCode::DATA_ERROR, '安全性错误A!');
            }
        }
        /*if ((time() - $this->time) > 300) {
            throw new BusinessException(StatusCode::DATA_ERROR, '安全性错误T!');
        }*/
        $data = file_get_contents("php://input");
        if($this->deviceType=='h5' || $this->deviceType=='ios'){
            $data = AesUtil::decryptBase64($data, $this->encodeKey);
            $data = trim($data,"'");
        }else{
            $data = AesUtil::decryptRaw($data, $this->encodeKey);
        }
        if (empty($data)) {
            throw new BusinessException(StatusCode::DATA_ERROR, '安全性错误D!');
        }
        $data = json_decode($data, true);
        if (empty($data['deviceId'])) {
            throw new BusinessException(StatusCode::DATA_ERROR, '安全性错误DD!');
        }
        $this->deviceId = trim($data['deviceId']);
        if (!empty($data['token'])) {
            $this->token = $data['token'];
        }
        $_REQUEST = empty($data['data']) ? array() : $data['data'];
    }


    /**
     * 数据中心
     * @return void
     */
    public function addDataCenter()
    {
        $token = $this->getToken();
        if($token){
            $userInfo = $this->userService->getInfoFromCache($token['user_id']);
        }
        $configs = CenterDataJob::getCenterConfig('data');

        # 数据中心初始化
        DataCenterService::setRedis(container()->get('redis'));
        DataCenterService::setSessionId();
        DataCenterService::setDeviceType($this->deviceType);
        DataCenterService::setDeviceId($userInfo['device_id']??$this->deviceId);
        DataCenterService::setClientIp(getClientIp());
        DataCenterService::setAppid($configs['appid']);
        DataCenterService::setUserId($token['user_id']??'');
        DataCenterService::setUserAgent($_SERVER['HTTP_USER_AGENT']);
        DataCenterService::setChannelCode($userInfo['channel_name']??'');
    }

    /**
     * @return mixed|string
     */
    public function getEncodeKey()
    {
        if ($this->deviceType == 'ios' || $this->deviceType=='h5') {
            $this->encodeKey = self::IOS_KEY;
        } else {
            $this->encodeKey = self::ANDROID_KEY;
        }
        //不同版本不同key
        $config = container()->get('config')->api;
        if($config){
            $encodeKeys = $config->toArray()[$this->deviceType.'_key'];
            if($encodeKeys){
                krsort($encodeKeys);
            }
            foreach($encodeKeys as $key=>$val){
                if($this->getVersion()>=$key){
                    $this->encodeKey = $val;
                    break;
                }
            }
        }
        return $this->encodeKey;
    }

    /**
     * @return mixed
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @return mixed
     */
    public function getDeviceType()
    {
        return $this->deviceType;
    }

    /**
     * 获取token
     * @return mixed
     */
    public function getToken()
    {
        if (!empty($this->tokenInfo)) {
            return $this->tokenInfo;
        }
        $token = $this->token;
        if (empty($token)) {
            return null;
        }
        $token = explode('_', $token);
        $tokenInfo = $this->tokenService->get($token[1], 'user');
        if (empty($tokenInfo) || $tokenInfo['user_id'] != $token[1] || $tokenInfo['token']!=$token[0]) {
            return null;
        }
        $tokenInfo['user_id'] = intval($tokenInfo['user_id']);
        $this->tokenInfo = $tokenInfo;
        return $this->tokenInfo;
    }

    /**
     * @return mixed
     */
    public function getDeviceId()
    {
        return $this->deviceId;
    }

    /**
     * @return bool
     */
    public function isDebug(): bool
    {
        return $this->isDebug;
    }

    /**
     * 改变状态游戏下分
     * @return void
     */
    public function balanceTransfer()
    {
        $controllerName = $this->getDispatcher()->getControllerName();
        $actionName = $this->getDispatcher()->getActionName();
        $urlKey = $controllerName . '/' . $actionName;
        if(!in_array($urlKey,['ai/girlFriendBringOutAssets','ai/getGirlFriendAuthUrl'])){
            $token = $this->getToken();
            if (empty($token)) {
                return;
            }
            $userId = $token['user_id'];
            if (empty($userId)) {
                return;
            }
            $row = $this->userBalanceService->findByID($userId);
            if($row['status']==1){
                $this->userBalanceService->userBalanceModel->update(['status'=>2],['_id'=>$userId]);
            }
        }
    }


}