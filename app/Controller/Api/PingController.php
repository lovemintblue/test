<?php

declare(strict_types=1);

namespace App\Controller\Api;


use App\Controller\BaseApiController;

class PingController extends BaseApiController
{
    /**
     * ping检测
     */
    public function indexAction()
    {
        $this->sendSuccessResult();
    }
}