<?php

declare(strict_types=1);

namespace App\Controller\Backend;

use App\Controller\BaseBackendController;
use App\Repositories\Backend\LogsRepository;
use App\Repositories\Backend\SmsLogsRepository;


/**
 * 日志管理管理
 *
 * @package App\Controller\Backend
 *
 * @property  SmsLogsRepository $smsLogsRepository
 */
class SmsLogsController extends BaseBackendController
{
    /**
     * 初始化
     */
    public function initialize()
    {
        parent::initialize();
        $this->checkPermission('/smsLogs');
    }


    /**
     * 短信日志
     */
    public function listAction()
    {
        if($this->isPost()) {
            $result = $this->smsLogsRepository->getList($_REQUEST);
            $this->sendSuccessResult($result);
        }
    }

}