<?php


namespace App\Controller\Api;


use App\Core\Controller\BaseController;
use App\Repositories\Api\SystemRepository;

/**
 * Class ServerController
 * @property SystemRepository $systemRepository
 * @package App\Controller\Api
 */
class ServerController extends BaseController
{
    /**
     * 系统检测
     */
    public function checkAction()
    {
        $result = $this->systemRepository->getServerStatus();
        $this->sendJson($result);
    }
}