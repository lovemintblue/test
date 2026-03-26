<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Mongodb\MongoDbConnection;
use App\Core\Services\RedisService;
use App\Core\Services\RequestService;
use Phalcon\Http\Response\Cookies;
use Phalcon\Mvc\Dispatcher;

class BaseComponent
{
    /**
     * 获取redis操作
     * @return RedisService
     */
    public function getRedis()
    {
        return container()->getShared('redis');
    }

    /**
     * 获取redis操作
     * @return MongoDbConnection
     */
    public function getMongo()
    {
        return container()->get('mongodb_default');
    }

    /**
     * 获取请求
     * @return RequestService
     */
    public function getRequestService()
    {
        return getAutoClass(RequestService::class);
    }

    /**
     * 获取分发器
     * @return Dispatcher
     */
    public function getDispatcher()
    {
        return container()->get('dispatcher');
    }

    /**
     * 获取cookie
     * @return Cookies
     */
    public function getCookie()
    {
        return container()->get('cookies');
    }
}