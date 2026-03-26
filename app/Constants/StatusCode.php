<?php

declare(strict_types=1);

namespace App\Constants;

class StatusCode
{
    const SERVER_ERROR = 5001;
    const DATA_ERROR = 2001;
    const NO_LOGIN_ERROR = 2002;
    const NO_PERMISSION_ERROR = 4003;
    const PARAMETER_ERROR = 4002;
    const DB_ERROR = 5003;
    const ERRORS = array(
        StatusCode::NO_PERMISSION_ERROR => '无权操作',
        StatusCode::SERVER_ERROR => '网络异常',
        StatusCode::NO_LOGIN_ERROR => '请登录后操作',
        StatusCode::DATA_ERROR => '请求数据错误',
        StatusCode::PARAMETER_ERROR=>'参数错误',
        StatusCode::DB_ERROR=>'数据执行错误!'
    );
}

