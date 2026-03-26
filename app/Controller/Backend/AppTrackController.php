<?php

declare(strict_types=1);

namespace App\Controller\Backend;

use App\Constants\CommonValues;
use App\Controller\BaseBackendController;
use App\Exception\BusinessException;
use App\Repositories\Backend\AdvAppRepository;
use App\Repositories\Backend\AppTrackRepository;

/**
 * 应用数据跟踪
 *
 * @package App\Controller\Backend
 *
 * @property  AppTrackRepository $appTrackRepo
 */
class AppTrackController extends BaseBackendController
{
    /**
     * 初始化
     */
    public function initialize()
    {
        parent::initialize();
        $this->checkPermission('/appTrack');
    }

    /**
     * 列表
     */
    public function listAction()
    {
        if ($this->isPost()) {
            $result = $this->appTrackRepo->getList($_REQUEST);
            $this->sendSuccessResult($result);
        }
        $this->view->setVar('typeArr',CommonValues::getAppTrackTypes());
    }

}