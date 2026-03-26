<?php

declare(strict_types=1);

namespace App\Controller\Backend;

use App\Controller\BaseBackendController;
use App\Repositories\Backend\AdminLogRepository;

/**
 * 日志管理管理
 *
 * @package App\Controller\Backend
 *
 * @property  AdminLogRepository $adminLogRepository
 */
class AdminLogsController extends BaseBackendController
{
    /**
     * 初始化
     */
    public function initialize()
    {
        parent::initialize();
        $this->checkPermission('/adminLogs');
    }

    /**
     * 管理员日志
     */
    public function listAction()
    {
        if($this->isPost()) {
            $result = $this->adminLogRepository->getLogList($_REQUEST);
            $this->sendSuccessResult($result);
        }
    }

    /**
     *删除30天以前的日志
     */
    public function delAction()
    {
        return $this->adminLogRepository->delLogs();
    }
}