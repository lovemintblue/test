<?php

declare(strict_types=1);

namespace App\Core;

use App\Constants\StatusCode;

class BaseTask
{
    /**
     * __get
     * 隐式注入仓库类
     * @param $key
     * @return mixed
     */
    public function __get($key)
    {
        if (substr($key, -7) == 'Service') {
            return $this->getServiceInstance($key);
        }
    }


    /**
     * getServiceInstance
     * 获取服务类实例
     * @param $key
     * @return mixed
     */
    public function getServiceInstance($key)
    {
        $key = ucfirst($key);
        $fileName = BASE_PATH . "/app/Services/{$key}.php";
        $className = "App\\Services\\{$key}";

        if (file_exists($fileName)) {
            return getAutoClass($className);
        } else {
            throw new \RuntimeException("服务{$key}不存在，文件不存在！", StatusCode::SERVER_ERROR);
        }
    }
}