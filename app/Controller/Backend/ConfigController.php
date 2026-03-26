<?php

declare(strict_types=1);

namespace App\Controller\Backend;

use App\Constants\CacheKey;
use App\Controller\BaseBackendController;
use App\Repositories\Backend\ConfigRepository;


/**
 * 系统配置
 *
 * @package App\Controller\Backend
 *
 * @property  ConfigRepository $configRepo
 */
class ConfigController extends BaseBackendController
{
    /**
     * 初始化数据
     * @param $group
     */
    protected  function  initData($group)
    {
        $result = $this->configRepo->getList($group);
        $this->view->setVar("items",$result);
        $this->view->render('config', 'info');
    }

    /**
     * 基础配置
     */
    public function baseAction()
    {
        $this->checkPermission('/configBase');
        $this->initData('base');
    }

    /**
     * 高级配置
     */
    public function otherAction()
    {
        $this->checkPermission('/configOther');
        $this->initData('other');
    }

    /**
     * 高级配置
     */
    public function movieAction()
    {
        $this->checkPermission('/config/movie');
        $this->initData('movie');
    }


    /**
     * Apk配置
     */
    public function apkAction()
    {
        $this->checkPermission('/configApk');
        $this->initData('apk');
    }

    /**
     * App配置
     */
    public function appAction()
    {
        $this->checkPermission('/configApp');
        $this->initData('app');
    }

    /**
     * CDN配置
     */
    public function cdnAction()
    {
        $this->checkPermission('/configCdn');
        $this->initData('cdn');
    }
    /**
     * 用户福利任务配置
     */
    public function userTaskAction()
    {
        $this->checkPermission('/config/userTask');
        $this->initData('userTask');
    }

    /**
     * AI配置
     */
    public function aiAction()
    {
        $this->checkPermission('/configAi');
        $this->initData('ai');
    }

    /**
     * Center配置
     */
    public function centerAction()
    {
        $this->checkPermission('/configCenter');
        $this->initData('center');
    }

    /**
     * 保存
     */
    public function saveAction()
    {
        $items = $_POST;
        foreach ($items as $code => $value) {
            if(is_array($value)){
                $value = json_encode(array_values(array_filter($value)),JSON_UNESCAPED_UNICODE);
            }
            $this->configRepo->save($code, $value);
        }
        delCache(CacheKey::SYSTEM_CONFIG);
        return $this->sendSuccessResult();
    }
}