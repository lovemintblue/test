<?php

declare(strict_types=1);

namespace App;

use App\Core\Application;
use App\Exception\Handler\AppExceptionHandler;
use Phalcon\Cli\Console;
use Phalcon\Cli\Dispatcher;
use Phalcon\Di\FactoryDefault\Cli as CliDI;
use Exception;

class CliApplication extends Application
{
    public function run()
    {
        set_time_limit(0);
        @ini_set('memory_limit', '512M');
        $this->container = new CliDI();
        $this->initAll();
        $dispatcher = new Dispatcher();
        $dispatcher->setDefaultNamespace('App\Tasks');
        $this->container->setShared('dispatcher', $dispatcher);
        $console = new Console($this->container);
        $arguments = [];
        global $argv;
        foreach ($argv as $k => $arg) {
            if ($k === 1) {
                $arguments['task'] = $arg;
            } elseif ($k === 2) {
                $arguments['action'] = $arg;
            } elseif ($k >= 3) {
                $arguments['params'][] = $arg;
            }
        }
        try {
            $console->handle($arguments);
        } catch (Exception $exception) {
            new AppExceptionHandler($exception);
        }
    }
}