<?php

declare(strict_types=1);

namespace App\Utils;

use \Exception;
use PHP_Parallel_Lint\PhpConsoleColor\ConsoleColor;

class LogUtil
{
    const LEVEL_INFO = 'INFO';

    const LEVEL_ERROR = 'ERROR';

    const LEVEL_DEBUG = 'DEBUG';

    public static function info($data)
    {
        LogUtil::print($data, LogUtil::LEVEL_INFO);
    }

    public static function error($data)
    {
        LogUtil::print($data, LogUtil::LEVEL_ERROR);
    }

    public static function debug($data)
    {
        LogUtil::print($data, LogUtil::LEVEL_DEBUG);
    }

    public static function print($data, $level)
    {
        if ($data instanceof Exception) {
            $data = $data->getMessage().' in '.$data->getFile().' line:'.$data->getLine().' Full info:'.$data->getTraceAsString();
        } elseif (is_array($data) || is_object($data)) {
            $data = var_export($data, true);
        }
        $data = sprintf('[%s] %s %s %s', $level, $data, date('Y-m-d H:i:s'), PHP_EOL);
        if (php_sapi_name() == 'cli') {
            self::write($data, $level);
        } else {
            self::writeToFile($data, $level);
        }
    }

    public static function write($data, $level)
    {
        $consoleColor = new ConsoleColor();
        switch ($level) {
            case LogUtil::LEVEL_INFO:
                $color = "green"; //Green background
                break;
            case LogUtil::LEVEL_ERROR:
                $color = "bg_red"; //Red background
                break;
            case LogUtil::LEVEL_DEBUG:
                $color = "bg_blue"; //Blue background
                break;
            default:
                $color = "bg_green"; //Green background
        }
        try {
            echo $consoleColor->apply($color, $data);
        } catch (Exception $exception) {

        }
    }

    public static function writeToFile($data, $level)
    {
        $logFile = RUNTIME_PATH . '/logs/' . date('Y-m-d') . '.log';
        file_put_contents($logFile, $data, FILE_APPEND);
    }
}