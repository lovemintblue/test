<?php

declare(strict_types=1);

use Phalcon\Di\FactoryDefault;

/**
 * 容器实例
 */
if (!function_exists('container')) {
    /**
     * 获取当前容器
     * @return \Phalcon\Di\DiInterface|null
     */
    function container()
    {
        return FactoryDefault::getDefault();
    }
}

if (!function_exists('appErrorHandler')) {
    /**错误日志
     * @param int $number
     * @param string $message
     * @param int $file
     * @param int $line
     * @return array
     */
    function appErrorHandler($number = 0, $message = '', $file = 0, $line = 0)
    {
        if ($number && $file) {
            $info = array();
            $info['type'] = $number;
            $info['message'] = $message;
            $info['file'] = $file;
            $info['line'] = $line;
        } else {
            $info = error_get_last();
        }
        if ($info) {
            if ($info['type'] == E_NOTICE || $info['type'] == E_WARNING || $info['type'] == E_USER_NOTICE) {
                return array();
            }
            $dir = RUNTIME_PATH . '/logs';
            $logMessage = 'Date:' . date('Y-m-d H:i:s') . PHP_EOL;
            $logMessage .= 'Type:' . $info['type'] . PHP_EOL;
            $logMessage .= 'Message:' . $info['message'] . PHP_EOL;
            $logMessage .= 'File:' . $info['file'] . PHP_EOL;
            $logMessage .= 'Line:' . $info['line'] . PHP_EOL . PHP_EOL;
            file_put_contents($dir . '/' . date('Y-m-d') . '.log', $logMessage, FILE_APPEND);
        }
        return array();
    }
}

if (!function_exists('handleTreeList')) {
    /**
     * handleTreeList
     * 建立数组树结构列表
     * @param array $arr 数组
     * @param int $pid 父级id
     * @param int $depth 增加深度标识
     * @param string $p_sub 父级别名
     * @param string $d_sub 深度别名
     * @param string $c_sub 子集别名
     * @return array
     */
    function handleTreeList($arr, $pid, $depth = 0, $p_sub = 'parent_id', $c_sub = 'children', $d_sub = 'depth')
    {
        $returnArray = [];
        if (is_array($arr) && $arr) {
            foreach ($arr as $k => $v) {
                if ($v[$p_sub] == $pid) {
                    $v[$d_sub] = $depth;
                    $tempInfo = $v;
                    unset($arr[$k]); // 减少数组长度，提高递归的效率，否则数组很大时肯定会变慢
                    $temp = handleTreeList($arr, $v['id'], $depth + 1, $p_sub, $c_sub, $d_sub);
                    if ($temp) {
                        $tempInfo[$c_sub] = $temp;
                    }
                    $returnArray[] = $tempInfo;
                }
            }
        }
        return $returnArray;
    }
}

if (!function_exists('formatBytes')) {
    /**
     * formatBytes
     * 字节->兆转换
     * 字节格式化
     * @param $bytes
     * @return string
     */
    function formatBytes($bytes)
    {
        if ($bytes >= 1073741824) {
            $bytes = round($bytes / 1073741824 * 100) / 100 . 'GB';
        } elseif ($bytes >= 1048576) {
            $bytes = round($bytes / 1048576 * 100) / 100 . 'MB';
        } elseif ($bytes >= 1024) {
            $bytes = round($bytes / 1024 * 100) / 100 . 'KB';
        } else {
            $bytes = $bytes . 'Bytes';
        }
        return $bytes;
    }
}

if (!function_exists('durationFormat')) {
    /**
     * durationFormat
     * 时间格式化，格式化秒
     * @param $number
     * @return string
     */
    function durationFormat($number)
    {
        if (!$number) {
            return '0分钟';
        }
        $newTime = '';
        if (floor($number / 3600) > 0) {
            $newTime .= floor($number / 3600) . '小时';
            $number = $number % 3600;
        }
        if ($number / 60 > 0) {
            $newTime .= floor($number / 60) . '分钟';
            $number = $number % 60;
        }
        if ($number < 60) {
            $newTime .= $number . '秒';
        }
        return $newTime;
    }
}

if (!function_exists('dateFormat')) {

    /**
     * 时间格式化
     * @param $time
     * @param string $format
     * @return false|string
     */
    function dateFormat($time, $format = "Y-m-d H:i:s")
    {
        if (empty($time)) {
            return "";
        }
        return date($format, intval($time));
    }

}

if (!function_exists('getFiles')) {
    /**
     * 读取指定目录下所有文件
     * @param $path
     * @param $ext
     * @return array
     */
    function getFiles($path, $ext = null)
    {
        $files = array();
        if (!file_exists($path)) {
            return $files;
        }
        $dirHandel = opendir($path);
        while (($file = readdir($dirHandel)) !== false) {
            if ($file == '..' || $file == '.') {
                continue;
            }
            if ($ext && strpos($file, $ext) === false) {
                continue;
            }
            $fullPath = $path . '/' . $file;
            if (is_dir($fullPath)) {
                $childFiles = getFiles($fullPath);
                $files = array_merge($files, $childFiles);
            } else {
                $files[] = $fullPath;
            }
        }
        closedir($dirHandel);
        return $files;
    }
}

if (!function_exists('delDir')) {
    /**
     * 删除指定的目录
     * @param $path
     */
    function delDir($path)
    {
        $path = rtrim($path, '/');
        if (!file_exists($path) || !is_dir($path)) {
            return;
        }
        $handle = opendir($path);
        while (($file = readdir($handle)) !== false) {
            if ($file == '..' || $file == '.') {
                continue;
            }
            $fullPath = $path . '/' . $file;
            if (is_dir($fullPath)) {
                delDir($fullPath);
                rmdir($fullPath);
            } else {
                unlink($fullPath);
            }
        }
        closedir($handle);
        rmdir($path);
    }
}

if (!function_exists('getClientIps')) {
    /**
     * 获取ip
     * @return string
     */
    function getClientIps()
    {
        if (isset($HTTP_SERVER_VARS["HTTP_X_FORWARDED_FOR"]) && !empty($HTTP_SERVER_VARS["HTTP_X_FORWARDED_FOR"])) {
            $ip = $HTTP_SERVER_VARS["HTTP_X_FORWARDED_FOR"];
        } elseif (isset($HTTP_SERVER_VARS["HTTP_CLIENT_IP"]) && !empty($HTTP_SERVER_VARS["HTTP_CLIENT_IP"])) {
            $ip = $HTTP_SERVER_VARS["HTTP_CLIENT_IP"];
        } elseif (isset($HTTP_SERVER_VARS["REMOTE_ADDR"]) && !empty($HTTP_SERVER_VARS["REMOTE_ADDR"])) {
            $ip = $HTTP_SERVER_VARS["REMOTE_ADDR"];
        } elseif (getenv("HTTP_X_FORWARDED_FOR")) {
            $ip = getenv("HTTP_X_FORWARDED_FOR");
        } elseif (getenv("HTTP_CLIENT_IP")) {
            $ip = getenv("HTTP_CLIENT_IP");
        } elseif (getenv("REMOTE_ADDR")) {
            $ip = getenv("REMOTE_ADDR");
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        } else {
            $ip = "Unknown";
        }
        return $ip;
    }
}

if (!function_exists('getClientIp')) {
    /**
     * 代理的时候是两个ip
     * @return bool|string
     */
    function getClientIp()
    {
        $ip = getClientIps();
        if (strpos($ip, ',') > 0) {
            return substr($ip, 0, strpos($ip, ','));
        }
        return $ip;
    }
}

if (!function_exists('getHeaderLine')) {
    /**
     * 获取header值
     * @param $key
     * @return mixed|null
     */
    function getHeaderLine($key)
    {
        $key = str_replace('-', '_', $key);
        $key = 'HTTP_' . strtoupper($key);
        if (isset($_SERVER[$key])) {
            return $_SERVER[$key];
        }
        return null;
    }
}


if (!function_exists('setCache')) {

    /**
     * 保存缓存
     * @param $key
     * @param $value
     * @param null $time
     */
    function setCache($key, $value, $time = null)
    {
        container()->get('cache')->set($key, $value, $time);
    }
}

if (!function_exists('getCache')) {
    /**
     * 获取缓存
     * @param $key
     * @return mixed
     */
    function getCache($key)
    {
        return container()->get('cache')->get($key);
    }
}

if (!function_exists('delCache')) {
    /**
     * 获取缓存
     * @param $key
     * @return mixed
     */
    function delCache($key)
    {
        return container()->get('cache')->delete($key);
    }
}

if (!function_exists('getSession')){
    /**
     * 获取session
     * @return \Phalcon\Session\Manager
     */
    function getSession()
    {
        return container()->get('session');
    }
}

if (!function_exists('getAutoClass')) {
    /**
     * 容器
     * @param $class
     * @return mixed
     */
    function getAutoClass($class)
    {
        if (!container()->has($class)) {
            container()->setShared($class, function () use ($class) {
                return new $class();
            });
        }
        return container()->getShared($class);
    }
}
if (!function_exists('value')) {
    /**
     * Return the default value of the given value.
     *
     * @param mixed $value
     * @return mixed
     */
    function value($value)
    {
        return $value instanceof Closure ? $value() : $value;
    }
}

if (!function_exists('formatNum')) {
    function formatNum($num, $p = 0)
    {
        if ($p == 0) {
            return sprintf("%u", $num / 100);
        }
        return sprintf("%." . $p . "f", $num / 100);
    }
}

if (!function_exists('getConfigs')) {
    /**
     * 获取配置
     * @return mixed
     */
    function getConfigs()
    {
        return getAutoClass("App\Services\ConfigService")->getAll();
    }
}


if (!function_exists('createUrl')) {

    /**
     * 创建url
     * @param $url
     * @param array $data
     * @param string $module
     * @return string
     */
    function createUrl($url, $data = array(), $module = null)
    {
        $config = container()->get('config');
        if (empty($module)) {
            $controllerBean = container()->get('controller');
            $module = $controllerBean->getModule();
        }
        $moduleUrl = $config->modules->{$module};
        $url = $moduleUrl . $url;
        if ($data) {
            $url .= '?' . http_build_query($data);
        }
        return $url;
    }
}

if (!function_exists('createStaticUrl')) {
    /**
     * 获取静态资源
     * @param $url
     * @return string
     */
    function createStaticUrl($url)
    {
        $configs = getConfigs();
        if (empty($url)) {
            return "";
        }
        if (strpos($url, '?') > 0) {
            $url .= '&_v=' . $configs['static_version'];
        } else {
            $url .= '?_v=' . $configs['static_version'];
        }
        return $url;
    }
}

if (!function_exists('isHttps')) {
    /**
     * 判断是否https
     * @return bool
     */
    function isHttps()
    {
        if(isset($_SERVER['HTTP_CF_VISITOR']) && strpos($_SERVER['HTTP_CF_VISITOR'],'https')!==false){
            return true;
        } elseif(isset($_SERVER['HTTP_X_FORWARDED_PROTO'])&& $_SERVER['HTTP_X_FORWARDED_PROTO']=='https'){
            return true;
        }elseif (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT']==443){
            return true;
        }
        return false;
    }
}

if (!function_exists('getFullDomain')) {
    /**
     * 获取对应域名的
     * @return string
     */
    function getFullDomain()
    {
        $url = '';
        $domain = $_SERVER['SERVER_NAME']?$_SERVER['SERVER_NAME']:$_SERVER['HTTP_HOST'];
        if(empty($domain)){
            return $url;
        }
        return 'https://'.$domain;
    }
}