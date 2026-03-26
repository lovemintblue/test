<?php

declare(strict_types=1);

namespace App\Repositories\Backend;

use App\Core\Repositories\BaseRepository;
use App\Services\AdminUserService;
use App\Services\ConfigService;

/**
 * 系统用户管理
 * @package App\Repositories\Backend
 *
 * @property  ConfigService $configService
 */
class ConfigRepository extends BaseRepository
{

    /**
     * 获取配置列表
     * @param $groupKey
     * @return array
     */
    public function getList($groupKey)
    {
        $result = array();
        $items = $this->configService->getList($groupKey);
        foreach ($items as $item) {
            $result[] = array(
                'code' => $item['code'],
                'name' => $item['name'],
                'type' => $item['type'],
                'value' => $item['value'],
                'values' => strval($item['values']),
                'help' => strval($item['help']),
            );
        }
        return $result;
    }

    /**
     * 获取配置
     * @return array
     */
    public function getAll()
    {
        return $this->configService->getAll();
    }

    /**
     * 获取指定配置
     * @param $code
     * @return mixed|null
     */
    public function getConfig($code)
    {
        return $this->configService->getConfig($code);
    }

    /**
     * 保存配置
     * @param $code
     * @param $value
     * @return bool
     */
    public function save($code,$value)
    {
        return $this->configService->save($code,$value);
    }

}