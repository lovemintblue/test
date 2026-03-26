<?php

declare(strict_types=1);

namespace App\Repositories\Backend;


use App\Core\Repositories\BaseRepository;
use App\Services\AgentSystemService;
use App\Services\CommonService;
use App\Services\ConfigService;

/**
 * 系统相关
 * @package App\Repositories\Backend
 * @property  ConfigService $configService
 * @property  CommonService $commonService
 * @property  AgentSystemService $agentSystemService
 */
class SystemRepository extends BaseRepository
{
    /**
     * 获取系统的配置
     * @return array
     */
    public function config()
    {
        $configs = $this->configService->getAll();
        $result =  array(
            'mrs_url' => $configs['media_url'],
            'system_name' => $configs['system_name'],
            'constants' => array()
        );
        $constantMethods = get_class_methods('\App\Constants\CommonValues');
        foreach ($constantMethods as $constantMethod)
        {
            $keyName = substr($constantMethod,3);
            $keyName = lcfirst($keyName);
            $result['constants'][$keyName] = call_user_func('\App\Constants\CommonValues::'.$constantMethod);
        }
        return  $result;
    }

    /**
     * 清理缓存
     */
    public function clearCache()
    {
        $this->commonService->clearCache();
//        $this->commonService->sendClearCacheEvent();
    }

    /**
     * 签到
     */
    public function adminLogs($act,$username='')
    {
        $this->agentSystemService->adminLogs($act,$username);
    }
}