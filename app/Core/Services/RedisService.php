<?php

declare(strict_types=1);

namespace App\Core\Services;

use \Redis;

/**
 * redis操作类
 *
 */
class RedisService
{
    /**
     * @var Redis
     */
    private $redis = null;
    protected $dbIndex = 0;
    protected $host;
    protected $port;
    protected $prefix = 'vns_';

    public function __construct($config)
    {
        $this->port = $config['port'] ? $config['port'] : 6379;
        $this->host = $config['host'];
        $this->prefix = $config['prefix'];
        $this->dbIndex = isset($config['db']) ? $config['db'] : 0;
        $this->redis = new Redis();
        $this->redis->connect($this->host, $this->port);
//        $this->redis->pconnect($this->host, $this->port,0.2,'www');
        $this->redis->select($this->dbIndex);
//        $this->redis->setOption(Redis::OPT_READ_TIMEOUT, 0.5);
//        $this->redis->setOption(Redis::OPT_TCP_KEEPALIVE, 1);
    }

    /**
     * 获取key
     * @param $key
     * @return string
     */
    public function getKey($key)
    {
        return $this->prefix . $key;
    }

    /**
     * 获取redis
     * @return Redis
     */
    public function getRedis()
    {
        return $this->redis;
    }

    /**
     * lPush
     * @param $key
     * @param $data
     * @return bool|int
     */
    public function lPush($key, $data)
    {
        return $this->redis->lPush($this->getKey($key), serialize($data));
    }

    /**
     * rPop
     * @param $key
     * @return bool|mixed
     */
    public function rPop($key)
    {
        $data = $this->redis->rPop($this->getKey($key));
        return empty($data) ? null : unserialize($data);
    }


    /**
     * 删除key
     * @param $key
     * @return int
     */
    public function delete($key)
    {
        return $this->redis->del($this->getKey($key));
    }

    /**
     * 设置过期 单位秒
     * @param $key
     * @param $ttl
     * @return bool
     */
    public function expire($key, $ttl)
    {
        return $this->redis->expire($this->getKey($key), $ttl);
    }

    /**
     * 是否存在
     * @param $key
     * @return bool
     */
    public function exists($key){
        return $this->redis->exists($this->getKey($key));
    }

    /**
     * 设置key
     * @param string $key
     * @param string|mixed $value string if not used serializer
     * @param int|array $timeout [optional] Calling setex() is preferred if you want a timeout.<br>
     * @return bool
     */
    public function set($key, $value, $timeout = null)
    {
        return $this->redis->set($this->getKey($key), $value, $timeout);
    }

    /**
     * 获取值
     * @param $key
     * @return bool|mixed|string
     */
    public function get($key)
    {
        return $this->redis->get($this->getKey($key));
    }

    /**
     * 获取值
     * @param $key
     * @return bool|mixed|string
     */
    public function mget($keys)
    {
        if(empty($keys)){
            return [];
        }
        $prefixKey = [];
        foreach ($keys as $k) {
            $prefixKey[] = $this->getKey($k);
        }
        try {
            $vals = $this->redis->mget($prefixKey);
        } catch (\Throwable $e) {
            return array_fill(0, count($keys), false);
        }

        return $vals ?: array_fill(0, count($keys), false);
    }

    /**
     * 获取过期时间
     * @param $key
     * @return bool|int|Redis
     */
    public function ttl($key)
    {
        return $this->redis->ttl($this->getKey($key));
    }

    /**
     * 自增
     * @param $key
     * @param $value
     * @return int
     */
    public function incrBy($key, $value)
    {
        return $this->redis->incrBy($this->getKey($key), $value);
    }

    public function multi($mode = Redis::MULTI)
    {
        return $this->redis->multi($mode);
    }

    public function exec()
    {
        return $this->redis->exec();
    }

    /**
     * 向集合添加一个或多个成员
     * @param $key
     * @param $value
     * @return bool|int
     */
    public function sAdd($key, $value,...$values)
    {
        return $this->redis->sAdd($this->getKey($key), $value, ...$values);
    }

    /**
     * 集合查询元素是否存在
     * @param $key
     * @param $value
     * @return bool|Redis
     */
    public function sismember($key,$value)
    {
        return $this->redis->sismember($this->getKey($key),$value);
    }

    /**
     * 返回集合中的所有成员
     * @param $key
     * @return array
     */
    public function sMembers($key)
    {
        return $this->redis->sMembers($this->getKey($key));
    }

    /**
     * 随机返回集合指定条数
     * @param $keyName
     * @param int $limit
     * @return array|bool|mixed|string
     */
    public function  sRandMember($keyName,$limit=15)
    {
        return $this->redis->sRandMember($this->getKey($keyName),$limit);
    }

    /**
     * 移除集合中一个或多个成员
     * @param $key
     * @param $value
     * @return int
     */
    public function sRem($key, $value)
    {
        return $this->redis->sRem($this->getKey($key), $value);
    }

    /**
     * 获取集合的成员数
     * @param $key
     * @return int
     */
    public function sCard($key)
    {
        return $this->redis->sCard($this->getKey($key));
    }

    /**
     * 关闭
     */
    public function close()
    {
        try{
            $this->redis->close();
        }catch (\Exception $exception){

        }
    }

    public function pfAdd($key, $value)
    {
        // pfAdd 支持多个元素，确保是数组
        if (!is_array($value)) {
            $value = [$value];
        }
        return $this->redis->pfAdd($this->getKey($key), $value);
    }

    public function pfcount(string $key)
    {
        return $this->redis->pfcount($this->getKey($key));
    }

    public function incr($key)
    {
        return $this->redis->incr($this->getKey($key));
    }

}