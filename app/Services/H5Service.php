<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\StatusCode;
use App\Core\Services\BaseService;
use App\Core\Services\RequestService;
use App\Exception\BusinessException;
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
 */
class H5Service extends BaseService
{
    const H5_KEY = "0e3d2cf6f78dc8d8";
    const DEBUG_KEY = "8ed1a631a6ab789a34256b44ff571476";

    protected $version;
    protected $deviceType;
    protected $token;
    protected $time;
    protected $deviceId;
    protected $isDebug = false;
    protected $encodeKey;
    protected $tokenInfo = array();

    protected $dayReports = array(
        'movie/detail',
        'cartoon/detail',
//        'user/info'
    );

    /**
     * 获取当地
     * @return ApiService
     */
    public static function factory()
    {
        return getAutoClass(self::class);
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
        return base64_encode(AesUtil::encryptRaw($data, $this->encodeKey));
    }


    /**
     * 监听数据
     * @throws BusinessException
     */
    public function handler()
    {
        $this->version = getHeaderLine('version')?:'1.0';
        $this->deviceType = getHeaderLine('deviceType')?:'web';
        $this->time = getHeaderLine('time');
        $debugKey = getHeaderLine('debugKey');
        if (empty($this->deviceType) || empty($this->time)) {
            throw new BusinessException(StatusCode::DATA_ERROR, '数据封装错误!');
        }
        if (!in_array($this->deviceType, array('web'))) {
            throw new BusinessException(StatusCode::DATA_ERROR, '设备信息错误!');
        }
        $this->encodeKey = self::H5_KEY;
        if (!empty($debugKey) && $debugKey == self::DEBUG_KEY) {
            $this->isDebug = true;
        }
        $this->time = strtotime($this->time);
        $this->checkRequestSafe();
        $this->addAppLogs();
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
            $keyName = 'app_log_' . date('Y-m-d') . $userId;
            $keyName = md5($keyName);
            $result = getCache($keyName);
            if (empty($result)) {
                $user = $this->userService->getInfoFromCache($userId);
                if (empty($user)) {
                    return;
                }
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
            $data = file_get_contents("php://input");
            $data = json_decode($data, true);
            if(empty($data)){
                $_REQUEST = isset($_REQUEST['data'])?$_REQUEST['data']:$_REQUEST;
            }else{
                $_REQUEST=$data['data'];
                $this->token = $data['token'];
                $this->deviceId = $data['device_id'];
            }
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            throw new BusinessException(StatusCode::DATA_ERROR, '安全性错误R!');
        }
        if (empty($_SERVER['HTTP_USER_AGENT']) /*|| strpos($_SERVER['HTTP_USER_AGENT'], ApiService::HEADER_KEYWORDS) === false*/) {
            throw new BusinessException(StatusCode::DATA_ERROR, '安全性错误A!');
        }
        if ((time() - $this->time) > 60) {
//            throw new BusinessException(StatusCode::DATA_ERROR, '安全性错误T!');
        }
        $data = file_get_contents("php://input");
        if (empty($data)) {
            $_REQUEST = array();
            return;
        }
        $data = AesUtil::decryptRaw(base64_decode($data), $this->encodeKey);
        if (empty($data)) {
            throw new BusinessException(StatusCode::DATA_ERROR, '安全性错误D!');
        }
        $data = json_decode($data, true);
        if (!empty($data['token'])) {
            $this->token = $data['token'];
        }
        if (!empty($data['device_id'])) {
            $this->deviceId = $data['device_id'];
        }
        $_REQUEST = empty($data['data']) ? array() : $data['data'];
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
     * @return mixed
     */
    public function getDeviceId()
    {
        return $this->deviceId;
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
        if (empty($tokenInfo) || $tokenInfo['user_id'] != $token[1]) {
            return null;
        }
        $tokenInfo['user_id'] = intval($tokenInfo['user_id']);
        $this->tokenInfo = $tokenInfo;
        return $this->tokenInfo;
    }

    /**
     * @return bool
     */
    public function isDebug(): bool
    {
        return $this->isDebug;
    }


}