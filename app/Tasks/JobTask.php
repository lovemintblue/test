<?php


namespace App\Tasks;


use App\Core\BaseTask;
use App\Services\JobService;

/**
 * 任务
 * Class JobTask
 * @property JobService $jobService
 * @package App\Tasks
 */
class JobTask extends BaseTask
{
    /**
     * @param string $drive
     */
    public function lowAction($drive='mongodb')
    {
        $runTime = 297;//可执行时间/秒
        $startTime = time();
        while (true) {
            if (time() - $startTime >= $runTime) {
                break;
            }
            $this->jobService->onQueue('low',$drive);
        }
    }

    /**
     * @param string $drive
     */
    public function mediumAction($drive='mongodb')
    {
        $runTime = 297;//可执行时间/秒
        $startTime = time();
        while (true) {
            if (time() - $startTime >= $runTime) {
                break;
            }
            $this->jobService->onQueue('medium',$drive);
        }
    }

    /**
     * @param string $drive
     */
    public function highAction($drive='mongodb')
    {
        $runTime = 297;//可执行时间/秒
        $startTime = time();
        while (true) {
            if (time() - $startTime >= $runTime) {
                break;
            }
            $this->jobService->onQueue('high',$drive);
        }
    }
}