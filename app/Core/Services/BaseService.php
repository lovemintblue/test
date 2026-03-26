<?php

declare(strict_types=1);

namespace App\Core\Services;

use App\Constants\StatusCode;
use App\Core\BaseComponent;
use App\Exception\BusinessException;
use Phalcon\Di\FactoryDefault;

/**
 * Class BaseService
 * @package App\Core\Services
 * @property FactoryDefault $container
 */
class BaseService extends BaseComponent
{
    /**
     * __get
     * 隐式注入服务类
     * @param $key
     * @return mixed
     * @throws BusinessException
     */
    public function __get($key)
    {
        if ($key == 'app' || $key == 'container') {
            return container();
        } elseif (substr($key, -5) == 'Model') {
            return $this->getModelInstance($key);
        } elseif (substr($key, -7) == 'Service') {
            return $this->getServiceInstance($key);
        } else {
            throw new BusinessException(StatusCode::SERVER_ERROR, "服务/模型{$key}不存在，书写错误！");
        }
    }


    /**
     * getModelInstance
     * 获取数据模型类实例
     * @param $key
     * @return mixed
     * @throws BusinessException
     */
    public function getModelInstance($key)
    {
        $key = ucfirst($key);
        $fileName = BASE_PATH . "/app/Models/{$key}.php";
        $className = "App\\Models\\{$key}";

        if (file_exists($fileName)) {
            return getAutoClass($className);
        } else {
            throw new BusinessException(StatusCode::SERVER_ERROR, "服务/模型{$key}不存在，文件不存在！");
        }
    }

    /**
     * getServiceInstance
     * 获取服务类实例
     * User：YM
     * Date：2019/11/21
     * Time：上午10:30
     * @param $key
     * @return mixed
     * @throws BusinessException
     */
    public function getServiceInstance($key)
    {
        $key = ucfirst($key);
        $fileName = BASE_PATH . "/app/Services/{$key}.php";
        $className = "App\\Services\\{$key}";

        if (file_exists($fileName)) {
            return getAutoClass($className);
        } else {
            throw new BusinessException(StatusCode::SERVER_ERROR, "服务/模型{$key}不存在，文件不存在！");
        }
    }

}