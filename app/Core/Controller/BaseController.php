<?php

declare(strict_types=1);

namespace App\Core\Controller;

use App\Constants\StatusCode;
use App\Core\Services\RequestService;
use Phalcon\Mvc\Controller;

abstract class BaseController extends Controller
{

    /**
     * 发送正确请求结果
     * @param string $data
     */
    protected function sendSuccessResult($data = null)
    {
        $result = array(
            'status' => 'y',
            'data' => $data
        );
        $this->sendJson($result);
    }

    /**
     * 发送错误请求结果
     * @param string $error
     * @param integer $errorCode
     */
    protected function sendErrorResult($error = '', $errorCode = 2008)
    {
        $result = array(
            'status' => 'n',
            'error' => $error,
            'errorCode' => $errorCode
        );
        $this->sendJson($result);
    }

    /**
     * 判读是否post请求
     * @return bool
     */
    protected function isPost()
    {
        if ($this->request->isPost()) {
            return true;
        }
        return false;
    }

    /**
     * 判读是否get请求
     * @return bool
     */
    protected function isGet()
    {
        if ($this->request->isGet()) {
            return true;
        }
        return false;
    }

    /**
     * 发送json
     * @param $data
     */
    protected function sendJson($data)
    {
        if (is_array($data) || is_object($data)) {
            $data = json_encode($data);
        }
        ob_clean();
        header('Content-Type:application/json; charset=utf-8');
        header('Content-Length:' . strlen(strval($data)));
        echo $data;
        exit();
    }


    /**
     * 获取请求
     * @param  $key
     * @param string $type
     * @param  $defaultValue
     * @return string
     */
    public function getRequest($key, $type = 'string', $defaultValue = null)
    {
        $requestService = $this->container->get(RequestService::class);
        /* @var RequestService $requestService */
        return $requestService->getRequest($_REQUEST, $key, $type, $defaultValue);
    }

    /**
     * __get
     * 隐式注入仓库类
     * @param $key
     * @return mixed
     */
    public function __get($key)
    {
        if ($key == 'app') {
            return container();
        } elseif ($key == 'view') {
            return container()->get('view');
        } elseif ($key=='request'){
            return container()->get('request');
        } elseif (substr($key, -7) == 'Service') {
            return $this->getServiceInstance($key);
        } else {
            $suffix = strstr($key, 'Repo');
            if ($suffix && ($suffix == 'Repo' || $suffix == 'Repository')) {
                $repoName = $suffix == 'Repo' ? $key . 'sitory' : $key;
                return $this->getRepositoriesInstance($repoName);
            } else {
                throw new \RuntimeException("仓库{$key}不存在，书写错误！", StatusCode::SERVER_ERROR);
            }
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


    /**
     * getRepositoriesInstance
     * 获取仓库类实例
     * @param $key
     * @return mixed
     */
    public function getRepositoriesInstance($key)
    {
        $key = ucfirst($key);
        $module = $this->getModuleName();
        if (!empty($module)) {
            $module = "{$module}";
        } else {
            $module = "";
        }
        if ($module) {
            $filename = BASE_PATH . "/app/Repositories/{$module}/{$key}.php";
            $className = "App\\Repositories\\{$module}\\{$key}";
        } else {
            $filename = BASE_PATH . "/app/Repositories/{$key}.php";
            $className = "App\\Repositories\\{$key}";
        }

        if (file_exists($filename)) {
            return getAutoClass($className);
        } else {
            throw new \RuntimeException("仓库{$key}不存在，文件不存在！", StatusCode::SERVER_ERROR);
        }
    }

    /**
     * getModuleName
     * 获取所属模块
     * @return string
     */
    private function getModuleName()
    {
        $className = get_called_class();
        $name = substr($className, 15);
        $space = explode('\\', $name);
        if (count($space) > 1) {
            return $space[0];
        } else {
            return '';
        }
    }

    /**
     * 重定向
     * @param string $url
     * @param array $params
     * @param string $module 控制器组
     */
    protected function redirect($url, $params = array(), $module = '')
    {
        if (strpos($url, 'http') !== false) {
            if (!empty($params)) {
                if (strpos($url, '?') !== false) {
                    $url .= '&' . http_build_query($params);
                } else {
                    $url .= '?' . http_build_query($params);
                }
            }
        } else {
            $url = createUrl($url, $params, $module);
        }
        ob_clean();
        header('Location:' . $url);
        exit;
    }

    /**
     * 发送404错误信息
     */
    protected function send404()
    {
        ob_clean();
        header("http/1.1 404 not found");
        header("status: 404 not found");
        exit();
    }

    protected function destroy()
    {
        try{
            container()->getShared('redis')->close();
        }catch (\Exception $exception){

        }

    }
}
