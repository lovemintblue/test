<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\Cache;
use App\Constants\CacheKey;
use App\Core\Services\BaseService;
use App\Models\ConfigModel;

/**
 * 配置操作
 * @package App\Services
 *
 * @property  ConfigModel $configModel
 */
class ConfigService extends BaseService
{
    protected $configs = array();

    public function getList($groupKey = "")
    {
        $query = array();
        if ($groupKey) {
            $query['group'] = $groupKey;
        }
        return $this->configModel->find($query, array(), array("sort" => -1), 0, 1000);
    }

    /**
     * 保存配置
     * @param $code
     * @param $value
     * @return bool
     */
    public function save($code, $value)
    {
        $this->configModel->update(array("value" => $value), array("code" => $code));
        return true;
    }

    /**
     * 添加数据
     * @param $data
     * @return bool
     */
    public function insert($data)
    {
        $this->configModel->insert($data);
        return true;
    }

    /**
     * 清除缓存
     */
    public function clear()
    {
        delCache(CacheKey::SYSTEM_CONFIG);
    }

    /**
     * 获取所有配置
     * @return array
     */
    public function getAll()
    {
        if (php_sapi_name() != 'cli' && !empty($this->configs)) {
            return $this->configs;
        }
        $this->configs = getCache(CacheKey::SYSTEM_CONFIG);
        if (empty($this->configs)) {
            $this->configs = array();
            $items = $this->configModel->find(array(), array(), array('sort' => -1), 0, 1000);
            foreach ($items as $item) {
                $this->configs[$item['code']] = $item['value'];
            }
            setCache(CacheKey::SYSTEM_CONFIG, $this->configs, 300);
        }
        return $this->configs;
    }

    /**
     * 获取配置
     * @param $code
     * @return mixed|null
     */
    public function getConfig($code)
    {
        $result = $this->getAll();
        return isset($result[$code]) ? $result[$code] : null;
    }

    /**
     * 删除配置
     * @param $code
     * @return bool
     */
    public function delConfig($code)
    {
        $this->configModel->delete(array('code'=>$code));
        return true;
    }
}