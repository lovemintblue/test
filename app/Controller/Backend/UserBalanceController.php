<?php

declare(strict_types=1);

namespace App\Controller\Backend;

use App\Constants\CommonValues;
use App\Controller\BaseBackendController;
use App\Exception\BusinessException;
use App\Repositories\Backend\UserBalanceRepository;

/**
 * 用户余额
 *
 * @package App\Controller\Backend
 *
 * @property  UserBalanceRepository $userBalanceRepository
 */
class UserBalanceController extends BaseBackendController
{
    /**
     * 初始化
     */
    public function initialize()
    {
        parent::initialize();
        $this->checkPermission('/userBalance');
    }

    /**
     * 列表
     */
    public function listAction()
    {
        if($this->isPost()) {
            $result = $this->userBalanceRepository->getList($_REQUEST);
            $this->sendSuccessResult($result);
        }
        $this->view->setVar('deviceArr',CommonValues::getDeviceTypes());
        $this->view->setVar('balanceArr',CommonValues::getBalanceTypes());
        $this->view->setVar('statusArr',CommonValues::getBalanceStatus());
    }

}