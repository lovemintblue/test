<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Services\BaseService;
use App\Utils\CommonUtil;
use App\Utils\LogUtil;

class TokenService extends BaseService
{

    /**
     * 根据用户删除token
     * @param $userId
     * @param string $namespace
     * @return bool
     */
    public function deleteByUserId($userId, $namespace = 'user')
    {
        $this->getRedis()->delete($this->getKeyName($userId, $namespace));
        return true;
    }


    /**
     * 返回token
     * @param $userId
     * @param string $namespace
     * @return mixed|null
     */
    public function get($userId, $namespace = 'user')
    {
        $token = $this->getRedis()->get($this->getKeyName($userId, $namespace));
        if (empty($token)) {
            return null;
        }
        return unserialize($token);
    }

    /**
     * 保存token
     * @param $userId
     * @param $username
     * @param int $expired
     * @param string $namespace
     * @param string $ext
     * @return array
     */
    public function set($userId, $username, $expired = 7200, $namespace = 'user', $ext = null)
    {
        $token = array(
            'token' => CommonUtil::getId(),
            'user_id' => strval($userId),
            'username' => $username,
            'expired_at' => strval($expired),
            'ip'=>getClientIp(),
            'ext' => $ext
        );
        $this->getRedis()->set($this->getKeyName($userId, $namespace), serialize($token), $expired);
        return $token;
    }

    /**
     * 获取key
     * @param $key
     * @param $namespace
     * @return string
     */
    protected function getKeyName($key, $namespace)
    {
        return $namespace . '_' . $key;
    }
}