<?php

declare(strict_types=1);

namespace App\Controller\Backend;

use App\Constants\CommonValues;
use App\Controller\BaseBackendController;
use App\Exception\BusinessException;
use App\Repositories\Backend\UserActRepository;

/**
 * 用户行为
 *
 * @package App\Controller\Backend
 *
 * @property  UserActRepository $userActRepository
 */
class UserActController extends BaseBackendController
{
    /**
     * 初始化
     */
    public function initialize()
    {
        parent::initialize();
        $this->checkPermission('/userAct');
    }

    /**
     * 列表
     */
    public function listAction()
    {
        if ($this->isPost()) {
            $result = $this->userActRepository->getList($_REQUEST);
            $this->sendSuccessResult($result);
        }
        $this->view->setVar('userActArr',CommonValues::getUserActs());
        $this->view->setVar('isUpArr',CommonValues::getIsUp());
    }

}