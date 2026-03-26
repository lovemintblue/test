<?php

declare(strict_types=1);

namespace App;

use App\Beans\ControllerBean;
use App\Core\Application;
use App\Core\Router;
use App\Core\Services\RequestService;
use App\Exception\BusinessException;
use App\Exception\Handler\AppExceptionHandler;
use Exception;
use Phalcon\Dispatcher\Exception as DispatcherException;
use Phalcon\Http\Response\Cookies;
use Phalcon\Mvc\Dispatcher;
use Phalcon\Di\FactoryDefault;
use Phalcon\Session\Adapter\Redis;
use Phalcon\Session\Adapter\Stream;
use Phalcon\Session\Manager;
use Phalcon\Storage\AdapterFactory;
use Phalcon\Storage\SerializerFactory;

class WebApplication extends Application
{
    /**
     * 初始化视图
     * @return $this
     */
    protected function initView()
    {
        $compiledPath = RUNTIME_PATH.'/compiled/';
        if(!file_exists($compiledPath)){
            mkdir($compiledPath,0777,true);
        }
        $view = new \Phalcon\Mvc\View();
        $view->setDI($this->container);
        $phtml=new \Phalcon\Mvc\View\Engine\Php($view, $this->container);
        $volt = new \Phalcon\Mvc\View\Engine\Volt($view, $this->container);
        $volt->setOptions(
            [
                'always' => true,
                'extension' => '.volt',
                'path'=>$compiledPath
            ]
        );
        $viewEngines = [
            ".phtml" => $phtml,
            ".volt" => $volt,
        ];
        $view->registerEngines($viewEngines);
        $view->setMainView('./layouts/main');
        $view->setPartialsDir(  './partials');
        $this->container->set('view', $view);
        return $this;
    }

    /**
     * 初始化cookie
     * @return $this
     */
    protected  function  initCookie()
    {
        $this->container->set(
            'cookies',
            function () {
                return new  Cookies(false);
            }
        );
        return $this;
    }

    /**
     * @return $this
     */
    protected function initSession()
    {
        $adapter = $this->config->path('session.adapter');
        $options = $this->config->path('session.'.$adapter);
        $prefix  = $options['prefix']?:'mh_';
        $this->container->set('session',function ()use($adapter,$prefix,$options){
            $session = new Manager();
            if($adapter=='Redis'){
                $serializerFactory = new SerializerFactory();
                $factory           = new AdapterFactory($serializerFactory);
                $redis             = new Redis($factory, $options->toArray());
                $session->setAdapter($redis);
            }else{
                $sessionDir = RUNTIME_PATH.'/sessions';
                if(!file_exists($sessionDir)){mkdir($sessionDir,0777,true);}
                $files = new Stream(
                    [
                        'prefix'   => $prefix,
                        'savePath' => $sessionDir,
                    ]
                );
                $session->setAdapter($files);
            }
            $session->start();
            return $session;
        });
        return $this;
    }

    /**
     * 初始化一些web相关服务
     * @return $this
     */
    protected function initWebServices()
    {
        $this->container->set(RequestService::class, function () {
            return new RequestService();
        });
        return $this;
    }

    /**
     * 初始化web分发器
     * @return $this
     */
    protected function initDispatcher()
    {
        $modules = $this->config->modules->toArray();
        $router = new Router();
        $router->initUrl($modules);
        $this->container->set('router', $router);

        $eventsManager = $this->container->getShared('eventsManager');

        //加载前处理的事件
        $eventsManager->attach("dispatch:beforeDispatchLoop", function ($event, $dispatcher) {
            /* @var $dispatcher \Phalcon\Mvc\Dispatcher */
            $controllerClass = ltrim($dispatcher->getControllerClass(), '\\');
            $controllerInfo = explode('\\', $controllerClass);
            container()->set('controller', function () use ($controllerInfo) {
                $controllerBean = new ControllerBean();
                $controllerBean->setController($controllerInfo[3]);
                $controllerBean->setModule($controllerInfo[2]);
                return $controllerBean;
            });
            $view = $dispatcher->getDI()->get('view');
            $viewsDir = APP_PATH . '/Views/' . $controllerInfo[2] . '/default';
            $view->setViewsDir($viewsDir);
        });

        //加载异常处理的事件
        $eventsManager->attach(
            "dispatch:beforeException",
            function ($event, $dispatcher, $exception) {
                switch ($exception->getCode()) {
                    case DispatcherException::EXCEPTION_ACTION_NOT_FOUND:
                        header("HTTP/1.1 404 Not Found");
                        header("Status: 404 Not Found");
                        exit('404-2');
                        break;
                    case DispatcherException::EXCEPTION_CYCLIC_ROUTING:
                        exit('404-3');
                        break;
                    case DispatcherException::EXCEPTION_HANDLER_NOT_FOUND:
                        header("HTTP/1.1 404 Not Found");
                        header("Status: 404 Not Found");
                        exit('404-1');
                        break;
                }
            }
        );

        //加载完成处理的事件
        $eventsManager->attach("dispatch:afterDispatchLoop", function ($event, $dispatcher) {

        });
        $dispatcher = new Dispatcher();
        $dispatcher->setDI($this->container);
        $dispatcher->setDefaultNamespace('Mrs\App\Admin\Controllers');
        $dispatcher->setEventsManager($eventsManager);
        $this->container->set("dispatcher", $dispatcher);
        return $this;
    }

    public function run()
    {
        $this->container = new FactoryDefault();
        $this->initAll()
            ->initWebServices()
            ->initCookie()
            ->initSession()
            ->initView()
            ->initDispatcher();
        $application = new \Phalcon\Mvc\Application($this->container);
        try {
            $response = $application->handle($_SERVER["REQUEST_URI"]);
            $response->send();
        } catch (\Error $e) {
            new AppExceptionHandler(new Exception($e->getMessage().$e->getFile().$e->getTraceAsString(),-2));
        }catch (BusinessException $e){
            new AppExceptionHandler($e);
        }  catch (Exception $e) {
            new AppExceptionHandler($e);
        }
    }
}