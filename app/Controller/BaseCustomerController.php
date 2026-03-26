<?php

declare(strict_types=1);

namespace App\Controller;

use App\Constants\StatusCode;
use App\Core\Controller\BaseController;
use App\Exception\BusinessException;
use App\Repositories\Backend\AdminUserRepository;
use App\Utils\AesUtil;

/**
 * Class BaseBackendController
 * @package App\Controller
 */
class BaseCustomerController extends BaseController
{
    /**
     * 初始化
     */
    public function initialize()
    {
//        if ($_SERVER['REQUEST_METHOD'] != 'GET') {
//            throw new BusinessException(StatusCode::DATA_ERROR, '安全性错误R!');
//        }
//        $data     = file_get_contents("php://input");
//        $data     = json_decode($data, true);
//        $_REQUEST = $data ?: [];
    }

    protected function sendSuccessResult($data = null)
    {
        $result = array(
            'code' => 200,
            'msg' => 'success',
            'tips' => '成功',
            'data'   => $data
        );
        $this->sendJson($result);
    }

    protected function sendErrorResult($error='',$errorCode = 4002)
    {
        $result = array(
            'code' => $errorCode,
            'msg' => 'error',
            'tips' => $error,
            'data'   => null
        );
        $this->sendJson($result);
    }


}