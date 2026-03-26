<?php
declare(strict_types=1);

namespace App\Jobs\Common;
use App\Jobs\BaseJob;
use App\Models\ComicsChapterModel;
use App\Services\AdminUserService;
use App\Services\AnalysisAdminLogsService;
use App\Services\ComicsService;
use App\Services\ConfigService;
use App\Services\MovieService;
use App\Services\PostService;
use App\Utils\LogUtil;
use App\Utils\TelegramBot;

/**
 * 发送统计数据到群组
 * Class SendMsgToGroupJob
 * @property PostService $postService
 * @property MovieService $movieService
 * @property AdminUserService $adminUserService
 * @property ComicsService $comicsService
 * @property ComicsChapterModel $comicsChapterModel
 * @property AnalysisAdminLogsService $analysisAdminLogsService
 * @property ConfigService $configService
 * @package App\Jobs\Common
 */
class SendMsgToGroupJob extends BaseJob
{
    public function handler($uniqid)
    {
        // TODO: Implement handler() method.
        set_time_limit(0);
        @ini_set('memory_limit','1024M');
        $startDate = date("Y-m-d",strtotime("-1day"));
        $startTime = strtotime($startDate);
        $endTime = $startTime+24*60*60;
        $filterLoginNum = 0;
        $adminLogs = $this->analysisAdminLogsService->getList(['date_label'=>$startDate],[],[],0,1000);
        foreach($adminLogs as $adminLog){
            $adminUser = $this->adminUserService->findByID(intval($adminLog['admin_id']));
            if($adminUser['role_id']!=4){
                $filterLoginNum++;
            }
        }
        $returnArr = [
            '<b>日期: </b>' . $startDate,
            '<b>帖子新增: </b>' . $this->postService->count(['created_at'=>['$gte'=>$startTime,'$lte'=>$endTime]]),
            '<b>帖子总数: </b>' . $this->postService->count(),
            '<b>视频新增: </b>' . $this->movieService->count(['created_at'=>['$gte'=>$startTime,'$lte'=>$endTime]]),
            '<b>视频总数: </b>' . $this->movieService->count(),
            '<b>漫画新增: </b>' . $this->comicsService->count(['created_at'=>['$gte'=>$startTime,'$lte'=>$endTime]]),
            '<b>漫画总数: </b>' . $this->comicsService->count(),
            '<b>漫画章节新增: </b>' . $this->comicsChapterModel->count(['created_at'=>['$gte'=>$startTime,'$lte'=>$endTime]]),
//            '<b>今日后台登录人数: </b>' . $this->analysisAdminLogsService->count(['date_label'=>$startDate]),
            '<b>今日后台登录人数: </b>' . $filterLoginNum,
        ];

        $configs = $this->configService->getAll();
        if(empty($configs['project_group_id'])||empty($configs['project_bot_token'])){
            LogUtil::error("群组和机器人token未配置！");
            return false;
        }
        TelegramBot::sendMsg(
            implode("\n",$returnArr),
            $configs['project_group_id'],
            $configs['project_bot_token']
        );
    }

    public function success($uniqid)
    {
        // TODO: Implement success() method.
    }

    public function error($uniqid)
    {
        // TODO: Implement error() method.
    }

}