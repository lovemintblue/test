<?php


namespace App\Tasks;


use App\Core\BaseTask;
use App\Jobs\Jms\JmsDayJob;
use App\Jobs\Jms\JmsUserJob;
use App\Jobs\Jms\JmsWithdrawJob;
use App\Services\JobService;

/**
 * 集团数据上报
 * Class JmsTask
 * @property JobService $jobService
 * @package App\Tasks
 */
class JmsTask extends BaseTask
{
    /**
     * 用户上报
     */
    public function userAction()
    {
        $startAt=time()-3600*1;
        $this->jobService->create(new JmsUserJob($startAt),'sync');
    }

    /**
     * 日活上报
     */
    public function dayAction()
    {
        $startAt=time()-3600*1;
        $this->jobService->create(new JmsDayJob($startAt),'sync');
    }

}