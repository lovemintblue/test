<?php

// 重新命名文件分隔符，建议路径后面加上分隔符
define('DS', '/');
// 项目根目录
define('BASE_PATH', str_replace('\\',DS,realpath(dirname(__DIR__))).DS);
// 应用根目录
define('APP_PATH', BASE_PATH.'app');
// 网站根目录
define('WEB_PATH', BASE_PATH.'public');
// 运行时根目录
define('RUNTIME_PATH', BASE_PATH.'runtime');
// 检查版本，运行成功后注释判断
version_compare(PHP_VERSION, '7.0.0', '>') || exit('Require PHP > 7.0.0 !');
// 检查是否安装phalcon扩展，运行成功后注释判断
extension_loaded('phalcon') || exit('Please open the Phalcon extension !');
