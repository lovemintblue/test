<?php

declare(strict_types=1);

namespace App\Core\Repositories;

use App\Constants\StatusCode;
use App\Core\BaseComponent;
use App\Core\Services\RequestService;
use App\Exception\BusinessException;
use Phalcon\Di\FactoryDefault;

/**
 * Class BaseRepository
 * @package App\Core\Repositories
 * @property  RequestService $requestService
 */
class BaseRepository extends BaseComponent
{
    /**
     * @Inject
     * @var FactoryDefault
     */
    protected $container;

    public function getRequest($data,$key, $type = 'string', $defaultValue = null)
    {
        if(empty($data)){
            $data =$_REQUEST;
        }
        return $this->getRequestService()->getRequest($data,$key,$type,$defaultValue);
    }


    /**
     * __get
     * 隐式注入服务类
     * @param $key
     * @return mixed
     * @throws BusinessException
     */
    public function __get($key)
    {
        if ($key == 'app' || $key =='container') {
            return container();
        } elseif (substr($key, -7) == 'Service') {
            return $this->getServiceInstance($key);
        } else {
            throw new BusinessException( StatusCode::SERVER_ERROR,"服务{$key}不存在，书写错误！");
        }
    }

    /**
     * getServiceInstance
     * 获取服务类实例
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
            throw new BusinessException(StatusCode::SERVER_ERROR,"服务{$key}不存在，文件不存在！");
        }
    }
}