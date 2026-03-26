<?php


namespace App\Jobs\Common;


use App\Jobs\BaseJob;
use App\Models\AppLogModel;
use App\Services\AccountService;
use App\Services\CollectionsService;
use App\Services\ComicsHistoryService;
use App\Services\CommentService;
use App\Services\ConfigService;
use App\Services\CreditLogService;
use App\Services\MovieFavoriteService;
use App\Services\MovieHistoryService;
use App\Services\MovieService;
use App\Services\NovelHistoryService;
use App\Services\PlayService;
use App\Services\RechargeService;
use App\Services\SmsService;
use App\Services\UserActiveService;
use App\Services\UserActService;
use App\Services\UserOrderService;
use App\Services\UserService;
use App\Utils\CommonUtil;
use App\Utils\LogUtil;

/**
 * Class ClearDataJob
 * @property UserService $userService
 * @property UserActiveService $userActiveService
 * @property MovieFavoriteService $movieFavoriteService
 * @property UserOrderService $userOrderService
 * @property RechargeService $rechargeService
 * @property CollectionsService $collectionsService
 * @property AccountService $accountService
 * @property AppLogModel $appLogModel
 * @property MovieHistoryService   $movieHistoryService
 * @property MovieService $movieService
 * @property PlayService $playService
 * @property SmsService $smsService
 * @property CreditLogService $creditLogService
 * @property CommentService $commentService
 * @property ConfigService $configService
 * @property  ComicsHistoryService $comicsHistoryService
 * @property  NovelHistoryService $novelHistoryService
 * @property  UserActService $userActService
 * @package App\Jobs\Common
 */
class ClearDataJob extends BaseJob
{
    private $action;
    public function __construct($action='')
    {
        $this->action=$action;
    }

    public function handler($uniqid)
    {
        set_time_limit(0);
        // TODO: Implement handler() method.
        LogUtil::info("清理前,请确认已备份,10秒钟后任务执行");
        sleep(10);
        if($this->action){
            $this->{$this->action}();
        }else{
//            $this->user();
            $this->day();
            $this->movieHistory();
            $this->comicsHistory();
            $this->novelHistory();
            $this->userAct();
        }
    }

    public function user()
    {

    }

    /**
     * 清除日活数据
     * 保留60天
     */
    public function day()
    {
        LogUtil::info("Start clear day");
        $endAt  =strtotime(date('Y-m-d',strtotime("-30day")));
        while (true){
            $rows=$this->appLogModel->find(['created_at'=>['$lte'=>$endAt]],['_id'],[],0,1000);
            if(empty($rows)){LogUtil::debug("Clear day ok");break;}
            $ids=array_column($rows,'_id');
            $this->appLogModel->delete(['_id'=>['$in'=>$ids]]);
            LogUtil::debug("Clear day num=>".(count($ids)));
        }
    }

    /**
     * 清除视频日志
     * 保留90天
     */
    public function movieHistory()
    {
        LogUtil::info("Start clear movie history");
        $endAt  =strtotime(date('Y-m-d',strtotime("-90day")));
        while (true){
            $rows=$this->movieHistoryService->getList(['updated_at'=>['$lte'=>$endAt]],['_id'],[],0,1000);
            if(empty($rows)){LogUtil::debug("Clear movie history ok");break;}
            $ids=array_column($rows,'_id');
            $this->movieHistoryService->movieHistoryModel->delete(['_id'=>['$in'=>$ids]]);
            LogUtil::debug("Clear movie history num=>".(count($ids)));
        }
    }

    /**
     * 清除漫画浏览日志
     * 保留90天
     */
    public function comicsHistory()
    {
        LogUtil::info("Start clear comics history");
        $endAt  =strtotime(date('Y-m-d',strtotime("-90day")));
        while (true){
            $rows=$this->comicsHistoryService->getList(['updated_at'=>['$lte'=>$endAt]],['_id'],[],0,1000);
            if(empty($rows)){LogUtil::debug("Clear comics history ok");break;}
            $ids=array_column($rows,'_id');
            $this->comicsHistoryService->comicsHistoryModel->delete(['_id'=>['$in'=>$ids]]);
            LogUtil::debug("Clear comics history num=>".(count($ids)));
        }
    }

    /**
     * 清除小说
     * 保留90天
     */
    public function novelHistory()
    {
        LogUtil::info("Start clear novel history");
        $endAt  =strtotime(date('Y-m-d',strtotime("-90day")));
        while (true){
            $rows=$this->novelHistoryService->getList(['updated_at'=>['$lte'=>$endAt]],['_id'],[],0,1000);
            if(empty($rows)){LogUtil::debug("Clear novel history ok");break;}
            $ids=array_column($rows,'_id');
            $this->novelHistoryService->novelHistoryModel->delete(['_id'=>['$in'=>$ids]]);
            LogUtil::debug("Clear novel history num=>".(count($ids)));
        }
    }

    public function userAct()
    {
        LogUtil::info("Start clear user_act");
        $endAt  =strtotime(date('Y-m-d',strtotime("-15day")));
        while (true){
            $rows=$this->userActService->getList(['register_at'=>['$lte'=>$endAt]],['_id'],['register_at'=>1],0,10000);
            if(empty($rows)){LogUtil::debug("Clear user_act ok");break;}
            $ids=array_column($rows,'_id');
            $this->userActService->userActModel->delete(['_id'=>['$in'=>$ids]]);
            LogUtil::debug("Clear user_act num=>".(count($ids)));
        }
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