<?php

declare(strict_types=1);

namespace App\Tasks;

use App\Core\BaseTask;
use App\Services\WssService;

/**
 * 数据分析平台
 * Class WssTask
 * @package App\Tasks
 * @property WssService $wssService
 */
class WssTask extends BaseTask
{
    /**
     * 获取开始时间
     * @param $startTime
     * @return false|int
     */
    protected function getStartTime($startTime = null)
    {
        return $this->wssService->getStartTime($startTime);
    }

    /**
     * app日志
     * @param null $startTime
     */
    public function appLogsAction($startTime = null)
    {
        $startTime = $this->getStartTime($startTime);
        $this->wssService->doAppLogs($startTime);
    }

    /**
     * app用户
     * @param null $startTime
     */
    public function usersAction($startTime = null)
    {
        $startTime = $this->getStartTime($startTime);
        $this->wssService->doUsers($startTime);
    }

    /**
     * app订单
     * @param null $startTime
     */
    public function userOrderAction($startTime = null)
    {
        $startTime = $this->getStartTime($startTime);
        $this->wssService->doUserOrders($startTime);
    }

    /**
     * 充值订单
     * @param null $startTime
     */
    public function rechargeAction($startTime = null)
    {
        $startTime = $this->getStartTime($startTime);
        $this->wssService->doRecharge($startTime);
    }

    /**
     * 游戏充值订单
     * @param null $startTime
     */
    public function gameRechargeAction($startTime = null)
    {
        $startTime = $this->getStartTime($startTime);
        $this->wssService->doGameRecharge($startTime);
    }

    /**
     * 金币提现
     * @param null $startTime
     */
    public function withdrawAction($startTime = null)
    {
        $startTime = $this->getStartTime($startTime);
        $this->wssService->doWithdraw($startTime);
    }

    /**
     * 游戏提现
     * @param null $startTime
     */
    public function gameWithdrawAction($startTime = null)
    {
        $startTime = $this->getStartTime($startTime);
        $this->wssService->doGameWithdraw($startTime);
    }

    /**
     * 运行事件队列
     */
    public function queueAction()
    {
        $this->wssService->runQueue();
    }
}