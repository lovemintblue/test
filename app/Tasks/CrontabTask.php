<?php

declare(strict_types=1);

namespace App\Tasks;

use App\Core\BaseTask;
use App\Jobs\Common\AdminLogsJob;
use App\Jobs\Common\AsyncAdvJob;
use App\Jobs\Common\AsyncAiJob;
use App\Jobs\Common\AsyncDomainJob;
use App\Jobs\Common\AsyncAdvAppJob;
use App\Jobs\Common\AutoBuildJob;
use App\Jobs\Common\ChannelApkJob;
use App\Jobs\Common\ClearDataJob;
use App\Jobs\Common\ComicsScoreJob;
use App\Jobs\Common\CommentJob;
use App\Jobs\Common\CommonMrsJob;
use App\Jobs\Common\MovieScoreJob;
use App\Jobs\Common\RandMovieJob;
use App\Jobs\Common\RandPostJob;
use App\Jobs\Common\SendMsgToGroupJob;
use App\Jobs\Common\TagCountJob;
use App\Jobs\Es\CartoonJob;
use App\Jobs\Es\ComicsJob;
use App\Jobs\Es\MovieJob;
use App\Jobs\Es\NovelJob;
use App\Jobs\Es\PostJob;
use App\Jobs\Game\AsyncGameJob;
use App\Jobs\Mrs\CdnJob;
use App\Jobs\Report\ReportAgentJob;
use App\Jobs\Report\ReportAgentV2Job;
use App\Jobs\Report\ReportAgentV3Job;
use App\Jobs\Report\ReportHourJob;
use App\Jobs\Report\ReportMmsJob;
use App\Jobs\Report\ReportMssJob;
use App\Jobs\Report\ReportServerJob;
use App\Services\AppTrackService;
use App\Services\JobService;
use App\Services\PaymentService;
use App\Services\QueueService;
use App\Services\UserActService;
use App\Services\UserBalanceService;

/**
 * Class CrontabTask
 * @property JobService $jobService
 * @property PaymentService $paymentService
 * @property QueueService $queueService
 * @property AppTrackService $appTrackService
 * @property UserActService $userActService
 * @property UserBalanceService $userBalanceService
 * @package App\Tasks
 */
class CrontabTask extends BaseTask
{
    /**
     * 消费队列
     * @throws \App\Exception\BusinessException
     */
    public function queueAction()
    {
        $this->queueService->run();
    }

    /**
     * 支付成功后的处理 ,每分钟执行一次
     */
    public function doPaidAction()
    {
        $this->paymentService->doPaidJob();
    }


    /**
     * 打乱视频排序
     */
    public function randMovieAction()
    {
        $this->jobService->create(new RandMovieJob(),'sync');
    }

    /**
     * 打乱帖子 暂时不用 只是上线前使用一次 避免前端日期都是集中显示
     */
    public function randPostAction()
    {
        $this->jobService->create(new RandPostJob(),'sync');
    }


    /**
     * 同步漫画到es
     */
    public function asyncComicsAction()
    {
        $this->jobService->create(new ComicsJob(),'sync');
    }


    /**
     * 同步视频到es
     */
    public function asyncMovieAction()
    {
        $this->jobService->create(new MovieJob(),'sync');
    }

    /**
     * 同步帖子到es
     */
    public function asyncPostAction()
    {
        $this->jobService->create(new PostJob(),'sync');
    }
    /**
     * 同步小说到es
     */
    public function asyncNovelAction()
    {
        $this->jobService->create(new NovelJob(),'sync');
    }

    /**
     * 同步Ai到公共库进行处理
     */
    public function asyncAiAction()
    {
        $this->jobService->create(new AsyncAiJob(),'sync');
    }

    /**
     * 同步apk列表
     */
    public function asyncApkAction()
    {
        $this->jobService->create(new ChannelApkJob(),'sync');
    }

    /**
     * 自动打包
     */
    public function autoBuildAction()
    {
        $this->jobService->create(new AutoBuildJob(container()->get('config')->path('app.name')),'sync');
    }

    /**
     * 同步域名列表
     */
    public function asyncDomainAction()
    {
        $this->jobService->create(new AsyncDomainJob(),'sync');
    }

    /**
     * 管理员操作日志统计
     */
    public function adminLogsAction()
    {
        $this->jobService->create(new AdminLogsJob(),'sync');
    }

    /**
     * 系统数据发送到项目群
     */
    public function sendMsgToGroupAction()
    {
        $this->jobService->create(new SendMsgToGroupJob(),'sync');
    }

    /**
     * 同步广告应用
     * @return void
     */
    public function asyncAdvAppAction()
    {
        $this->jobService->create(new AsyncAdvAppJob(),'sync');
    }

    /**
     * 推送广告位到广告系统
     * @return void
     */
    public function asyncAdvPosAction()
    {
        $this->jobService->create(new AsyncAdvJob('','advPos'),'sync');
    }

    /**
     * 标签统计
     */
    public function tagCountAction()
    {
        $this->jobService->create(new TagCountJob(),'sync');
    }

    /**
     * 视频评分
     */
    public function scoreMovieAction()
    {
        $this->jobService->create(new MovieScoreJob(),'sync');
    }

    /**
     * 漫画评分
     */
    public function scoreComicsAction()
    {
        $this->jobService->create(new ComicsScoreJob(),'sync');
    }

    /**
     * 评论数量计算
     */
    public function commentCountAction()
    {
        $this->jobService->create(new CommentJob(),'sync');
    }

    /**
     * 数据清理
     * @param string $action
     */
    public function clearAction($action='')
    {
        $this->jobService->create(new ClearDataJob($action),'sync');
    }

    /**
     * 自身系统统计
     */
    public function reportServerAction()
    {
        $this->jobService->create(new ReportServerJob(),'sync');
    }

    /**
     * 分时段统计
     */
    public function reportServerHourAction()
    {
        $this->jobService->create(new ReportHourJob(date('Y-m-d')),'sync');
        if(in_array(date("H"),['01'])){
            $this->jobService->create(new ReportHourJob(date("Y-m-d",strtotime('-1day'))),'sync');
        }
    }

    /**
     * 上报管理系统
     */
    public function reportMmsAction()
    {
        $this->jobService->create(new ReportMmsJob(),'sync');
    }


    /**
     * 上报销售系统 *\/30 * * * *
     */
    public function reportMssAction()
    {
        $startAt = time() - 3600 * 6;
        $this->jobService->create(new ReportMssJob($startAt),'sync');
    }


    /**
     * 上报代理系统 *\/3 * * * *
     */
    public function reportAgentV2Action($startAt='')
    {
        $startAt = intval($startAt?:time() - 3600 * 3);
        $this->jobService->create(new ReportAgentV2Job($startAt),'sync');
    }

    /**
     * 上报代理系统 *\/3 * * * *
     */
    public function reportAgentV3Action($startAt='')
    {
        $startAt = intval($startAt?:time() - 3600 * 2);
        $this->jobService->create(new ReportAgentV3Job($startAt),'sync');
    }

    /**
     *  执行app跟踪
     */
    public function doTrackQueueAction()
    {
        $this->appTrackService->doTrackQueue();
    }

    /**
     *  用户行为
     */
    public function doActQueueAction()
    {
        $this->userActService->doActQueue();
    }

    /**
     * 同步游戏链接
     * @return void
     */
    public function asyncGameAction()
    {
        $this->jobService->create(new AsyncGameJob(),'sync');
    }

    /**
     * 同步cdn
     */
    public function asyncCdnAction()
    {
        $this->jobService->create(new CdnJob(),'sync');
    }

    /**
     * 游戏下分
     */
    public function balanceTransferAction()
    {
        $this->userBalanceService->balanceTransfer();
    }
}