<?php

namespace App\Tasks;

use App\Core\BaseTask;
use App\Jobs\Center\CenterAdvJob;
use App\Jobs\Center\CenterDataJob;
use App\Services\JobService;

/**
 * 中心任务
 * @property JobService $jobService
 */
class CenterTask extends BaseTask
{
    public function advAction($action)
    {
        $this->jobService->create(new CenterAdvJob($action));
    }

    /**
     * 数据
     * @return void
     */
    public function dataAction($action='')
    {
        $this->jobService->create(new CenterDataJob($action));
    }


}
