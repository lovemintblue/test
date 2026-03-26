<?php

declare(strict_types=1);

namespace App\Controller\Backend;

use App\Controller\BaseBackendController;
use App\Exception\BusinessException;
use App\Repositories\Backend\AdminUserRepository;
use App\Repositories\Backend\SystemRepository;


/**
 * Class LoginController
 * @package App\Controller\Backend
 * @property  AdminUserRepository $adminUserRepo
 * @property  SystemRepository $systemRepository
 */
class LoginController extends BaseBackendController
{
    /**
     * 登录
     */
    public function indexAction()
    {
        $token = $this->getToken();
        if(!empty($token)){
            $this->redirect('/index');
        }
    }

    /**
     * 登录系统
     * @throws BusinessException
     */
    public function doAction()
    {
        $username = $this->getRequest("username");
        $password = $this->getRequest("password");
        $googleCode = $this->getRequest("google_code");
        if (empty($username) || empty($password) || empty($googleCode)) {
            $this->sendErrorResult("参数错误!");
        }
        $token = $this->adminUserRepo->login($username, $password, $googleCode);
        if ($token) {
            $this->systemRepository->adminLogs('login',$token['username']);
            $this->sendSuccessResult($token);
        }
        $this->sendErrorResult("登陆失败!");
    }

    /**
     * 登录
     */
    public function exitAction()
    {
        $this->adminUserRepo->logout();
        $this->redirect('/login/');
    }
}