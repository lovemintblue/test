<?php

declare(strict_types=1);

namespace App\Controller;

use App\Constants\StatusCode;
use App\Core\Controller\BaseController;
use App\Exception\BusinessException;
use App\Services\ApiService;
use App\Utils\LogUtil;

class BaseApiController extends BaseController
{
    /**
     * @var ApiService
     */
    protected $apiService;

    /**
     * 初始化
     * @throws BusinessException
     */
    public function initialize()
    {
        $this->apiService = ApiService::factory();
        $this->apiService->handler();
    }

    /**
     * 发送正确请求结果
     * @param string $data
     */
    protected function sendSuccessResult($data = null)
    {
        $result = array(
            'status' => 'y',
            'data' => $data,
            'time' => date('Y-m-d H:i:s')
        );
        $result = $this->apiService->encryptData($result);
        $this->send($result);
    }

    /**
     * 发送错误请求结果
     * @param string $error
     * @param integer $errorCode
     */
    protected function sendErrorResult($error = '', $errorCode = 2008)
    {
        $result = array(
            'status' => 'n',
            'error' => $error,
            'errorCode' => $errorCode
        );
        $result = $this->apiService->encryptData($result);
        $this->send($result);
    }

    /**
     * 发送数据
     * @param $result
     */
    protected function send($result)
    {
        if ($this->apiService->isDebug()) {
            $this->sendJson($result);
        }
        ob_clean();
        header('Content-type:application/octet-stream');
        header('Content-Length:' . strlen($result));
        $this->destroy();
        echo $result;
        exit;
    }

    /**
     * 获取token
     * @param bool $isExits
     * @return mixed|null
     */
    protected function getToken($isExits= true)
    {
        $token = $this->apiService->getToken();
        if(empty($token) && $isExits){
            $this->sendErrorResult("授权过期!",StatusCode::NO_LOGIN_ERROR);
        }
        return $token;
    }

    /**
     * 获取用户id
     * @param bool $isExits
     * @return string|null
     */
    protected function getUserId($isExits= true)
    {
        $token=$this->getToken($isExits);
        return $token['user_id']?:null;
    }
}