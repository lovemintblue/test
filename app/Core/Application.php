<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Mongodb\MongoDbConnection;
use Phalcon\Cache;
use Phalcon\Cache\Adapter\Stream;
use Phalcon\Di\DiInterface;
use Phalcon\Config\Adapter\Ini;
use Phalcon\Cache\AdapterFactory;
use Phalcon\Storage\SerializerFactory;
use App\Core\Services\RedisService;

abstract class Application
{
    /**
     * 主容器
     * @var DiInterface
     */
    protected $container;

    protected $config;

    public function __construct()
    {
        $configFile = BASE_PATH . '.env';
        if (!file_exists($configFile)) {
            exit('Please copy .env.example to .env!');
        }
        $this->config = new Ini($configFile);
    }

    protected function initAll()
    {
        $this->initEnvironment()
            ->initCache()
            ->initMongo()
            ->initRedis();
        return $this;
    }

    /**
     * 初始化环境
     * @return $this
     */
    protected function initEnvironment()
    {
        $logPath = RUNTIME_PATH . '/logs';
        if (!file_exists($logPath)) {
            @mkdir($logPath, 0777, true);
        }
        date_default_timezone_set($this->config->app->timezone);
        error_reporting(E_ALL | ~E_NOTICE | ~E_WARNING);
        register_shutdown_function('appErrorHandler') or set_error_handler('appErrorHandler', E_ALL);
        $this->container->set('config', $this->config);
        return $this;
    }

    /**
     * 初始化redis
     * @return $this
     */
    protected function initRedis()
    {
        $configs = $this->config->redis->toArray();
        $this->container->setShared('redis', function () use ($configs) {
            return new RedisService($configs);
        });
        return $this;
    }

    /**
     * 初始化缓存
     * @return $this
     */
    protected function initCache()
    {
        $adapter = $this->config->path('cache.adapter')?:'Files';
        $options = $this->config->path('cache')->toArray();
        if($adapter=='Files'){
            $adapter='stream';
        }
        $this->container->setShared('cache',function ()use($adapter,$options){
            if($adapter=='stream'){
                $cacheDir = RUNTIME_PATH.'/cache';
                if(!file_exists($cacheDir)){mkdir($cacheDir,0777,true);}
                $options['storageDir']=$cacheDir;
            }


            $serializerFactory = new SerializerFactory();
            $adapterFactory = new AdapterFactory($serializerFactory);
            $options['defaultSerializer'] = 'Php';
            return $adapterFactory->newInstance($adapter, $options);
        });
        return $this;
    }


    /**
     * 初始化mongodb链接
     * @return $this
     */
    protected function initMongo()
    {
        foreach ($this->config->toArray() as $key=>$config) {
            if(strpos($key,'database.mongodb') === false) {continue;}
            $name = str_replace('database.mongodb.','',$key);
            $this->container->setShared("mongodb_{$name}", function () use ($config) {
                return new MongoDbConnection($config);
            });
        }
        return $this;
    }


    protected abstract function run();

}