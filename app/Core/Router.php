<?php

namespace App\Core;

use App\Utils\LogUtil;

class Router extends \Phalcon\Mvc\Router
{
    /**
     * 初始化url
     * @param array $modules
     * @return $this
     */
    public function initUrl($modules = array())
    {
        foreach ($modules as $groupName => $url) {
            $namespace = '\App\Controller\\' . $groupName ;
            $this->add($url . '/:controller/:action/:params', [
                'namespace' => $namespace,
                'controller' => 1,
                'action' => 2,
                'params' => 3
            ]);
            $this->add($url . '/:controller/:action/', [
                'namespace' => $namespace,
                'controller' => 1,
                'action' => 2
            ]);
            $this->add($url . '/:controller', [
                'namespace' => $namespace,
                'controller' => 1,
                'action' => 'index'
            ]);
            $this->add($url . '/:controller/', [
                'namespace' => $namespace,
                'controller' => 1,
                'action' => 'index'
            ]);
            $this->add($url . '/', [
                'namespace' => $namespace,
                'controller' => 'index',
                'action' => 'index'
            ]);
            $this->add($url, [
                'namespace' => $namespace,
                'controller' => 'index',
                'action' => 'index'
            ]);
        }
        return $this;
    }
}